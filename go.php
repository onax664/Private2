<?php
require __DIR__ . '/config.php';

/*
 * go.php
 *   /go.php            -> trang chọn: "Vượt link" hoặc "Mua link"
 *   /go.php?m=vuot     -> chuỗi vượt link: gtraffic -> gtraffic -> link4m
 *   /go.php?m=buy      -> chuyển sang Telegram để mua key
 */
$buyAmount = defined('LINKX_BUY_AMOUNT') ? (int) LINKX_BUY_AMOUNT : 10000;
$mode = $_GET['m'] ?? '';

if ($mode === 'buy') {
    header('Location: https://t.me/onaxscript');
    exit;
}

// ---------- Trang chọn ----------
if ($mode !== 'vuot') {
    $price = number_format($buyAmount, 0, ',', '.');
    ?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chọn cách nhận Key</title>
<style>
  *{box-sizing:border-box}
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;
       background:#faf8ff;color:#0f172a;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
  .card{width:100%;max-width:380px;background:#fff;border:2px solid #ddd6fe;border-radius:20px;padding:24px 18px;text-align:center}
  h1{margin:0 0 6px;font-size:22px;color:#6d28d9}
  p{margin:0 0 18px;color:#64748b;font-size:15px}
  a.btn{display:block;padding:14px 12px;margin-top:10px;border-radius:12px;font-size:16px;font-weight:700;text-decoration:none}
  a.vuot{background:#6d28d9;color:#fff;border:2px solid #6d28d9}
  a.buy{background:#fff;color:#6d28d9;border:2px solid #6d28d9}
  small{display:block;margin-top:6px;color:#64748b;font-size:13px}
  small.flow{margin-top:2px;color:#6d28d9;font-weight:600}
</style>
</head>
<body>
  <div class="card">
    <h1>Nhận Key</h1>
    <p>Chọn một trong hai cách bên dưới.</p>
    <a class="btn vuot" href="go.php?m=vuot">Vượt link</a>
    <small>Miễn phí, làm theo các bước vượt link.</small>
    <small class="flow">gtraffic.io->gtraffic.io->link4m.com</small>
    <a class="btn buy" href="https://t.me/onaxscript">Mua link - <?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>đ</a>
    <small>Trả phí để bỏ qua các bước vượt.</small>
  </div>
</body>
</html>
<?php
    exit;
}

// ---------- Tạo chuỗi link ----------
// Token ký HMAC: không phụ thuộc cookie (trình duyệt trong app vượt link hay mất session)
$t   = time() . '.' . bin2hex(random_bytes(8));
$sig = hash_hmac('sha256', $t, SECRET);
$callback = BASE_URL . '/callback.php?t=' . urlencode($t) . '&sig=' . $sig;

// Chặng cuối: link4m (gọi API rút gọn callback)
$ctx = stream_context_create(['http' => ['timeout' => 8]]);
$res = json_decode(@file_get_contents(
  'https://link4m.co/api-shorten/v2?api=' . LINK4M_API . '&url=' . urlencode($callback), false, $ctx
), true);
if (!$res || ($res['status'] ?? '') !== 'success') {
  http_response_code(502);
  exit('Link4m lỗi: ' . ($res['message'] ?? 'không phản hồi'));
}
$u = $res['shortenedUrl'];

// Bọc ngược từ trong ra ngoài: link4m <- gtra2 <- gtra1
// (người dùng đi: gtra1 -> gtra2 -> link4m -> callback)
$u = 'https://gtraffic.io/st?apikey=' . GTRAFFIC_API . '&url=' . urlencode($u); // gtra2
$u = 'https://gtraffic.io/st?apikey=' . GTRAFFIC_API . '&url=' . urlencode($u); // gtra1

header('Location: ' . $u);
exit;
