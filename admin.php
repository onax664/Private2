<?php
require __DIR__ . '/config.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
$F = __DIR__ . '/keys.txt';

function load($F) {
  $r = [];
  foreach (@file($F, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $l) {
    [$k, $e] = explode('|', $l) + [1 => 0];
    $r[$k] = (int)$e;
  }
  return $r;
}
function save($F, $r) {
  $s = '';
  foreach ($r as $k => $e) $s .= "$k|$e\n";
  file_put_contents($F, $s, LOCK_EX);
}
function h($s) { return htmlspecialchars((string)$s); }
function newkey() {
  return 'LQ-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));
}

$head = '<!DOCTYPE html><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quản lý key</title><style>body{font-family:sans-serif;margin:0;padding:16px;background:#faf8ff;color:#1e1033}table{width:100%;border-collapse:collapse}td,th{padding:8px 4px;border-bottom:1px solid #ddd6fe;font-size:14px;text-align:left}input,button{font-size:16px;padding:8px;border-radius:8px;border:1px solid #c4b5fd}button{background:#4c1d95;color:#fff;border:0}.x{background:#b91c1c;padding:4px 10px}.ok{color:#15803d}.no{color:#b91c1c}</style>';

$err = '';
if (isset($_POST['pw'])) {
  if (defined('ADMIN_HASH') && password_verify($_POST['pw'], ADMIN_HASH)) {
    session_regenerate_id(true);
    $_SESSION['ok'] = 1;
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    header('Location: admin.php');
    exit;
  }
  sleep(2);
  $err = 'Sai mật khẩu';
}
if (isset($_GET['out'])) { session_destroy(); header('Location: admin.php'); exit; }
if (empty($_SESSION['ok'])) {
  exit($head . '<h3>Đăng nhập quản trị</h3><p class="no">' . h($err) . '</p><form method="post"><input type="password" name="pw" placeholder="Mật khẩu" autofocus> <button>Vào</button></form>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
  $k = load($F);
  $a = $_POST['a'] ?? '';
  if ($a === 'add') $k[newkey()] = time() + max(1, (int)($_POST['hrs'] ?? 24)) * 3600;
  if ($a === 'del') unset($k[$_POST['key'] ?? '']);
  if ($a === 'purge') $k = array_filter($k, fn($e) => $e > time());
  save($F, $k);
  header('Location: admin.php');
  exit;
}

$k = load($F);
arsort($k);
$now = time();
$act = count(array_filter($k, fn($e) => $e > $now));
$c = '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
echo $head . '<h3>Quản lý key</h3><p>Còn hạn: <b>' . $act . '</b> · Hết hạn: <b>' . (count($k) - $act) . '</b> · <a href="?out=1">Đăng xuất</a></p>';
echo '<form method="post">' . $c . '<input type="hidden" name="a" value="add"><input type="number" name="hrs" value="24" min="1" style="width:70px"> giờ <button>Tạo key</button></form><br>';
echo '<form method="post">' . $c . '<input type="hidden" name="a" value="purge"><button>Xóa key hết hạn</button></form><br><table><tr><th>Key</th><th>Còn lại</th><th></th></tr>';
foreach ($k as $key => $e) {
  $left = $e - $now;
  $st = $left > 0 ? '<span class="ok">' . floor($left / 3600) . 'h ' . floor($left % 3600 / 60) . 'm</span>' : '<span class="no">hết hạn</span>';
  echo '<tr><td>' . h($key) . '</td><td>' . $st . '</td><td><form method="post">' . $c . '<input type="hidden" name="a" value="del"><input type="hidden" name="key" value="' . h($key) . '"><button class="x">Xóa</button></form></td></tr>';
}
echo '</table>';
