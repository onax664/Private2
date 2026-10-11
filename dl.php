<?php
$links = [
  'red' => 'https://www.mediafire.com/file/khkrg9zf3fshs7e/LQMOD_1.64.1.7_RED.apk/file',
  'tim' => 'https://www.mediafire.com/file/simtb2xynhmgyha/LQMOD_1.64.1.7_TIM.apk/file',
];
$v = $_GET['v'] ?? '';
if (!isset($links[$v])) {
  http_response_code(404);
  exit('Không tìm thấy bản tải.');
}
$fp = fopen(__DIR__ . '/downloads.txt', 'c+');
if ($fp) {
  if (flock($fp, LOCK_EX)) {
    $n = (int)stream_get_contents($fp) + 1;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string)$n);
    fflush($fp);
    flock($fp, LOCK_UN);
  }
  fclose($fp);
}
header('Location: ' . $links[$v]);
