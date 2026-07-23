<?php
/**
 * Admin panel - cấu hình & bảo vệ chung
 * - Dùng chung kết nối DB với web (../connect.php -> $conn mysqli)
 * - Quản lý session, kiểm tra quyền admin, CSRF, escape output
 *
 * Quyền admin = cột account.admin = 1 HOẶC account.is_admin = 1
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/servers_store.php';

// Chuyển máy chủ đang quản lý (từ bộ chọn trên header)
if (isset($_POST['__switch_sv'])) {
    if (admin_server_by_key((string)$_POST['__switch_sv'])) {
        $_SESSION['admin_sv'] = (string)$_POST['__switch_sv'];
    }
    header('Location: ' . ($_SERVER['REQUEST_URI'] ?? 'index.php'));
    exit();
}

// Kết nối DB của máy chủ đang chọn
$__sv = admin_current_server();
$conn = @new mysqli($__sv['host'], $__sv['user'], $__sv['pass'], $__sv['dbname']);
if ($conn->connect_error) {
    die('Lỗi kết nối DB máy chủ "' . htmlspecialchars($__sv['name'], ENT_QUOTES) . '": ' . htmlspecialchars($conn->connect_error, ENT_QUOTES));
}
$conn->set_charset('utf8mb4');

/** Trả về đối tượng mysqli (của máy chủ đang chọn) */
function db(): mysqli
{
    global $conn;
    return $conn;
}

/** Thông tin máy chủ đang chọn */
function current_server(): array
{
    return admin_current_server();
}

/** Escape output chống XSS */
function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * So khớp mật khẩu, tương thích cả mật khẩu đã hash lẫn plaintext (source cũ).
 * Khuyến nghị: dần chuyển sang password_hash().
 */
function verify_password(string $input, string $stored): bool
{
    $info = password_get_info($stored);
    if (!empty($info['algo'])) {
        return password_verify($input, $stored);
    }
    return hash_equals($stored, $input); // legacy plaintext
}

/** Sinh / lấy CSRF token cho form */
function csrf_token(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

/** Kiểm tra CSRF token khi submit POST; dừng nếu sai */
function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || empty($_SESSION['admin_csrf']) || !hash_equals($_SESSION['admin_csrf'], $token)) {
        http_response_code(419);
        die('CSRF token không hợp lệ. Vui lòng tải lại trang.');
    }
}

/** Đang đăng nhập admin? */
function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Cổng bảo vệ: gọi ở đầu mọi trang admin (trừ login) */
function require_admin(): void
{
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

/** Thông báo flash 1 lần */
function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['admin_flash'] = $msg;
        return null;
    }
    $m = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    return $m;
}
