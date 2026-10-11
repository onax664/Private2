<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');
$downloads = (int)@file_get_contents(__DIR__ . '/downloads.txt');
$keys = count(glob(__DIR__ . '/used/*') ?: []); // mỗi key đã cấp để lại 1 file trong used/
echo json_encode(['downloads' => $downloads, 'keys' => $keys]);
