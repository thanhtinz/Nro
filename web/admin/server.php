<?php
$__active = 'server';
$__title  = 'Điều khiển server';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

// Kiểm tra bảng cầu nối tồn tại chưa
function bridge_ready(mysqli $c): bool
{
    $r = $c->query("SELECT 1 FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_name = 'server_control' LIMIT 1");
    return $r && $r->num_rows > 0;
}
$ready = bridge_ready($c);

// Danh sách lệnh hợp lệ (whitelist)
$CMDS = ['notify_all','set_exp','reset_boss','reset_rank','maintenance','restart'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ready) {
    csrf_check();
    $cmd = $_POST['command'] ?? '';
    $params = trim($_POST['params'] ?? '');
    if (!in_array($cmd, $CMDS, true)) {
        flash('Lệnh không hợp lệ.');
    } else {
        $by = $_SESSION['admin_username'] ?? '';
        $stmt = $c->prepare('INSERT INTO server_control (command, params, created_by) VALUES (?,?,?)');
        $stmt->bind_param('sss', $cmd, $params, $by);
        $stmt->execute(); $stmt->close();
        flash('Đã gửi lệnh "' . $cmd . '" tới server. Server sẽ xử lý trong ít giây.');
    }
    header('Location: server.php'); exit();
}

// Đọc trạng thái sống
$status = [];
if ($ready) {
    $r = $c->query('SELECT sv_key, sv_value, updated_at FROM server_status');
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $row) $status[$row['sv_key']] = $row;
}
$hb = (int)($status['last_heartbeat']['sv_value'] ?? 0);
$svOnline = $hb > 0 && (time() - $hb) < 30; // heartbeat trong 30s => server đang chạy bridge
$uptime = (int)($status['uptime']['sv_value'] ?? 0);
$uptimeStr = sprintf('%dh %dm', intdiv($uptime, 3600), intdiv($uptime % 3600, 60));

// Lịch sử lệnh
$history = [];
if ($ready) {
    $r = $c->query('SELECT id, command, params, status, result, created_by, created_at, processed_at
                      FROM server_control ORDER BY id DESC LIMIT 30');
    if ($r) $history = $r->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Điều khiển server</h1>

<?php if (!$ready): ?>
    <div class="note" style="border-left-color:var(--danger)">
        <b>Chưa cài cầu nối.</b> Hãy chạy file <code>web/admin/sql/bridge.sql</code> trên DB game,
        thêm <code>WebControlService.gI();</code> vào <code>ServerManager.init()</code> rồi build lại server.
        Xem hướng dẫn ở <code>docs/PHASE2_SERVER_BRIDGE.md</code>.
    </div>
<?php else: ?>

    <div class="cards">
        <div class="card <?= $svOnline ? 'ok' : 'warn' ?>">
            <div class="num"><?= $svOnline ? '🟢 Online' : '🔴 Offline' ?></div>
            <div class="lbl">Trạng thái server<?= $hb ? ' · nhịp ' . (time() - $hb) . 's trước' : '' ?></div>
        </div>
        <div class="card"><div class="num"><?= (int)($status['online_players']['sv_value'] ?? 0) ?></div><div class="lbl">Người chơi online</div></div>
        <div class="card"><div class="num">x<?= (int)($status['rate_exp']['sv_value'] ?? 1) ?></div><div class="lbl">Hệ số EXP</div></div>
        <div class="card <?= (int)($status['maintenance']['sv_value'] ?? 0) ? 'warn' : '' ?>">
            <div class="num"><?= (int)($status['maintenance']['sv_value'] ?? 0) ? 'BẢO TRÌ' : 'Bình thường' ?></div>
            <div class="lbl">Chế độ</div>
        </div>
        <div class="card"><div class="num"><?= e($uptimeStr) ?></div><div class="lbl">Uptime</div></div>
    </div>
    <?php if (!$svOnline): ?>
        <div class="note" style="border-left-color:var(--warn)">Server chưa gửi nhịp (heartbeat) gần đây — có thể server đang tắt hoặc chưa gắn cầu nối. Lệnh gửi đi sẽ chờ tới khi server chạy.</div>
    <?php endif; ?>

    <div class="grid2">
        <div class="box">
            <h2>Thông báo / EXP</h2>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="command" value="notify_all">
                <label>Gửi thông báo tới TẤT CẢ người chơi (in-game)</label>
                <input type="text" name="params" placeholder="Nội dung thông báo..." required>
                <button type="submit">Gửi thông báo</button>
            </form>
            <hr style="border-color:var(--line);margin:16px 0">
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="command" value="set_exp">
                <label>Đặt hệ số EXP server (1–127)</label>
                <input type="number" name="params" min="1" max="127" value="<?= (int)($status['rate_exp']['sv_value'] ?? 1) ?>" required>
                <button type="submit">Cập nhật EXP</button>
            </form>
        </div>

        <div class="box">
            <h2>Vận hành</h2>
            <form method="post" onsubmit="return confirm('Reset (nạp lại) toàn bộ boss?')">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="command" value="reset_boss">
                <button type="submit" class="btn">🐉 Reset boss</button>
            </form>
            <form method="post" onsubmit="return confirm('RESET bảng xếp hạng? Không hoàn tác!')" style="margin-top:10px">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="command" value="reset_rank">
                <button type="submit" class="btn danger">🏆 Reset bảng xếp hạng</button>
            </form>
            <form method="post" onsubmit="return confirm('BẬT BẢO TRÌ? Server sẽ đóng sau khi đếm ngược.')" style="margin-top:10px">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="command" value="maintenance">
                <input type="number" name="params" value="60" min="0" style="width:90px" title="giây đếm ngược">
                <button type="submit" class="btn danger">🔧 Bật bảo trì (giây)</button>
            </form>
            <form method="post" onsubmit="return confirm('KHỞI ĐỘNG LẠI server ngay?')" style="margin-top:10px">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="command" value="restart">
                <button type="submit" class="btn danger">♻️ Restart server</button>
            </form>
        </div>
    </div>

    <h2>Lịch sử lệnh</h2>
    <div class="tablewrap">
    <table>
    <thead><tr><th>ID</th><th>Lệnh</th><th>Tham số</th><th>Trạng thái</th><th>Kết quả</th><th>Admin</th><th>Lúc</th></tr></thead>
    <tbody>
    <?php if (!$history): ?><tr><td colspan="7" class="empty">Chưa có lệnh nào.</td></tr>
    <?php else: foreach ($history as $h):
        $st = (int)$h['status'];
        $stTag = $st === 1 ? ['on','Xong'] : ($st === 0 ? ['off','Chờ'] : ['ban','Lỗi']);
    ?>
        <tr>
            <td><?= (int)$h['id'] ?></td>
            <td class="mono"><?= e($h['command']) ?></td>
            <td class="dim"><?= e(mb_strimwidth((string)$h['params'], 0, 30, '…')) ?></td>
            <td><span class="tag <?= $stTag[0] ?>"><?= $stTag[1] ?></span></td>
            <td class="dim"><?= e(mb_strimwidth((string)$h['result'], 0, 40, '…')) ?></td>
            <td class="dim"><?= e($h['created_by']) ?></td>
            <td class="dim"><?= e($h['created_at']) ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
    </div>
    <p class="dim">Lệnh được đưa vào hàng đợi; server đọc & xử lý mỗi ~3 giây rồi ghi kết quả. Trạng thái ở trên lấy trực tiếp từ server (heartbeat).</p>
<?php endif; ?>
<?php require_once __DIR__ . '/footer.php'; ?>
