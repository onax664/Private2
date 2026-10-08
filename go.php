<?php
require __DIR__ . '/config.php';

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

// Bọc ngược từ trong ra ngoài: link4m <- gtra2 <- gtra1 <- vuotlink <- linkx
$u = 'https://gtraffic.io/st?apikey=' . GTRAFFIC_API . '&url=' . urlencode($u); // gtra2
$u = 'https://gtraffic.io/st?apikey=' . GTRAFFIC_API . '&url=' . urlencode($u); // gtra1
$u = 'https://vuotlink.xyz/st?api='    . VUOTLINK_API . '&url=' . urlencode($u);
$u = 'https://linkx.me/st?api='        . LINKX_API    . '&buy_type=buy-all&amount=10000&url=' . urlencode($u);

header('Location: ' . $u);
