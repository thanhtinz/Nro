<?php
// connect.php
$ip_sv = "localhost";
$dbname_sv = "ngocrong";
$user_sv = "root";
$pass_sv = "";

// ===== Cấu hình cổng thanh toán SePay (phương thức nạp DUY NHẤT) =====
// Tài khoản ngân hàng liên kết SePay + khoá API webhook.
$sepay = [
    'bank_code'    => 'MB',            // Mã ngân hàng dùng cho VietQR/SePay (vd: MB, VCB, ACB...)
    'bank_name'    => 'MBBank',        // Tên hiển thị ngân hàng
    'account_no'   => '0392920228',    // Số tài khoản nhận tiền
    'account_name' => 'LUONG VAN TAN', // Tên chủ tài khoản
    // Khoá API webhook (SePay gửi header: Authorization: Apikey <key>)
    'api_key'      => 'XDVTMYSFTKSCXPDUOW74OBFC6IVLEH6G8UNTT15R0JARPMWYXYJ3EKIXQAK7AVPY',
    'prefix'       => 'naptien',       // Từ khoá bắt buộc trong nội dung chuyển khoản
];

// (Đã ngừng) Cấu hình cổng thẻ cào Thesieure - giữ lại để tương thích khai báo cũ.
$thesieure_url = 'https://thesieure.com/chargingws/v2';
$thesieure_partner_id = '97860629743';
$thesieure_partner_key = 'c631d6023de6ffc308f0d01078dcde85';


$conn = new mysqli($ip_sv, $user_sv, $pass_sv, $dbname_sv);

if ($conn->connect_error) {
    die("Lỗi kết nối database: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

date_default_timezone_set('Asia/Ho_Chi_Minh');
?>