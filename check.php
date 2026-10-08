<?php
header('Content-Type: application/json');
$key = strtoupper(trim($_REQUEST['key'] ?? ''));
$ok = false;
$exp = 0;
if ($key !== '') {
  foreach (@file(__DIR__ . '/keys.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $l) {
    [$k, $e] = explode('|', $l) + [1 => 0];
    if (hash_equals($k, $key)) { $exp = (int)$e; $ok = $exp > time(); break; }
  }
}
echo json_encode(['valid' => $ok, 'expires' => $ok ? $exp : null]);
