<?php
$__active = 'manage_servers';
$__title  = 'Máy chủ quản lý';
require_once __DIR__ . '/config.php';
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    $list = admin_servers();

    if ($do === 'save') {
        $orig = trim($_POST['orig_key'] ?? '');
        $row = [
            'key'    => trim($_POST['key'] ?? ''),
            'name'   => trim($_POST['name'] ?? ''),
            'host'   => trim($_POST['host'] ?? 'localhost'),
            'dbname' => trim($_POST['dbname'] ?? ''),
            'user'   => trim($_POST['user'] ?? 'root'),
            'pass'   => (string)($_POST['pass'] ?? ''),
        ];
        if ($row['key'] === '' || $row['dbname'] === '') {
            flash('Cần nhập Key và Tên DB.');
        } else {
            $found = false;
            foreach ($list as &$s) {
                if ($s['key'] === $orig) { $s = $row; $found = true; break; }
            }
            unset($s);
            // trùng key mới với máy chủ khác?
            $dup = false;
            foreach ($list as $s) { if ($s['key'] === $row['key'] && $s['key'] !== $orig) $dup = true; }
            if (!$found) {
                foreach ($list as $s) { if ($s['key'] === $row['key']) $dup = true; }
                if (!$dup) $list[] = $row;
            }
            if ($dup) flash('Key đã tồn tại, chọn key khác.');
            elseif (admin_save_servers($list)) flash('Đã lưu máy chủ.');
            else flash('Lỗi ghi file cấu hình (kiểm tra quyền ghi).');
        }
    } elseif ($do === 'delete') {
        $k = trim($_POST['key'] ?? '');
        if (count($list) <= 1) { flash('Phải giữ ít nhất 1 máy chủ.'); }
        else {
            $list = array_values(array_filter($list, fn($s) => $s['key'] !== $k));
            if (admin_save_servers($list)) {
                if (($_SESSION['admin_sv'] ?? '') === $k) unset($_SESSION['admin_sv']);
                flash('Đã xoá máy chủ ' . $k . '.');
            }
        }
    } elseif ($do === 'test') {
        $k = trim($_POST['key'] ?? '');
        $s = admin_server_by_key($k);
        if ($s) {
            $t = @new mysqli($s['host'], $s['user'], $s['pass'], $s['dbname']);
            $msg = $t->connect_error ? ('❌ ' . $s['name'] . ': ' . $t->connect_error)
                                     : ('✅ ' . $s['name'] . ': kết nối OK');
            if (!$t->connect_error) $t->close();
        }
    }
    if ($do !== 'test') { header('Location: manage_servers.php'); exit(); }
}

$servers = admin_servers();
$edit = null;
if (isset($_GET['edit'])) $edit = admin_server_by_key((string)$_GET['edit']);
$isNew = isset($_GET['new']);

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Máy chủ quản lý</h1>
<p class="dim">Mỗi máy chủ = 1 kết nối DB riêng. Admin chọn máy chủ ở góc phải header rồi mọi trang sẽ thao tác trên máy chủ đó. Mật khẩu lưu trong <code>servers.config.php</code> (file PHP, không lộ qua URL).</p>

<?php if ($msg): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>

<?php if ($edit || $isNew): $r = $edit ?: ['key'=>'','name'=>'','host'=>'localhost','dbname'=>'','user'=>'root','pass'=>'']; ?>
    <div class="box">
        <h2><?= $isNew ? 'Thêm máy chủ' : ('Sửa máy chủ ' . e($r['key'])) ?></h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($tok) ?>">
            <input type="hidden" name="do" value="save">
            <input type="hidden" name="orig_key" value="<?= e($r['key']) ?>">
            <div class="row2">
                <div><label>Key (định danh)</label><input type="text" name="key" value="<?= e($r['key']) ?>" placeholder="sv2" required></div>
                <div><label>Tên hiển thị</label><input type="text" name="name" value="<?= e($r['name']) ?>" placeholder="Server 2" required></div>
            </div>
            <div class="row2">
                <div><label>DB Host</label><input type="text" name="host" value="<?= e($r['host']) ?>" placeholder="localhost"></div>
                <div><label>Tên DB</label><input type="text" name="dbname" value="<?= e($r['dbname']) ?>" placeholder="team2026_sv2" required></div>
            </div>
            <div class="row2">
                <div><label>DB User</label><input type="text" name="user" value="<?= e($r['user']) ?>" placeholder="root"></div>
                <div><label>DB Password</label><input type="text" name="pass" value="<?= e($r['pass']) ?>" placeholder="(trống nếu không có)"></div>
            </div>
            <button type="submit">Lưu</button>
            <a class="btn" href="manage_servers.php">Huỷ</a>
        </form>
    </div>
<?php else: ?>
    <p><a class="btn ok" href="manage_servers.php?new=1">＋ Thêm máy chủ</a></p>
    <div class="tablewrap">
    <table>
    <thead><tr><th>Key</th><th>Tên</th><th>Host</th><th>DB</th><th>User</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php foreach ($servers as $s): ?>
        <tr>
            <td class="mono"><?= e($s['key']) ?></td>
            <td><b><?= e($s['name']) ?></b></td>
            <td class="dim"><?= e($s['host']) ?></td>
            <td class="mono"><?= e($s['dbname']) ?></td>
            <td class="dim"><?= e($s['user']) ?></td>
            <td class="actions">
                <a class="btn" href="manage_servers.php?edit=<?= urlencode($s['key']) ?>">Sửa</a>
                <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="test"><input type="hidden" name="key" value="<?= e($s['key']) ?>">
                    <button class="btn">Test</button>
                </form>
                <form method="post" onsubmit="return confirm('Xoá máy chủ này khỏi danh sách quản lý?')">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="delete"><input type="hidden" name="key" value="<?= e($s['key']) ?>">
                    <button class="btn danger">Xoá</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <p class="dim">Lưu ý: đây là kết nối DB để QUẢN LÝ. Máy chủ hiển thị cho người chơi (server list) quản lý ở trang <a href="servers.php">Máy chủ</a>.</p>
<?php endif; ?>
<?php require_once __DIR__ . '/footer.php'; ?>
