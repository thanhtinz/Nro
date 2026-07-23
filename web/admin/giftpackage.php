<?php
$__active = 'giftpackage';
$__title  = 'Gói quà';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

function bridge_ready(mysqli $c): bool {
    $r = $c->query("SELECT 1 FROM information_schema.tables
                     WHERE table_schema=DATABASE() AND table_name='gift_package' LIMIT 1");
    return $r && $r->num_rows > 0;
}
$ready = bridge_ready($c);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ready) {
    csrf_check();
    $do = $_POST['do'] ?? '';

    if ($do === 'create_pkg') {
        $name = trim($_POST['name'] ?? '');
        $title = trim($_POST['mail_title'] ?? '');
        $content = trim($_POST['mail_content'] ?? '');
        if ($name === '' || $title === '') { flash('Nhập tên gói và tiêu đề mail.'); }
        else {
            $stmt = $c->prepare('INSERT INTO gift_package (name, mail_title, mail_content) VALUES (?,?,?)');
            $stmt->bind_param('sss', $name, $title, $content); $stmt->execute();
            $pid = $stmt->insert_id; $stmt->close();
            flash('Đã tạo gói. Thêm vật phẩm bên dưới.');
            header('Location: giftpackage.php?edit=' . $pid); exit();
        }
    } elseif ($do === 'save_pkg') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? ''); $title = trim($_POST['mail_title'] ?? ''); $content = trim($_POST['mail_content'] ?? '');
        $stmt = $c->prepare('UPDATE gift_package SET name=?, mail_title=?, mail_content=? WHERE id=?');
        $stmt->bind_param('sssi', $name, $title, $content, $id); $stmt->execute(); $stmt->close();
        flash('Đã lưu gói #' . $id . '.');
        header('Location: giftpackage.php?edit=' . $id); exit();
    } elseif ($do === 'add_item') {
        $pid = (int)($_POST['package_id'] ?? 0);
        $item = (int)($_POST['item_id'] ?? 0);
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $optId = $_POST['option_id'] !== '' ? (int)$_POST['option_id'] : null;
        $optPar = (int)($_POST['option_param'] ?? 0);
        $stmt = $c->prepare('INSERT INTO gift_package_item (package_id, item_id, quantity, option_id, option_param) VALUES (?,?,?,?,?)');
        $stmt->bind_param('iiiii', $pid, $item, $qty, $optId, $optPar); $stmt->execute(); $stmt->close();
        flash('Đã thêm vật phẩm.');
        header('Location: giftpackage.php?edit=' . $pid); exit();
    } elseif ($do === 'del_item') {
        $iid = (int)($_POST['item_row'] ?? 0); $pid = (int)($_POST['package_id'] ?? 0);
        $stmt = $c->prepare('DELETE FROM gift_package_item WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $iid); $stmt->execute(); $stmt->close();
        header('Location: giftpackage.php?edit=' . $pid); exit();
    } elseif ($do === 'del_pkg') {
        $id = (int)($_POST['id'] ?? 0);
        $c->query('DELETE FROM gift_package WHERE id=' . $id);
        $c->query('DELETE FROM gift_package_item WHERE package_id=' . $id);
        flash('Đã xoá gói #' . $id . '.');
        header('Location: giftpackage.php'); exit();
    } elseif ($do === 'send') {
        $pid = (int)($_POST['id'] ?? 0);
        // snapshot gói -> gift_mail + gift_mail_item
        $stmt = $c->prepare('SELECT name, mail_title, mail_content FROM gift_package WHERE id=?');
        $stmt->bind_param('i', $pid); $stmt->execute();
        $pkg = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$pkg) { flash('Không thấy gói.'); header('Location: giftpackage.php'); exit(); }
        $items = $c->query('SELECT item_id, quantity, option_id, option_param FROM gift_package_item WHERE package_id=' . $pid)->fetch_all(MYSQLI_ASSOC);
        if (!$items) { flash('Gói chưa có vật phẩm — thêm trước khi gửi.'); header('Location: giftpackage.php?edit=' . $pid); exit(); }
        $stmt = $c->prepare('INSERT INTO gift_mail (title, content) VALUES (?,?)');
        $stmt->bind_param('ss', $pkg['mail_title'], $pkg['mail_content']); $stmt->execute();
        $mailId = $stmt->insert_id; $stmt->close();
        $ins = $c->prepare('INSERT INTO gift_mail_item (mail_id, item_id, quantity, option_id, option_param) VALUES (?,?,?,?,?)');
        foreach ($items as $it) {
            $oid = $it['option_id'] !== null ? (int)$it['option_id'] : null;
            $ins->bind_param('iiiii', $mailId, $it['item_id'], $it['quantity'], $oid, $it['option_param']);
            $ins->execute();
        }
        $ins->close();
        flash('Đã gửi gói "' . $pkg['name'] . '" tới toàn bộ người chơi. Ai đang online nhận trong ~3 giây; người khác nhận khi đăng nhập.');
        header('Location: giftpackage.php'); exit();
    }
}

