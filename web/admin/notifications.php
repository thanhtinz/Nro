<?php
$__active = 'notifications';
$__title  = 'Thông báo';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = trim((string)($_POST['name'] ?? ''));
        $text = trim((string)($_POST['text'] ?? ''));
        if ($name === '' || $text === '') {
            flash('Tiêu đề và nội dung không được để trống.');
        } elseif ($action === 'create') {
            $stmt = $c->prepare('INSERT INTO notify (name, text) VALUES (?, ?)');
            $stmt->bind_param('ss', $name, $text);
            $stmt->execute(); $stmt->close();
            flash('Đã thêm thông báo mới.');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $c->prepare('UPDATE notify SET name=?, text=? WHERE id=?');
            $stmt->bind_param('ssi', $name, $text, $id);
            $stmt->execute(); $stmt->close();
            flash("Đã cập nhật thông báo #$id.");
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $c->prepare('DELETE FROM notify WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute(); $ok = $stmt->affected_rows; $stmt->close();
        flash($ok ? "Đã xoá thông báo #$id." : "Không tìm thấy thông báo #$id.");
    }

    header('Location: notifications.php');
    exit();
}

$rows = $c->query('SELECT id, name, text FROM notify ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);

$edit = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    foreach ($rows as $r) {
        if ((int)$r['id'] === $editId) { $edit = $r; break; }
    }
}

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Quản lý Thông báo</h1>

<section class="gc-form">
    <h2><?= $edit ? 'Sửa thông báo #' . (int)$edit['id'] : 'Thêm thông báo mới' ?></h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e($tok) ?>">
        <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <label>Tiêu đề
            <input type="text" name="name" required value="<?= e($edit['name'] ?? '') ?>" placeholder="vd: Sự kiện Trung Thu">
        </label>
        <label>Nội dung
            <textarea name="text" rows="4" required placeholder="Nội dung thông báo hiển thị trong game..."><?= e($edit['text'] ?? '') ?></textarea>
        </label>
        <div class="submit-row">
            <button type="submit"><?= $edit ? 'Lưu thay đổi' : 'Thêm thông báo' ?></button>
            <?php if ($edit): ?><a class="clear" href="notifications.php">Huỷ</a><?php endif; ?>
        </div>
    </form>
</section>

<h2>Danh sách thông báo</h2>
<div class="tablewrap">
<table>
<thead><tr><th>ID</th><th>Tiêu đề</th><th>Nội dung</th><th>Thao tác</th></tr></thead>
<tbody>
<?php if (!$rows): ?>
    <tr><td colspan="4" class="empty">Chưa có thông báo nào.</td></tr>
<?php else: foreach ($rows as $r): ?>
    <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><b><?= e($r['name']) ?></b></td>
        <td class="dim" style="white-space:normal;max-width:520px"><?= nl2br(e($r['text'])) ?></td>
        <td class="actions">
            <a class="btn" href="notifications.php?edit=<?= (int)$r['id'] ?>">Sửa</a>
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
<p class="dim">Lưu ý: server nạp danh sách thông báo lúc khởi động. Thông báo mới có thể cần khởi động lại server để hiển thị trong game.</p>
<?php require_once __DIR__ . '/footer.php'; ?>
