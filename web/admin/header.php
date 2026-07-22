<?php
// Header + menu dùng chung cho các trang admin (đã đăng nhập)
require_once __DIR__ . '/config.php';
require_admin();
$__active = $__active ?? '';
$__title  = $__title  ?? 'Admin';
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($__title) ?> · NRO Admin</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">🐉 NRO Admin</div>
    <nav>
        <a href="index.php"         class="<?= $__active==='dashboard'?'on':'' ?>">Tổng quan</a>
        <a href="accounts.php"      class="<?= $__active==='accounts'?'on':'' ?>">Tài khoản</a>
        <a href="players.php"       class="<?= $__active==='players'?'on':'' ?>">Nhân vật</a>
        <a href="giftcodes.php"     class="<?= $__active==='giftcodes'?'on':'' ?>">Giftcode</a>
        <a href="notifications.php" class="<?= $__active==='notifications'?'on':'' ?>">Thông báo</a>
        <a href="payments.php"      class="<?= $__active==='payments'?'on':'' ?>">Nạp thẻ</a>
    </nav>
    <div class="me">
        <span><?= e($_SESSION['admin_username'] ?? '') ?></span>
        <a href="logout.php" class="logout">Đăng xuất</a>
    </div>
</header>
<main class="wrap">
<?php if ($f = flash()): ?>
    <div class="flash"><?= e($f) ?></div>
<?php endif; ?>