$edit = null; $editItems = [];
if ($ready && isset($_GET['edit'])) {
    $stmt = $c->prepare('SELECT * FROM gift_package WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $_GET['edit']); $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($edit) {
        $editItems = $c->query('SELECT * FROM gift_package_item WHERE package_id=' . (int)$edit['id'] . ' ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    }
}
$packages = $ready ? $c->query('SELECT p.*, (SELECT COUNT(*) FROM gift_package_item i WHERE i.package_id=p.id) AS nItems
                                  FROM gift_package p ORDER BY p.id DESC')->fetch_all(MYSQLI_ASSOC) : [];
$sends = $ready ? $c->query('SELECT m.*, (SELECT COUNT(*) FROM gift_mail_received r WHERE r.mail_id=m.id) AS nRecv
                              FROM gift_mail m ORDER BY m.id DESC LIMIT 20')->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/header.php';
$tok = csrf_token();
function itemName($id){ return $id==-1?'Vàng':($id==-2?'Ngọc':($id==-3?'Ngọc khoá':'Item #'.$id)); }
?>
<h1>Gói quà — gửi qua hộp quà</h1>

<?php if (!$ready): ?>
    <div class="note" style="border-left-color:var(--danger)">Chưa cài cầu nối. Chạy lại <code>web/admin/sql/bridge.sql</code> (đã thêm bảng gói quà) & build server. Xem <code>docs/PHASE2_SERVER_BRIDGE.md</code>.</div>
<?php elseif ($edit): ?>
    <p><a href="giftpackage.php">← Danh sách gói</a></p>
    <div class="grid2">
      <div class="box">
        <h2>Sửa gói #<?= (int)$edit['id'] ?></h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="save_pkg"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
            <label>Tên gói</label><input type="text" name="name" value="<?= e($edit['name']) ?>" required>
            <label>Tiêu đề mail</label><input type="text" name="mail_title" value="<?= e($edit['mail_title']) ?>" required>
            <label>Nội dung mail</label><textarea name="mail_content" rows="4"><?= e($edit['mail_content']) ?></textarea>
            <button type="submit">Lưu gói</button>
        </form>
      </div>
      <div class="box">
        <h2>Thêm vật phẩm</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="add_item"><input type="hidden" name="package_id" value="<?= (int)$edit['id'] ?>">
            <label>Item ID <span class="dim">(-1 Vàng · -2 Ngọc · -3 Ngọc khoá · ≥0 item template)</span></label>
            <input type="number" name="item_id" value="0" required>
            <div class="row2">
                <div><label>Số lượng</label><input type="number" name="quantity" value="1" min="1"></div>
                <div><label>Option ID (trống nếu không)</label><input type="number" name="option_id" value=""></div>
            </div>
            <label>Option param</label><input type="number" name="option_param" value="0">
            <button type="submit">Thêm vật phẩm</button>
        </form>
      </div>
    </div>

    <h2>Vật phẩm trong gói (<?= count($editItems) ?>)</h2>
    <div class="tablewrap">
    <table>
    <thead><tr><th>Item</th><th>Số lượng</th><th>Option</th><th></th></tr></thead>
    <tbody>
    <?php if (!$editItems): ?><tr><td colspan="4" class="empty">Chưa có vật phẩm.</td></tr>
    <?php else: foreach ($editItems as $it): ?>
        <tr>
            <td><?= e(itemName((int)$it['item_id'])) ?></td>
            <td><?= (int)$it['quantity'] ?></td>
            <td class="dim"><?= $it['option_id']!==null ? 'opt '.(int)$it['option_id'].':'.(int)$it['option_param'] : '—' ?></td>
            <td class="actions">
                <form method="post" onsubmit="return confirm('Xoá vật phẩm này?')">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="del_item"><input type="hidden" name="package_id" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="item_row" value="<?= (int)$it['id'] ?>">
                    <button class="btn danger">Xoá</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
    </div>
    <form method="post" style="margin-top:14px" onsubmit="return confirm('GỬI gói này cho TOÀN BỘ người chơi?')">
        <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="send"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <button class="btn ok" style="padding:12px 24px">📨 Gửi gói này cho tất cả người chơi</button>
    </form>

<?php else: ?>
    <div class="box">
        <h2>Tạo gói quà mới</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="create_pkg">
            <label>Tên gói</label><input type="text" name="name" placeholder="vd: Quà tân thủ" required>
            <label>Tiêu đề mail</label><input type="text" name="mail_title" placeholder="vd: Quà từ Admin" required>
            <label>Nội dung mail</label><textarea name="mail_content" rows="3" placeholder="Lời nhắn gửi người chơi..."></textarea>
            <button type="submit">Tạo gói (rồi thêm vật phẩm)</button>
        </form>
    </div>

    <h2>Gói đã lưu (<?= count($packages) ?>)</h2>
    <div class="tablewrap">
    <table>
    <thead><tr><th>ID</th><th>Tên gói</th><th>Tiêu đề mail</th><th>Số VP</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if (!$packages): ?><tr><td colspan="5" class="empty">Chưa có gói nào.</td></tr>
    <?php else: foreach ($packages as $p): ?>
        <tr>
            <td><?= (int)$p['id'] ?></td>
            <td><b><?= e($p['name']) ?></b></td>
            <td class="dim"><?= e($p['mail_title']) ?></td>
            <td><?= (int)$p['nItems'] ?></td>
            <td class="actions">
                <a class="btn" href="giftpackage.php?edit=<?= (int)$p['id'] ?>">Sửa / Gửi</a>
                <form method="post" onsubmit="return confirm('GỬI gói #<?= (int)$p['id'] ?> cho TẤT CẢ người chơi?')">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="send"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn ok">📨 Gửi</button>
                </form>
                <form method="post" onsubmit="return confirm('Xoá gói này?')">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="del_pkg"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn danger">Xoá</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
    </div>

    <h2>Lịch sử gửi gần đây</h2>
    <div class="tablewrap">
    <table>
    <thead><tr><th>ID</th><th>Tiêu đề</th><th>Đã nhận</th><th>Lúc gửi</th></tr></thead>
    <tbody>
    <?php if (!$sends): ?><tr><td colspan="4" class="empty">Chưa gửi lần nào.</td></tr>
    <?php else: foreach ($sends as $m): ?>
        <tr><td><?= (int)$m['id'] ?></td><td><?= e($m['title']) ?></td><td><?= (int)$m['nRecv'] ?> người</td><td class="dim"><?= e($m['created_at']) ?></td></tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
    </div>
    <p class="dim">Ai đang online nhận trong ~3 giây; người khác nhận ngay khi đăng nhập. Mỗi người chỉ nhận 1 lần / lượt gửi. Vật phẩm vào thẳng túi + hiện thông báo nội dung mail.</p>
<?php endif; ?>
<?php require_once __DIR__ . '/footer.php'; ?>
