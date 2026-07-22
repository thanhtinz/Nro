<?php
require_once __DIR__ . '/config.php';

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập tài khoản và mật khẩu.';
    } else {
        $stmt = db()->prepare(
            'SELECT id, username, password, admin, is_admin, ban
               FROM account WHERE username = ? LIMIT 1'
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res || !verify_password($password, $res['password'])) {
            $error = 'Sai tài khoản hoặc mật khẩu.';
        } elseif ((int)$res['ban'] === 1) {
            $error = 'Tài khoản đang bị khoá.';
        } elseif ((int)$res['admin'] !== 1 && (int)$res['is_admin'] !== 1) {
            $error = 'Tài khoản không có quyền quản trị.';
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$res['id'];
            $_SESSION['admin_username'] = $res['username'];
            header('Location: index.php');
            exit();
        }
    }
}
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Đăng nhập · NRO Admin</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
<form class="login-card" method="post" autocomplete="off">
    <div class="login-logo">🐉 NRO Admin</div>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Tài khoản admin</label>
    <input type="text" name="username" required autofocus>
    <label>Mật khẩu</label>
    <input type="password" name="password" required>
    <button type="submit">Đăng nhập</button>
    <p class="hint">Chỉ tài khoản có <code>admin=1</code> hoặc <code>is_admin=1</code> mới vào được.</p>
</form>
</body>
</html>
