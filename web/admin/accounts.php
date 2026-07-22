<?php
$__active = 'accounts';
$__title  = 'Tài khoản';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

// ---- Xử lý hành động (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['account_id'] ?? 0);

    if ($id <= 0) {
        flash('ID tài khoản không hợp lệ.');
        header('Location: accounts.php'); exit();
    }

    switch ($action) {
        case 'ban':
        case 'unban':
            $val = $action === 'ban' ? 1 : 0;
            $stmt = $c->prepare('UPDATE account SET ban=? WHERE id=?');
            $stmt->bind_param('ii', $val, $id); $stmt->execute(); $stmt->close();
            flash($action === 'ban' ? 'Đã khoá tài khoản #' . $id : 'Đã mở khoá tài khoản #' . $id);
            break;

        case 'grant_admin':
        case 'revoke_admin':
            $val = $action === 'grant_admin' ? 1 : 0;
            // set cả 2 cột để đồng bộ với game (dùng admin & is_admin)
            $stmt = $c->prepare('UPDATE account SET admin=?, is_admin=? WHERE id=?');
            $stmt->bind_param('iii', $val, $val, $id); $stmt->execute(); $stmt->close();
            flash($val ? 'Đã cấp quyền admin cho #' . $id : 'Đã gỡ quyền admin của #' . $id);
            break;

        case 'activate':
            $stmt = $c->prepare('UPDATE account SET active=1 WHERE id=?');
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            flash('Đã kích hoạt tài khoản #' . $id);
            break;

        case 'add_resource':
            $field  = $_POST['field'] ?? '';
            $amount = (int)($_POST['amount'] ?? 0);
            $allowed = ['vnd', 'vang', 'vip', 'tongnap', 'event_point'];
            if (!in_array($field, $allowed, true)) {
                flash('Trường không hợp lệ.');
                break;
            }
            // tên cột nằm trong whitelist => an toàn khi nội suy
            $stmt = $c->prepare("UPDATE account SET `$field` = `$field` + ? WHERE id=?");
            $stmt->bind_param('ii', $amount, $id); $stmt->execute(); $stmt->close();
            flash("Đã cộng $amount vào `$field` cho tài khoản #$id");
            break;

        default:
            flash('Hành động không hợp lệ.');
    }
    header('Location: accounts.php' . (isset($_POST['q']) && $_POST['q'] !== '' ? '?q=' . urlencode($_POST['q']) : ''));
    exit();
}

// ---- Tìm kiếm / liệt kê ----
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $c->prepare(
        'SELECT id, username, ban, admin, is_admin, active, vnd, vang, vip, tongnap, last_time_login, ip_address
           FROM account WHERE username LIKE ? OR id = ? ORDER BY id DESC LIMIT 100'
    );
    $idq = ctype_digit($q) ? (int)$q : 0;
    $stmt->bind_param('si', $like, $idq);
} else {
    $stmt = $c->prepare(
        'SELECT id, username, ban, admin, is_admin, active, vnd, vang, vip, tongnap, last_time_login, ip_address
           FROM account ORDER BY id DESC LIMIT 50'
    );
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Quản lý tài khoản</h1>

<form class="search" method="get">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Tìm theo username hoặc ID...">
    <button type="submit">Tìm</button>
    <?php if ($q !== ''): ?><a class="clear" href="accounts.php">Xoá lọc</a><?php endif; ?>
</form>

<div class="tablewrap">
<table>
<thead><tr>
    <th>ID</th><th>Username</th><th>Trạng thái</th><th>VNĐ</th><th>Vàng</th><th>VIP</th>
    <th>Tổng nạp</th><th>Đăng nhập cuối</th><th>Thao tác</th>
</tr></thead>
<tbody>
<?php if (!$rows): ?>
    <tr><td colspan="9" class="empty">Không có tài khoản nào.</td></tr>
<?php else: foreach ($rows as $r):
    $isAdmin = ((int)$r['admin'] === 1 || (int)$r['is_admin'] === 1);
    $isBan   = ((int)$r['ban'] === 1);
?>
    <tr>
        <td><?= (int)$r['id'] ?></td>
        <td>
            <?= e($r['username']) ?>
            <?php if ($isAdmin): ?><span class="tag admin">ADMIN</span><?php endif; ?>
        </td>
        <td>
            <?php if ($isBan): ?><span class="tag ban">Khoá</span>
            <?php elseif ((int)$r['active'] !== 1): ?><span class="tag off">Chưa KH</span>
            <?php else: ?><span class="tag on">Hoạt động</span><?php endif; ?>
        </td>
        <td><?= number_format((int)$r['vnd']) ?></td>
        <td><?= number_format((int)$r['vang']) ?></td>
        <td><?= (int)$r['vip'] ?></td>
        <td><?= number_format((int)$r['tongnap']) ?></td>
        <td class="dim"><?= e($r['last_time_login']) ?></td>
        <td class="actions">
            <form method="post" onsubmit="return confirm('Xác nhận thao tác?')">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <input type="hidden" name="account_id" value="<?= (int)$r['id'] ?>">
                <?php if ($isBan): ?>
                    <button name="action" value="unban" class="btn ok">Mở khoá</button>
                <?php else: ?>
                    <button name="action" value="ban" class="btn danger">Khoá</button>
                <?php endif; ?>
                <?php if ($isAdmin): ?>
                    <button name="action" value="revoke_admin" class="btn">Gỡ admin</button>
                <?php else: ?>
                    <button name="action" value="grant_admin" class="btn">Cấp admin</button>
                <?php endif; ?>
                <?php if ((int)$r['active'] !== 1): ?>
                    <button name="action" value="activate" class="btn">Kích hoạt</button>
                <?php endif; ?>
            </form>
            <form method="post" class="inline-add">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <input type="hidden" name="account_id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="action" value="add_resource">
                <select name="field">
                    <option value="vnd">VNĐ</option>
                    <option value="vang">Vàng</option>
                    <option value="vip">VIP</option>
                    <option value="event_point">Điểm SK</option>
                    <option value="tongnap">Tổng nạp</option>
                </select>
                <input type="number" name="amount" value="0" step="1" style="width:90px">
                <button class="btn small">Cộng</button>
            </form>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
<p class="dim">Hiển thị tối đa <?= $q !== '' ? '100' : '50' ?> tài khoản. Dùng ô tìm kiếm để lọc.</p>
<?php require_once __DIR__ . '/footer.php'; ?>
