<?php
$__active = 'players';
$__title  = 'Chi tiết nhân vật';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib_game.php';
require_admin();
$c = db();

$genders = [0 => 'Trái Đất', 1 => 'Namek', 2 => 'Xayda'];

/** Lấy phần tử mảng JSON an toàn. */
function jget(array $a, int $i, $def = 0)
{
    return $a[$i] ?? $def;
}

// ---- Xử lý POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['player_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id <= 0) { flash('ID nhân vật không hợp lệ.'); header('Location: players.php'); exit(); }

    // Nạp bản ghi hiện tại
    $stmt = $c->prepare('SELECT data_inventory, data_point, items_bag, items_box FROM player WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $cur = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$cur) { flash("Không tìm thấy nhân vật #$id."); header('Location: players.php'); exit(); }

    if ($action === 'update_char') {
        $name    = trim((string)($_POST['name'] ?? ''));
        $head    = (int)($_POST['head'] ?? 0);
        $gender  = (int)($_POST['gender'] ?? 0);
        $clan_id = (int)($_POST['clan_id'] ?? -1);
        $rank    = (int)($_POST['rank'] ?? 0);

        // Tài nguyên (clamp theo giới hạn game)
        $gold    = max(0, min((int)($_POST['gold'] ?? 0), 2000000000));
        $gem     = max(0, min((int)($_POST['gem'] ?? 0), 200000000));
        $ruby    = max(0, min((int)($_POST['ruby'] ?? 0), 200000000));
        $power   = max(0, (int)($_POST['power'] ?? 0));
        $tiemNang = max(0, (int)($_POST['tiemnang'] ?? 0));

        if ($name === '') { flash('Tên nhân vật không được để trống.'); header('Location: player_edit.php?id=' . $id); exit(); }

        // Cập nhật data_inventory[0..2]
        $inv = json_decode((string)$cur['data_inventory'], true);
        if (is_array($inv)) {
            $inv[0] = $gold; $inv[1] = $gem; $inv[2] = $ruby;
            $invJson = json_encode($inv);
        } else {
            $invJson = $cur['data_inventory'];
            flash('Cảnh báo: data_inventory không hợp lệ, bỏ qua cập nhật tài nguyên.');
        }

        // Cập nhật data_point[1]=power, [2]=tiemNang
        $pt = json_decode((string)$cur['data_point'], true);
        if (is_array($pt) && count($pt) > 2) {
            $pt[1] = $power; $pt[2] = $tiemNang;
            $ptJson = json_encode($pt);
        } else {
            $ptJson = $cur['data_point'];
        }

        $stmt = $c->prepare('UPDATE player SET name=?, head=?, gender=?, clan_id=?, rank=?, data_inventory=?, data_point=? WHERE id=?');
        $stmt->bind_param('siiiissi', $name, $head, $gender, $clan_id, $rank, $invJson, $ptJson, $id);
        $stmt->execute(); $stmt->close();
        flash("Đã cập nhật nhân vật #$id.");
        header('Location: player_edit.php?id=' . $id); exit();
    }

    if ($action === 'grant_item') {
        $itemId  = (int)($_POST['grant_item_id'] ?? 0);
        $qty     = (int)($_POST['grant_qty'] ?? 1);
        $optPairs = parse_options_str((string)($_POST['grant_opt'] ?? ''));
        $target  = ($_POST['grant_target'] ?? 'bag') === 'box' ? 'items_box' : 'items_bag';

        if ($itemId < 0 || $qty <= 0) {
            flash('ID vật phẩm hoặc số lượng không hợp lệ.');
            header('Location: player_edit.php?id=' . $id); exit();
        }

        $slot = build_item_slot($itemId, $qty, $optPairs, now_ms());
        $err = null;
        $newJson = inventory_add_item((string)$cur[$target], $slot, $err);
        if ($newJson === null) {
            flash('Lỗi cấp vật phẩm: ' . $err);
            header('Location: player_edit.php?id=' . $id); exit();
        }

        // whitelist tên cột -> an toàn khi nội suy
        $col = $target === 'items_box' ? 'items_box' : 'items_bag';
        $stmt = $c->prepare("UPDATE player SET `$col`=? WHERE id=?");
        $stmt->bind_param('si', $newJson, $id);
        $stmt->execute(); $stmt->close();

        $nm = game_item_name($c, $itemId);
        flash("Đã cấp {$qty}x " . ($nm ? $nm : "vật phẩm #$itemId") . " vào " . ($target === 'items_box' ? 'rương' : 'túi') . " của nhân vật #$id.");
        header('Location: player_edit.php?id=' . $id); exit();
    }

    flash('Hành động không hợp lệ.');
    header('Location: player_edit.php?id=' . $id); exit();
}

// ---- Hiển thị (GET) ----
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash('Thiếu ID nhân vật.'); header('Location: players.php'); exit(); }

$stmt = $c->prepare(
    'SELECT p.*, a.username, a.ban
       FROM player p LEFT JOIN account a ON a.id = p.account_id
      WHERE p.id=? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$p) { flash("Không tìm thấy nhân vật #$id."); header('Location: players.php'); exit(); }

