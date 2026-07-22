<?php
$__active = 'events';
$__title  = 'Sự kiện';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

// Nhãn tiếng Việt cho từng sự kiện (khớp key trong EventManager)
$EVENTS = [
    'LUNNAR_NEW_YEAR'          => 'Tết Nguyên Đán',
    'INTERNATIONAL_WOMANS_DAY' => 'Quốc tế Phụ nữ 8/3',
    'CHRISTMAS'                => 'Giáng Sinh',
    'HALLOWEEN'                => 'Halloween',
    'HUNG_VUONG'               => 'Giỗ Tổ Hùng Vương',
    'TRUNG_THU'                => 'Trung Thu',
    'TOP_UP'                   => 'Sự kiện Nạp (Top Up)',
];

function bridge_ready(mysqli $c): bool
{
    $r = $c->query("SELECT 1 FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_name = 'server_control' LIMIT 1");
    return $r && $r->num_rows > 0;
}
$ready = bridge_ready($c);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ready) {
    csrf_check();
    $key = $_POST['key'] ?? '';
    $on  = ($_POST['on'] ?? '') === '1' ? '1' : '0';
    if (!isset($EVENTS[$key])) {
        flash('Sự kiện không hợp lệ.');
    } else {
        $params = $key . ':' . $on;
        $by = $_SESSION['admin_username'] ?? '';
        $cmd = 'event_toggle';
        $stmt = $c->prepare('INSERT INTO server_control (command, params, created_by) VALUES (?,?,?)');
        $stmt->bind_param('sss', $cmd, $params, $by);
        $stmt->execute(); $stmt->close();
        flash('Đã gửi lệnh ' . ($on === '1' ? 'BẬT' : 'TẮT') . ' "' . $EVENTS[$key] . '". Server sẽ áp dụng trong ít giây.');
    }
    header('Location: events.php'); exit();
}

// Đọc trạng thái sự kiện từ server_status.events ("KEY:1,KEY2:0,...")
$states = [];
$hb = 0;
if ($ready) {
    $r = $c->query("SELECT sv_key, sv_value FROM server_status WHERE sv_key IN ('events','last_heartbeat')");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $row) {
        if ($row['sv_key'] === 'last_heartbeat') $hb = (int)$row['sv_value'];
        if ($row['sv_key'] === 'events') {
            foreach (explode(',', (string)$row['sv_value']) as $pair) {
                $kv = explode(':', $pair);
                if (count($kv) === 2) $states[trim($kv[0])] = trim($kv[1]) === '1';
            }
        }
    }
}
$svOnline = $hb > 0 && (time() - $hb) < 30;

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Quản lý sự kiện in-game</h1>

<?php if (!$ready): ?>
    <div class="note" style="border-left-color:var(--danger)">
        Chưa cài cầu nối server. Xem <code>docs/PHASE2_SERVER_BRIDGE.md</code> (chạy <code>bridge.sql</code>,
        gắn <code>WebControlService.gI();</code>, build lại server).
    </div>
<?php else: ?>
    <?php if (!$svOnline): ?>
        <div class="note" style="border-left-color:var(--warn)">
            Server chưa gửi heartbeat gần đây — trạng thái bên dưới có thể cũ. Lệnh gửi đi sẽ chờ tới khi server chạy.
        </div>
    <?php endif; ?>

    <div class="tablewrap">
    <table>
    <thead><tr><th>Sự kiện</th><th>Trạng thái hiện tại</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php foreach ($EVENTS as $key => $label):
        $known = array_key_exists($key, $states);
        $isOn = $states[$key] ?? false;
    ?>
        <tr>
            <td><b><?= e($label) ?></b> <span class="dim mono"><?= e($key) ?></span></td>
            <td>
                <?php if (!$known): ?><span class="tag off">?</span>
                <?php elseif ($isOn): ?><span class="tag on">Đang BẬT</span>
                <?php else: ?><span class="tag ban">Đang TẮT</span><?php endif; ?>
            </td>
            <td class="actions">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                    <input type="hidden" name="key" value="<?= e($key) ?>">
                    <?php if ($isOn): ?>
                        <input type="hidden" name="on" value="0">
                        <button class="btn danger">Tắt</button>
                    <?php else: ?>
                        <input type="hidden" name="on" value="1">
                        <button class="btn ok">Bật</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <p class="dim">Trạng thái lấy trực tiếp từ server (heartbeat). Bật/tắt gửi lệnh vào hàng đợi, server áp dụng sau ~3 giây (áp dụng cho phiên chạy hiện tại; khởi động lại server sẽ về mặc định trong code).</p>
<?php endif; ?>
<?php require_once __DIR__ . '/footer.php'; ?>
