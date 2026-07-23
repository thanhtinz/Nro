<?php
$__active = 'schedule';
$__title  = 'Lịch hoạt động';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

// Hành động hợp lệ + nhãn + gợi ý tham số
$ACTIONS = [
    'notify'     => ['Gửi thông báo',       'Nội dung thông báo'],
    'reset_boss' => ['Reset boss',          '(không cần)'],
    'reset_rank' => ['Reset bảng xếp hạng', '(không cần)'],
    'event_on'   => ['Bật sự kiện',         'Tên sự kiện (vd CHRISTMAS)'],
    'event_off'  => ['Tắt sự kiện',         'Tên sự kiện (vd CHRISTMAS)'],
    'maintenance'=> ['Bật bảo trì',         '(không cần)'],
];
$EVENT_KEYS = ['LUNNAR_NEW_YEAR','INTERNATIONAL_WOMANS_DAY','CHRISTMAS','HALLOWEEN','HUNG_VUONG','TRUNG_THU','TOP_UP'];

function bridge_ready(mysqli $c): bool
{
    $r = $c->query("SELECT 1 FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_name = 'server_schedule' LIMIT 1");
    return $r && $r->num_rows > 0;
}
$ready = bridge_ready($c);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ready) {
    csrf_check();
    $act = $_POST['do'] ?? '';
    if ($act === 'save' || $act === 'create') {
        $time   = trim($_POST['run_time'] ?? '');
        $action = $_POST['action'] ?? '';
        $params = trim($_POST['params'] ?? '');
        $note   = trim($_POST['note'] ?? '');
        $enabled = ($_POST['enabled'] ?? '') === '1' ? 1 : 0;
        // validate HH:MM
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) || !isset($ACTIONS[$action])) {
            flash('Giờ (HH:MM) hoặc hành động không hợp lệ.');
        } else {
            if ($act === 'create') {
                $stmt = $c->prepare('INSERT INTO server_schedule (run_time, action, params, enabled, note) VALUES (?,?,?,?,?)');
                $stmt->bind_param('sssis', $time, $action, $params, $enabled, $note);
                $stmt->execute(); $stmt->close();
                flash('Đã thêm lịch.');
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $c->prepare('UPDATE server_schedule SET run_time=?, action=?, params=?, enabled=?, note=? WHERE id=?');
                $stmt->bind_param('sssisi', $time, $action, $params, $enabled, $note, $id);
                $stmt->execute(); $stmt->close();
                flash('Đã lưu lịch #' . $id . '.');
            }
        }
    } elseif ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $c->prepare('DELETE FROM server_schedule WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        flash('Đã xoá lịch #' . $id . '.');
    } elseif ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $c->prepare('UPDATE server_schedule SET enabled = 1 - enabled WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    }
    header('Location: schedule.php'); exit();
}

$editing = null;
if ($ready && isset($_GET['edit'])) {
    $stmt = $c->prepare('SELECT * FROM server_schedule WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $_GET['edit']); $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc(); $stmt->close();
}
$rows = $ready ? $c->query('SELECT * FROM server_schedule ORDER BY run_time')->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Lịch hoạt động</h1>

<?php if (!$ready): ?>
    <div class="note" style="border-left-color:var(--danger)">Chưa cài cầu nối. Chạy lại <code>web/admin/sql/bridge.sql</code> (đã thêm bảng lịch), xem <code>docs/PHASE2_SERVER_BRIDGE.md</code>.</div>
<?php else: ?>
    <div class="box">
        <h2><?= $editing ? 'Sửa lịch #' . (int)$editing['id'] : 'Thêm lịch mới' ?></h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($tok) ?>">
            <input type="hidden" name="do" value="<?= $editing ? 'save' : 'create' ?>">
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
            <div class="row2">
                <div><label>Giờ chạy (HH:MM, giờ VN)</label>
                    <input type="text" name="run_time" value="<?= e($editing['run_time'] ?? '20:00') ?>" placeholder="20:00" required></div>
                <div><label>Hành động</label>
                    <select name="action">
                        <?php foreach ($ACTIONS as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($editing['action'] ?? '')===$k?'selected':'' ?>><?= e($v[0]) ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
            <label>Tham số <span class="dim">(nội dung thông báo, hoặc tên sự kiện: <?= implode(', ', $EVENT_KEYS) ?>)</span></label>
            <input type="text" name="params" value="<?= e($editing['params'] ?? '') ?>" placeholder="tuỳ hành động">
            <label>Ghi chú</label>
            <input type="text" name="note" value="<?= e($editing['note'] ?? '') ?>" placeholder="mô tả ngắn">
            <label style="margin-top:8px"><input type="checkbox" name="enabled" value="1" <?= !$editing || (int)($editing['enabled']??1)===1 ? 'checked' : '' ?>> Bật lịch này</label>
            <div style="margin-top:12px">
                <button type="submit"><?= $editing ? 'Lưu' : 'Thêm lịch' ?></button>
                <?php if ($editing): ?><a class="btn" href="schedule.php">Huỷ</a><?php endif; ?>
            </div>
        </form>
    </div>

    <h2>Danh sách lịch (<?= count($rows) ?>)</h2>
    <div class="tablewrap">
    <table>
    <thead><tr><th>Giờ</th><th>Hành động</th><th>Tham số</th><th>Ghi chú</th><th>Bật</th><th>Chạy gần nhất</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="empty">Chưa có lịch. Thêm ở trên.</td></tr>
    <?php else: foreach ($rows as $r):
        $al = $ACTIONS[$r['action']][0] ?? $r['action']; ?>
        <tr>
            <td class="mono"><b><?= e($r['run_time']) ?></b></td>
            <td><?= e($al) ?></td>
            <td class="dim"><?= e(mb_strimwidth((string)$r['params'],0,30,'…')) ?></td>
            <td class="dim"><?= e($r['note']) ?></td>
            <td>
                <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn <?= (int)$r['enabled']?'ok':'' ?>"><?= (int)$r['enabled'] ? 'BẬT' : 'tắt' ?></button>
                </form>
            </td>
            <td class="dim"><?= e($r['last_run'] ?? '—') ?></td>
            <td class="actions">
                <a class="btn" href="schedule.php?edit=<?= (int)$r['id'] ?>">Sửa</a>
                <form method="post" onsubmit="return confirm('Xoá lịch này?')">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn danger">Xoá</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
    </div>
    <p class="dim">Server kiểm tra lịch mỗi ~3 giây và chạy đúng giờ (giờ VN), mỗi lịch chạy 1 lần/ngày. Bật/tắt sự kiện qua lịch sẽ cập nhật cấu hình sự kiện.</p>
<?php endif; ?>
<?php require_once __DIR__ . '/footer.php'; ?>