$inv = json_decode((string)$p['data_inventory'], true) ?: [];
$pt  = json_decode((string)$p['data_point'], true) ?: [];
$emptyBag = inventory_count_empty((string)$p['items_bag']);
$emptyBox = inventory_count_empty((string)$p['items_box']);
$task = null;
$td = json_decode((string)$p['data_task'], true);
if (is_array($td)) $task = $td[0] ?? null;

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Chi tiết nhân vật #<?= (int)$p['id'] ?>
    <?php if ((int)$p['ban'] === 1): ?><span class="tag ban">TK khoá</span><?php endif; ?>
</h1>
<p class="dim">
    Tài khoản: <?= $p['username'] !== null ? e($p['username']) : '—' ?>
    <span class="dim">#<?= (int)$p['account_id'] ?></span> ·
    Tạo lúc: <?= e($p['create_time']) ?> ·
    <a href="players.php">← Về danh sách</a>
</p>

<section class="gc-form">
    <h2>Thông tin &amp; chỉ số</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e($tok) ?>">
        <input type="hidden" name="action" value="update_char">
        <input type="hidden" name="player_id" value="<?= (int)$p['id'] ?>">
        <div class="grid3">
            <label>Tên nhân vật
                <input type="text" name="name" value="<?= e($p['name']) ?>" maxlength="20" required>
            </label>
            <label>Head (avatar id)
                <input type="number" name="head" value="<?= (int)$p['head'] ?>" step="1">
            </label>
            <label>Hành tinh
                <select name="gender">
                    <?php foreach ($genders as $gv => $gl): ?>
                        <option value="<?= $gv ?>" <?= (int)$p['gender'] === $gv ? 'selected' : '' ?>><?= e($gl) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Clan ID <span class="hint">(-1 = không có)</span>
                <input type="number" name="clan_id" value="<?= (int)$p['clan_id'] ?>" step="1">
            </label>
            <label>Rank
                <input type="number" name="rank" value="<?= (int)$p['rank'] ?>" step="1">
            </label>
            <label>Nhiệm vụ chính
                <input type="text" value="<?= $task !== null ? (int)$task : '—' ?>" disabled>
            </label>
        </div>

        <h3>Tài nguyên</h3>
        <div class="grid3">
            <label>Vàng <span class="hint">(tối đa 2 tỷ)</span>
                <input type="number" name="gold" value="<?= (int)jget($inv, 0) ?>" step="1">
            </label>
            <label>Ngọc <span class="hint">(tối đa 200tr)</span>
                <input type="number" name="gem" value="<?= (int)jget($inv, 1) ?>" step="1">
            </label>
            <label>Hồng ngọc <span class="hint">(tối đa 200tr)</span>
                <input type="number" name="ruby" value="<?= (int)jget($inv, 2) ?>" step="1">
            </label>
            <label>Sức mạnh
                <input type="number" name="power" value="<?= (int)jget($pt, 1) ?>" step="1">
            </label>
            <label>Tiềm năng
                <input type="number" name="tiemnang" value="<?= (int)jget($pt, 2) ?>" step="1">
            </label>
        </div>

        <div class="submit-row">
            <button type="submit">Lưu thay đổi</button>
        </div>
    </form>
</section>

<section class="gc-form">
    <h2>Cấp vật phẩm</h2>
    <p class="dim">Túi trống: <b><?= $emptyBag ?></b> ô · Rương trống: <b><?= $emptyBox ?></b> ô.
       Nên cấp khi nhân vật <b>đang offline</b> để tránh bị ghi đè khi lưu game.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e($tok) ?>">
        <input type="hidden" name="action" value="grant_item">
        <input type="hidden" name="player_id" value="<?= (int)$p['id'] ?>">
        <div class="grid3">
            <label>ID vật phẩm
                <input type="number" name="grant_item_id" step="1" required id="grantId">
                <span class="hint" id="grantName"></span>
            </label>
            <label>Số lượng
                <input type="number" name="grant_qty" step="1" value="1" min="1">
            </label>
            <label>Nơi nhận
                <select name="grant_target">
                    <option value="bag">Túi (hành trang)</option>
                    <option value="box">Rương (kho)</option>
                </select>
            </label>
            <label>Options <span class="hint">(id:param, id:param)</span>
                <input type="text" name="grant_opt" placeholder="vd: 30:0">
            </label>
        </div>
        <div class="submit-row">
            <button type="submit">Cấp vật phẩm</button>
        </div>
    </form>
</section>
<script>
(function () {
    const idInput = document.getElementById('grantId');
    const nameEl = document.getElementById('grantName');
    if (!idInput) return;
    idInput.addEventListener('change', function () {
        const id = parseInt(idInput.value, 10);
        if (isNaN(id) || id < 0) { nameEl.textContent = ''; return; }
        fetch('item_lookup.php?id=' + id)
            .then(r => r.json())
            .then(d => { nameEl.textContent = d.name || '❓ không tìm thấy vật phẩm'; })
            .catch(() => {});
    });
})();
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
