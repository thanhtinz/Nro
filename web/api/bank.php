<?php
/**
 * Webhook nhận biến động số dư từ SePay (https://sepay.vn).
 *
 * SePay gửi POST JSON khi có giao dịch, ví dụ:
 *   {
 *     "id": 92704, "gateway": "MBBank", "transactionDate": "2024-01-01 10:00:00",
 *     "accountNumber": "0392920228", "content": "[username] naptien",
 *     "transferType": "in", "transferAmount": 50000,
 *     "referenceCode": "FT24...", "description": "..."
 *   }
 * Xác thực bằng header:  Authorization: Apikey <api_key>
 *
 * Người chơi chuyển khoản với nội dung [username] naptien -> cộng vào account.vnd & tongnap.
 */

session_start();
include_once '../connect.php'; // $conn, $sepay

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request or data.'];

function log_activity($message, $type = 'info') {
    $log_file = __DIR__ . '/sepay_webhook_debug.log';
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($log_file, $timestamp . " [" . strtoupper($type) . "] " . $message . "\n", FILE_APPEND);
}

// ---- Log request để debug ----
log_activity("=========== SePay Webhook Request ===========");
log_activity("Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));

$input_raw = file_get_contents('php://input');
log_activity("Raw input (len " . strlen($input_raw) . "): " . ($input_raw === '' ? "[EMPTY]" : $input_raw));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    $response['message'] = 'Chỉ chấp nhận POST hoặc GET.';
    echo json_encode($response);
    exit();
}

// ---- Phân giải dữ liệu (JSON ưu tiên, fallback form/GET) ----
$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $data = json_decode($input_raw, true) ?: [];
    } elseif (stripos($ct, 'application/x-www-form-urlencoded') !== false || $ct === '') {
        parse_str($input_raw, $data);
        if (empty($data)) { $data = json_decode($input_raw, true) ?: []; }
    } else {
        $data = json_decode($input_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) { parse_str($input_raw, $data); }
    }
} else { // GET
    $data = $_GET;
}

if (empty($data) || !is_array($data)) {
    $response['message'] = 'Dữ liệu webhook không hợp lệ (không phải JSON/Form data hoặc trống).';
    log_activity("Dữ liệu không hợp lệ: " . $input_raw, 'ERROR');
    echo json_encode($response);
    exit();
}

// ---- Xác thực Authorization: Apikey <key> (cách chuẩn của SePay) ----
$api_key = $sepay['api_key'] ?? '';
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
$received_key = '';
if (preg_match('/^\s*Apikey\s+(.+)$/i', $auth_header, $m)) {
    $received_key = trim($m[1]);
}
// Tương thích ngược: chấp nhận chữ ký HMAC X-Signature nếu SePay cấu hình kiểu cũ.
$received_signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$hmac_ok = $received_signature !== '' && hash_equals(hash_hmac('sha256', $input_raw, $api_key), $received_signature);

if ($api_key !== '') {
    $apikey_ok = $received_key !== '' && hash_equals($api_key, $received_key);
    if (!$apikey_ok && !$hmac_ok) {
        http_response_code(401);
        $response['message'] = 'Xác thực webhook thất bại (Apikey/chữ ký không hợp lệ).';
        log_activity("Xác thực thất bại. Auth header: '" . $auth_header . "'", 'WARNING');
        echo json_encode($response);
        exit();
    }
}

// ---- Trích xuất trường giao dịch (SePay chuẩn + fallback tên cũ) ----
$transaction_id = $data['id'] ?? $data['referenceCode'] ?? $data['transaction_id'] ?? $data['refId'] ?? null;
if ($transaction_id !== null) { $transaction_id = (string)$transaction_id; }
$amount = (int)($data['transferAmount'] ?? $data['amount'] ?? 0);
$description = $data['content'] ?? $data['description'] ?? '';
$transfer_type = strtolower((string)($data['transferType'] ?? 'in')); // SePay: "in" = tiền vào
$bank_account_number = $data['accountNumber'] ?? $data['receiverAccount'] ?? '';
$sender_bank_name = $data['gateway'] ?? $data['senderBankName'] ?? '';
$transfer_time = $data['transactionDate'] ?? $data['transactionTime'] ?? date('Y-m-d H:i:s');

if (empty($transaction_id) || $amount <= 0) {
    $response['message'] = 'Dữ liệu giao dịch thiếu thông tin quan trọng từ SePay.';
    log_activity("Thiếu thông tin giao dịch: " . json_encode($data), 'ERROR');
    echo json_encode($response);
    exit();
}

// SePay chỉ báo webhook khi giao dịch thành công; chỉ cộng tiền cho giao dịch TIỀN VÀO.
$is_incoming = ($transfer_type === 'in');

