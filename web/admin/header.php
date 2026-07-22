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
        <a href="server.php"   class="<?= $__active==='server'?'on':'' ?>">⚙ Server</a>
        <a href="events.php"   class="<?= $__active==='events'?'on':'' ?>">Sự kiện</a>
        <span class="navsep"></span>
        <a href="items.php"  class="<?= $__active==='items'?'on':'' ?>">Vật phẩm</a>
        <a href="bosses.php" class="<?= $__active==='bosses'?'on':'' ?>">Boss/Quái</a>
        <a href="npcs.php"   class="<?= $__active==='npcs'?'on':'' ?>">NPC</a>
        <a href="maps.php"   class="<?= $__active==='maps'?'on':'' ?>">Bản đồ</a>
        <a href="badges.php" class="<?= $__active==='badges'?'on':'' ?>">Danh hiệu</a>
        <a href="shops.php"  class="<?= $__active==='shops'?'on':'' ?>">Cửa hàng</a>
        <a href="clans.php"  class="<?= $__active==='clans'?'on':'' ?>">Bang hội</a>
        <a href="tasks.php"  class="<?= $__active==='tasks'?'on':'' ?>">Nhiệm vụ</a>
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
