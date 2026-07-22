<?php
/**
 * (ĐÃ NGỪNG) Endpoint nạp thẻ cào qua Thesieure.
 *
 * Toàn bộ phương thức thanh toán đã chuyển sang chuyển khoản ngân hàng qua SePay.
 * Endpoint này giữ lại để phản hồi rõ ràng cho các client cũ còn gọi tới.
 */
session_start();
header('Content-Type: application/json');

http_response_code(410); // Gone
echo json_encode([
    'success' => false,
    'status'  => 'discontinued',
    'message' => 'Nạp thẻ cào đã ngừng hỗ trợ. Vui lòng nạp bằng hình thức chuyển khoản ngân hàng (SePay) tại trang Nạp Tiền.',
]);
