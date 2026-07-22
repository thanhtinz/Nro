<?php
$__active = 'events';
$__title  = 'Sự kiện';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

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
                     WHERE table_schema = DATABASE() AND table_name = 'server_config' LIMIT 1");
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
        $cfgKey = 'event_' . $key;
        $stmt = $c->prepare('INSERT INTO server_config (cfg_key, cfg_value) VALUES (?, ?)
                             ON DUPLICATE KEY UPDATE cfg_value = VALUES(cfg_value)');
        $stmt->bind_param('ss', $cfgKey, $on);
        $stmt->execute(); $stmt->close();
        flash('Đã ' . ($on === '1' ? 'BẬT' : 'TẮT') . ' "' . $EVENTS[$key] . '". Server tự áp dụng trong ít giây.');
    }
    header('Location: events.php'); exit();
}

// Trạng thái mong muốn (config) + trạng thái thực tế từ server (status)
$cfg = []; $live = []; $hb = 0;
if ($ready) {
    $r = $c->query("SELECT cfg_key, cfg_value FROM server_config WHERE cfg_key LIKE 'event_%'");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $row) $cfg[substr($row['cfg_key'], 6)] = $row['cfg_value'] === '1';
    $r = $c->query("SELECT sv_key, sv_value FROM server_status WHERE sv_key IN ('events','last_heartbeat')");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $row) {
        if ($row['sv_key'] === 'last_heartbeat') $hb = (int)$row['sv_value'];
        if ($row['sv_key'] === 'events') {
            foreach (explode(',', (string)$row['sv_value']) as $pair) {
                $kv = explode(':', $pair);
                if (count($kv) === 2) $live[trim($kv[0])] = trim($kv[1]) === '1';
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
    <div class="note" style="border-left-color:var(--danger)">Chưa cài cầu nối. Xem <code>docs/PHASE2_SERVER_BRIDGE.md</code>.</div>
<?php else: ?>
    <?php if (!$svOnline): ?>
        <div class="note" style="border-left-color:var(--warn)">Server chưa gửi heartbeat — cột "Thực tế" có thể trống. Chỉnh vẫn lưu, server áp dụng khi chạy.</div>
    <?php endif; ?>
    <div class="tablewrap">
    <table>
    <thead><tr><th>Sự kiện</th><th>Cài đặt</th><th>Thực tế (từ sv)</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php foreach ($EVENTS as $key => $label):
        $want = $cfg[$key] ?? true;
        $liveOn = $live[$key] ?? null;
    ?>
        <tr>
            <td><b><?= e($label) ?></b> <span class="dim mono"><?= e($key) ?></span></td>
            <td><?= $want ? '<span class="tag on">BẬT</span>' : '<span class="tag ban">TẮT</span>' ?></td>
            <td>
                <?php if ($liveOn === null): ?><span class="dim">—</span>
                <?php elseif ($liveOn): ?><span class="tag on">Đang BẬT</span>
                <?php else: ?><span class="tag ban">Đang TẮT</span><?php endif; ?>
            </td>
            <td class="actions">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                    <input type="hidden" name="key" value="<?= e($key) ?>">
                    <?php if ($want): ?>
                        <input type="hidden" name="on" value="0"><button class="btn danger">Tắt</button>
                    <?php else: ?>
                        <input type="hidden" name="on" value="1"><button class="btn ok">Bật</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <p class="dim">"Cài đặt" là trạng thái bạn chọn (lưu ở <code>server_config</code>); "Thực tế" là trạng thái server đang chạy (heartbeat). Server tự đồng bộ trong ít giây.</p>
<?php endif; ?>
<?php require_once __DIR__ . '/footer.php'; ?>
