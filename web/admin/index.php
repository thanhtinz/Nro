<?php
$__active = 'dashboard';
$__title  = 'Tổng quan';
require_once __DIR__ . '/header.php';

$c = db();

/** Lấy 1 giá trị số an toàn từ 1 câu SELECT (không tham số động) */
function scalar(mysqli $c, string $sql): int
{
    $r = $c->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_row();
    return (int)($row[0] ?? 0);
}

$totalAcc    = scalar($c, 'SELECT COUNT(*) FROM account');
$totalAdmin  = scalar($c, 'SELECT COUNT(*) FROM account WHERE admin=1 OR is_admin=1');
$totalBan    = scalar($c, 'SELECT COUNT(*) FROM account WHERE ban=1');
$totalPlayer = scalar($c, 'SELECT COUNT(*) FROM player');
$sumNap      = scalar($c, 'SELECT COALESCE(SUM(tongnap),0) FROM account');

// Đếm nạp thẻ nếu bảng tồn tại
$napCount = 0;
if ($r = $c->query("SHOW TABLES LIKE 'napthe'")) {
    if ($r->num_rows) $napCount = scalar($c, 'SELECT COUNT(*) FROM napthe');
}
?>
<h1>Tổng quan hệ thống</h1>
<div class="cards">
    <div class="card"><div class="num"><?= number_format($totalAcc) ?></div><div class="lbl">Tài khoản</div></div>
    <div class="card"><div class="num"><?= number_format($totalPlayer) ?></div><div class="lbl">Nhân vật</div></div>
    <div class="card"><div class="num"><?= number_format($totalAdmin) ?></div><div class="lbl">Admin</div></div>
    <div class="card warn"><div class="num"><?= number_format($totalBan) ?></div><div class="lbl">Bị khoá</div></div>
    <div class="card ok"><div class="num"><?= number_format($sumNap) ?></div><div class="lbl">Tổng nạp (đ)</div></div>
    <div class="card"><div class="num"><?= number_format($napCount) ?></div><div class="lbl">Lượt nạp thẻ</div></div>
</div>

<h2>Truy cập nhanh</h2>
<div class="quick">
    <a class="qbtn" href="accounts.php">👤 Quản lý tài khoản</a>
    <a class="qbtn" href="players.php">🎮 Quản lý nhân vật</a>
    <a class="qbtn" href="giftcodes.php">🎁 Quản lý giftcode</a>
    <a class="qbtn" href="notifications.php">📢 Gửi thông báo</a>
    <a class="qbtn" href="payments.php">💳 Lịch sử nạp thẻ</a>
</div>

<div class="note">
    <b>Lưu ý bảo mật:</b> panel này đã thay thế các file admin ẩn cũ trong <code>images/</code> và
    <code>assets/</code> (đã bị vô hiệu hoá). Nên đổi mật khẩu DB, và cân nhắc hash mật khẩu người dùng.
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
