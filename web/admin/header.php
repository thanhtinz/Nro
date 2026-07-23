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
        <a href="index.php"    class="<?= $__active==='dashboard'?'on':'' ?>">Tổng quan</a>
        <a href="accounts.php" class="<?= $__active==='accounts'?'on':'' ?>">Tài khoản</a>
        <a href="players.php"  class="<?= $__active==='players'?'on':'' ?>">Nhân vật</a>
        <a href="giftcode.php" class="<?= $__active==='giftcode'?'on':'' ?>">Giftcode</a>
        <a href="notify.php"   class="<?= $__active==='notify'?'on':'' ?>">Thông báo</a>
        <a href="payments.php" class="<?= $__active==='payments'?'on':'' ?>">Nạp thẻ</a>
        <a href="data.php"     class="<?= $__active==='data'?'on':'' ?>">Dữ liệu game</a>
        <span class="navsep"></span>
        <a href="server.php"   class="<?= $__active==='server'?'on':'' ?>">⚙ Server</a>
        <a href="events.php"   class="<?= $__active==='events'?'on':'' ?>">Sự kiện</a>
        <a href="schedule.php" class="<?= $__active==='schedule'?'on':'' ?>">Lịch</a>
        <a href="welfare.php"  class="<?= $__active==='welfare'?'on':'' ?>">Phúc lợi</a>
        <a href="servers.php"  class="<?= $__active==='servers'?'on':'' ?>">Máy chủ</a>
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