// ---- Lấy username từ nội dung: [username] ----
$username_from_description = '';
if (preg_match('/\[(.*?)\]/', $description, $mu)) {
    $username_from_description = trim($mu[1]);
}
$prefix = $sepay['prefix'] ?? 'naptien';
$is_top_up_transaction = ($prefix === '' || stripos($description, $prefix) !== false);

if (empty($username_from_description)) {
    $response['message'] = 'Không tìm thấy tên người dùng trong nội dung chuyển khoản (yêu cầu định dạng [username]).';
    log_activity("Thiếu [username] trong nội dung: '" . $description . "' - TxID: " . $transaction_id, 'WARNING');
    echo json_encode($response);
    exit();
}

// ---- Kiểm tra username tồn tại ----
$stmt = $conn->prepare("SELECT id FROM `account` WHERE username = ?");
if (!$stmt) {
    log_activity("Lỗi prepare kiểm tra user: " . $conn->error, 'ERROR');
    $response['message'] = 'Lỗi hệ thống khi kiểm tra tài khoản.';
    echo json_encode($response);
    exit();
}
$stmt->bind_param("s", $username_from_description);
$stmt->execute();
$stmt->store_result();
$user_exists = $stmt->num_rows > 0;
$stmt->close();

if (!$user_exists) {
    $response['message'] = 'Tên tài khoản "' . htmlspecialchars($username_from_description) . '" không tồn tại.';
    log_activity("User không tồn tại: " . $username_from_description . " - TxID: " . $transaction_id, 'WARNING');
    echo json_encode($response);
    exit();
}

// ---- Chống trùng giao dịch ----
$stmt = $conn->prepare("SELECT id FROM bank_transfers WHERE transaction_id = ?");
$stmt->bind_param("s", $transaction_id);
$stmt->execute();
$stmt->store_result();
$already = $stmt->num_rows > 0;
$stmt->close();

if ($already) {
    $response['status'] = 'success'; // trả success để SePay không gửi lại
    $response['message'] = 'Giao dịch đã được xử lý trước đó.';
    log_activity("Trùng giao dịch: " . $transaction_id, 'INFO');
    echo json_encode($response);
    exit();
}

// ---- Lưu giao dịch + cộng tiền (transaction để đảm bảo nhất quán) ----
$will_credit = ($is_incoming && $is_top_up_transaction);
$final_status = $will_credit ? 'success' : ($is_incoming ? 'unknown' : 'ignored');
$is_credited = $will_credit ? 1 : 0;

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO bank_transfers
           (`transaction_id`, `username`, `amount`, `description`, `status`, `sender_bank_name`, `created_at`, `is_credited`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) { throw new Exception("prepare insert: " . $conn->error); }
    $stmt->bind_param(
        "ssissssi",
        $transaction_id, $username_from_description, $amount, $description,
        $final_status, $sender_bank_name, $transfer_time, $is_credited
    );
    if (!$stmt->execute()) { throw new Exception("execute insert: " . $stmt->error); }
    $stmt->close();

    if ($will_credit) {
        $stmt = $conn->prepare("UPDATE `account` SET vnd = vnd + ?, tongnap = tongnap + ? WHERE username = ?");
        if (!$stmt) { throw new Exception("prepare update balance: " . $conn->error); }
        $stmt->bind_param("iis", $amount, $amount, $username_from_description);
        if (!$stmt->execute()) { throw new Exception("execute update balance: " . $stmt->error); }
        $stmt->close();
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    log_activity("Lỗi xử lý giao dịch " . $transaction_id . ": " . $e->getMessage(), 'ERROR');
    $response['message'] = 'Lỗi hệ thống khi xử lý giao dịch.';
    echo json_encode($response);
    if (isset($conn) && $conn->ping()) { $conn->close(); }
    exit();
}

if ($will_credit) {
    $response['status'] = 'success';
    $response['message'] = 'Nạp tiền thành công! Đã cộng ' . number_format($amount) . ' VNĐ cho ' . $username_from_description . '.';
    log_activity("Cộng " . $amount . " VNĐ cho " . $username_from_description . " (TxID: " . $transaction_id . ")", 'INFO');
} else {
    $response['status'] = 'success'; // ghi nhận, không gửi lại
    $response['message'] = $is_incoming
        ? 'Giao dịch ghi nhận nhưng không phải nội dung nạp tiền hợp lệ.'
        : 'Giao dịch tiền ra, đã ghi nhận (không cộng tiền).';
    log_activity("Ghi nhận không cộng tiền. type=" . $transfer_type . " topup=" . ($is_top_up_transaction ? '1' : '0') . " TxID: " . $transaction_id, 'INFO');
}

if (isset($conn) && $conn->ping()) { $conn->close(); }
echo json_encode($response);
