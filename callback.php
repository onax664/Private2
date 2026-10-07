<?php
require __DIR__ . '/config.php';

function fail($msg) {
  http_response_code(403);
  exit('<meta name="viewport" content="width=device-width,initial-scale=1"><body style="font-family:sans-serif;padding:24px;color:#4c1d95"><h2>Không nhận được key</h2><p>' . htmlspecialchars($msg) . '</p><a href="getkey.html">Quay lại</a>');
}

$t   = $_GET['t']   ?? '';
$sig = $_GET['sig'] ?? '';
if (!hash_equals(hash_hmac('sha256', $t, SECRET), $sig)) fail('Link không hợp lệ.');

[$issued] = explode('.', $t) + [0];
if (time() - (int)$issued < MIN_SECONDS) fail('Bạn vượt link quá nhanh. Hãy hoàn thành đầy đủ các bước.');
if (time() - (int)$issued > 3600)        fail('Link đã hết hạn, hãy vượt lại.');

// Mỗi token chỉ dùng 1 lần
$dir = __DIR__ . '/used'; if (!is_dir($dir)) mkdir($dir, 0700);
$f = $dir . '/' . hash('sha256', $t);
if (file_exists($f)) fail('Link này đã được dùng.');
touch($f);

// Tạo key và lưu vào keys.txt (định dạng: key|hết hạn)
$key = 'LQ-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));
file_put_contents(__DIR__ . '/keys.txt', $key . '|' . (time() + KEY_TTL) . "\n", FILE_APPEND | LOCK_EX);
?>
<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Key của bạn</title>
<style>
body{margin:0;font-family:Inter,"Segoe UI",Arial,sans-serif;background:#faf8ff;color:#1e1033}
.box{max-width:400px;margin:60px auto;padding:24px;background:#fff;border:2px solid #ddd6fe;border-radius:22px;text-align:center;box-shadow:0 8px 24px rgba(76,29,149,.12)}
h2{color:#2e1065;margin:0 0 16px}
.key{font-size:22px;font-weight:700;letter-spacing:.06em;color:#4c1d95;background:#f5f3ff;border-radius:12px;padding:16px;word-break:break-all}
button{margin-top:16px;width:100%;height:50px;border:0;border-radius:12px;background:#4c1d95;color:#fff;font-size:18px;font-weight:700}
small{display:block;margin-top:12px;color:#6b6485}
</style></head><body>
<div class="box">
  <h2>Key của bạn</h2>
  <div class="key" id="k"><?= htmlspecialchars($key) ?></div>
  <button onclick="navigator.clipboard.writeText(document.getElementById('k').textContent).then(()=>this.textContent='Đã sao chép')">Sao chép key</button>
  <small>Key có hiệu lực 24 giờ.</small>
</div>
</body></html>
