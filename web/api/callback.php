<?php
/**
 * (ĐÃ NGỪNG) Callback kết quả nạp thẻ từ Thesieure.
 *
 * Phương thức thẻ cào đã ngừng; toàn bộ nạp tiền xử lý qua webhook SePay (bank.php).
 * Chỉ ghi log và trả OK để cổng cũ không retry.
 */
$log_file = __DIR__ . '/card_api_debug.log';
$timestamp = date('[Y-m-d H:i:s]');
file_put_contents(
    $log_file,
    $timestamp . " [INFO] Thesieure callback bị bỏ qua (đã ngừng thẻ cào). POST: " . json_encode($_POST) . "\n",
    FILE_APPEND
);

http_response_code(200);
echo "OK";
