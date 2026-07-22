<?php
$__active = 'notify';
$__title  = 'Thông báo';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $text = trim($_POST['text'] ?? '');
        if ($name === '' || $text === '') {
            flash('Vui lòng nhập tiêu đề và nội dung.');
        } else {
            $stmt = $c->prepare('INSERT INTO notify (name, text) VALUES (?, ?)');
            $stmt->bind_param('ss', $name, $text);
            $stmt->execute(); $stmt->close();
            flash('Đã thêm thông báo. (Server cần nạp lại danh sách để hiển thị)');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $c->prepare('DELETE FROM notify WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        flash('Đã xoá thông báo #' . $id . '.');
    }
    header('Location: notify.php'); exit();
}

$rows = $c->query('SELECT id, name, text FROM notify ORDER BY id DESC LIMIT 200')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Thông báo / Tin tức</h1>

<div class="box">
    <h2>Thêm thông báo</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e($tok) ?>">
        <input type="hidden" name="action" value="create">
        <label>Tiêu đề</label>
        <input type="text" name="name" required placeholder="vd: Bảo trì máy chủ">
        <label>Nội dung</label>
        <textarea name="text" rows="4" required placeholder="Nội dung thông báo hiển thị cho người chơi..."></textarea>
        <button type="submit">Đăng thông báo</button>
    </form>
</div>

<div class="note">
    Server nạp bảng <code>notify</code> vào bộ nhớ <b>lúc khởi động</b>. Thông báo mới thêm ở đây
    sẽ hiển thị sau khi <b>server nạp lại / khởi động lại</b> (hoặc khi game truy vấn trực tiếp bảng này).
</div>

<h2>Danh sách thông báo (<?= count($rows) ?>)</h2>
<div class="tablewrap">
<table>
<thead><tr><th>ID</th><th>Tiêu đề</th><th>Nội dung</th><th>Thao tác</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="4" class="empty">Chưa có thông báo.</td></tr>
<?php else: foreach ($rows as $r): ?>
    <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= e($r['name']) ?></td>
        <td class="dim"><?= e(mb_strimwidth($r['text'], 0, 90, '…')) ?></td>
        <td class="actions">
            <form method="post" onsubmit="return confirm('Xoá thông báo này?')">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn danger">Xoá</button>
            </form>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
