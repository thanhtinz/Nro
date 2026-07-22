<?php
$__active = 'payments';
$__title  = 'Nạp thẻ';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

/** Che bớt chuỗi nhạy cảm (serial/mã thẻ) */
function mask(?string $s): string
{
    $s = (string)$s;
    $n = strlen($s);
    if ($n <= 4) return str_repeat('•', $n);
    return substr($s, 0, 2) . str_repeat('•', max(2, $n - 4)) . substr($s, -2);
}

function table_exists(mysqli $c, string $t): bool
{
    // SHOW TABLES LIKE ? không hỗ trợ bind param -> dùng information_schema
    $stmt = $c->prepare(
        'SELECT 1 FROM information_schema.tables
          WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
    $stmt->bind_param('s', $t); $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0; $stmt->close();
    return $ok;
}

$hasNapthe   = table_exists($c, 'napthe');
$hasPayments = table_exists($c, 'payments');

$napRows = [];
if ($hasNapthe) {
    $res = $c->query('SELECT id, user_nap, telco, serial, code, amount, status, created_at
                        FROM napthe ORDER BY id DESC LIMIT 100');
    if ($res) $napRows = $res->fetch_all(MYSQLI_ASSOC);
}
require_once __DIR__ . '/header.php';
?>
<h1>Lịch sử nạp thẻ</h1>

<?php if (!$hasNapthe && !$hasPayments): ?>
    <div class="note">Không tìm thấy bảng <code>napthe</code> hoặc <code>payments</code> trong DB.</div>
<?php endif; ?>

<?php if ($hasNapthe): ?>
<h2>Bảng <code>napthe</code></h2>
<div class="tablewrap">
<table>
<thead><tr>
    <th>ID</th><th>Người nạp</th><th>Nhà mạng</th><th>Serial</th><th>Mã thẻ</th>
    <th>Mệnh giá</th><th>Trạng thái</th><th>Thời gian</th>
</tr></thead>
<tbody>
<?php if (!$napRows): ?>
    <tr><td colspan="8" class="empty">Chưa có lượt nạp nào.</td></tr>
<?php else: foreach ($napRows as $r):
    $st = (int)$r['status'];
    $stTxt = $st === 1 ? ['on','Thành công'] : ($st === 0 ? ['off','Chờ'] : ['ban','Thất bại']);
?>
    <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= e($r['user_nap']) ?></td>
        <td><?= e($r['telco']) ?></td>
        <td class="mono"><?= e(mask($r['serial'])) ?></td>
        <td class="mono"><?= e(mask($r['code'])) ?></td>
        <td><?= number_format((int)$r['amount']) ?></td>
        <td><span class="tag <?= $stTxt[0] ?>"><?= $stTxt[1] ?></span></td>
        <td class="dim"><?= e($r['created_at']) ?></td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<?php if ($hasPayments):
    $payRows = [];
    $res = $c->query('SELECT id, name, card_telco, card_serial, declared_amount, final_credited_amount, status_text, date
                        FROM payments ORDER BY id DESC LIMIT 100');
    if ($res) $payRows = $res->fetch_all(MYSQLI_ASSOC);
?>
<h2>Bảng <code>payments</code></h2>
<div class="tablewrap">
<table>
<thead><tr>
    <th>ID</th><th>Tài khoản</th><th>Nhà mạng</th><th>Serial</th>
    <th>Khai báo</th><th>Thực nhận</th><th>Trạng thái</th><th>Thời gian</th>
</tr></thead>
<tbody>
<?php if (!$payRows): ?>
    <tr><td colspan="8" class="empty">Chưa có giao dịch nào.</td></tr>
<?php else: foreach ($payRows as $r): ?>
    <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= e($r['name']) ?></td>
        <td><?= e($r['card_telco']) ?></td>
        <td class="mono"><?= e(mask($r['card_serial'])) ?></td>
        <td><?= number_format((int)$r['declared_amount']) ?></td>
        <td><?= number_format((int)$r['final_credited_amount']) ?></td>
        <td class="dim"><?= e($r['status_text']) ?></td>
        <td class="dim"><?= e($r['date']) ?></td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<p class="dim">Serial và mã thẻ được che một phần để tránh lộ thông tin. Hiển thị tối đa 100 dòng gần nhất.</p>
<?php require_once __DIR__ . '/footer.php'; ?>
