<?php
$__active = 'server';
$__title  = 'Điều khiển server';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

function bridge_ready(mysqli $c): bool
{
    $r = $c->query("SELECT 1 FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_name = 'server_config' LIMIT 1");
    return $r && $r->num_rows > 0;
}
$ready = bridge_ready($c);

/** Ghi 1 khoá cấu hình (server sẽ tự đọc & áp dụng) */
function set_cfg(mysqli $c, string $key, string $val): void
{
    $stmt = $c->prepare(
        'INSERT INTO server_config (cfg_key, cfg_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE cfg_value = VALUES(cfg_value)'
    );
    $stmt->bind_param('ss', $key, $val);
    $stmt->execute(); $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ready) {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'save_settings') {
        $exp = (string)max(1, min(127, (int)($_POST['rate_exp'] ?? 1)));
        $mt  = ($_POST['maintenance'] ?? '') === '1' ? '1' : '0';
        set_cfg($c, 'rate_exp', $exp);
        set_cfg($c, 'maintenance', $mt);
        flash('Đã lưu cấu hình. Server tự áp dụng trong ít giây.');
    } elseif ($do === 'notify') {
        $text = trim($_POST['notify_text'] ?? '');
        if ($text !== '') {
            set_cfg($c, 'notify_text', $text);
            set_cfg($c, 'notify_seq', (string)time()); // đổi seq -> server gửi 1 lần
            flash('Đã đặt thông báo. Server sẽ gửi tới người chơi trong ít giây.');
        }
    } elseif (in_array($do, ['do_reset_boss','do_reset_rank','do_restart'], true)) {
        set_cfg($c, $do, (string)time()); // đổi giá trị -> server chạy 1 lần
        flash('Đã yêu cầu: ' . $do . '. Server sẽ thực hiện trong ít giây.');
    }
    header('Location: server.php'); exit();
}

// Đọc cấu hình + trạng thái
$cfg = []; $status = [];
if ($ready) {
    $r = $c->query('SELECT cfg_key, cfg_value FROM server_config');
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $row) $cfg[$row['cfg_key']] = $row['cfg_value'];
    $r = $c->query('SELECT sv_key, sv_value FROM server_status');
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $row) $status[$row['sv_key']] = $row['sv_value'];
}
$hb = (int)($status['last_heartbeat'] ?? 0);
$svOnline = $hb > 0 && (time() - $hb) < 30;
$uptime = (int)($status['uptime'] ?? 0);
$uptimeStr = sprintf('%dh %dm', intdiv($uptime, 3600), intdiv($uptime % 3600, 60));

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Điều khiển server</h1>

<?php if (!$ready): ?>
    <div class="note" style="border-left-color:var(--danger)">
        Chưa cài cầu nối. Chạy <code>web/admin/sql/bridge.sql</code>, thêm
        <code>WebControlService.gI();</code> vào <code>ServerManager.init()</code>, build lại server.
        Xem <code>docs/PHASE2_SERVER_BRIDGE.md</code>.
    </div>
<?php else: ?>

    <div class="cards">
        <div class="card <?= $svOnline ? 'ok' : 'warn' ?>">
            <div class="num"><?= $svOnline ? '🟢 Online' : '🔴 Offline' ?></div>
            <div class="lbl">Server<?= $hb ? ' · nhịp ' . (time() - $hb) . 's trước' : '' ?></div>
        </div>
        <div class="card"><div class="num"><?= (int)($status['online_players'] ?? 0) ?></div><div class="lbl">Online</div></div>
        <div class="card"><div class="num">x<?= (int)($status['rate_exp'] ?? 1) ?></div><div class="lbl">EXP (thực tế)</div></div>
        <div class="card <?= (int)($status['maintenance'] ?? 0) ? 'warn' : '' ?>">
            <div class="num"><?= (int)($status['maintenance'] ?? 0) ? 'BẢO TRÌ' : 'Bình thường' ?></div><div class="lbl">Chế độ</div>
        </div>
        <div class="card"><div class="num"><?= e($uptimeStr) ?></div><div class="lbl">Uptime</div></div>
    </div>
    <?php if (!$svOnline): ?>
        <div class="note" style="border-left-color:var(--warn)">Server chưa gửi heartbeat — có thể đang tắt hoặc chưa gắn cầu nối. Chỉnh vẫn được lưu, server áp dụng khi chạy.</div>
    <?php endif; ?>

    <div class="grid2">
        <div class="box">
            <h2>Cấu hình (chỉnh là server tự áp dụng)</h2>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="do" value="save_settings">
                <label>Hệ số EXP (1–127)</label>
                <input type="number" name="rate_exp" min="1" max="127" value="<?= (int)($cfg['rate_exp'] ?? 1) ?>">
                <label style="margin-top:10px">Bảo trì</label>
                <select name="maintenance">
                    <option value="0" <?= (int)($cfg['maintenance'] ?? 0) === 0 ? 'selected' : '' ?>>Tắt (bình thường)</option>
                    <option value="1" <?= (int)($cfg['maintenance'] ?? 0) === 1 ? 'selected' : '' ?>>Bật bảo trì</option>
                </select>
                <button type="submit">Lưu cấu hình</button>
            </form>
        </div>

        <div class="box">
            <h2>Thông báo & Hành động</h2>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="do" value="notify">
                <label>Gửi thông báo in-game tới tất cả</label>
                <input type="text" name="notify_text" placeholder="Nội dung..." required>
                <button type="submit">Gửi thông báo</button>
            </form>
            <hr style="border-color:var(--line);margin:14px 0">
            <div class="actions">
                <form method="post" onsubmit="return confirm('Reset boss?')">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="do_reset_boss">
                    <button class="btn">🐉 Reset boss</button>
                </form>
                <form method="post" onsubmit="return confirm('RESET bảng xếp hạng? Không hoàn tác!')">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="do_reset_rank">
                    <button class="btn danger">🏆 Reset BXH</button>
                </form>
                <form method="post" onsubmit="return confirm('KHỞI ĐỘNG LẠI server?')">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="do_restart">
                    <button class="btn danger">♻️ Restart</button>
                </form>
            </div>
        </div>
    </div>
    <p class="dim">Server đọc bảng <code>server_config</code> mỗi ~3 giây và tự áp dụng. Bạn chỉ cần chỉnh giá trị & lưu — không cần "gửi lệnh". Bật/tắt sự kiện ở trang <a href="events.php">Sự kiện</a>.</p>
<?php endif; ?>
<?php require_once __DIR__ . '/footer.php'; ?>
