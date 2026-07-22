<?php
$__active = 'players';
$__title  = 'Chi tiết nhân vật';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

// Các trường an toàn cho phép sửa (kiểu -> để bind)
$EDITABLE = [
    'name'        => 's',
    'head'        => 'i',
    'gender'      => 'i',
    'clan_id'     => 'i',
    'rank'        => 'i',
    'event_point' => 'i',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { flash('ID không hợp lệ.'); header('Location: players.php'); exit(); }

    if (($_POST['action'] ?? '') === 'save') {
        $sets = []; $types = ''; $vals = [];
        foreach ($EDITABLE as $col => $t) {
            if (!isset($_POST[$col])) continue;
            $sets[] = "`$col` = ?";
            $types .= $t;
            $vals[] = $t === 'i' ? (int)$_POST[$col] : trim((string)$_POST[$col]);
        }
        // Đặt lại nhiệm vụ chính (tuỳ chọn)
        if (isset($_POST['set_task']) && $_POST['set_task'] !== '') {
            $task = max(0, min(100, (int)$_POST['set_task']));
            $sets[] = "`data_task` = ?";
            $types .= 's';
            $vals[] = "[$task,0,0]";
        }
        if ($sets) {
            $types .= 'i'; $vals[] = $id;
            $sql = 'UPDATE player SET ' . implode(', ', $sets) . ' WHERE id = ?';
            $stmt = $c->prepare($sql);
            $stmt->bind_param($types, ...$vals);
            $stmt->execute(); $stmt->close();
            flash('Đã lưu nhân vật #' . $id . '.');
        }
    }
    header('Location: player_detail.php?id=' . $id); exit();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: players.php'); exit(); }

$stmt = $c->prepare('SELECT * FROM player WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id); $stmt->execute();
$p = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$p) {
    require_once __DIR__ . '/header.php';
    echo '<h1>Không tìm thấy nhân vật #' . (int)$id . '</h1><p><a href="players.php">← Quay lại</a></p>';
    require_once __DIR__ . '/footer.php';
    exit();
}

// Thông tin tài khoản chủ
$acc = null;
if (!empty($p['account_id'])) {
    $stmt = $c->prepare('SELECT id, username, ban, admin, is_admin, vnd, vang FROM account WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $p['account_id']); $stmt->execute();
    $acc = $stmt->get_result()->fetch_assoc(); $stmt->close();
}

require_once __DIR__ . '/header.php';
$tok = csrf_token();
$genders = [0 => 'Trái Đất', 1 => 'Namek', 2 => 'Xayda'];
?>
<p><a href="players.php">← Danh sách nhân vật</a></p>
<h1>Nhân vật: <?= e($p['name']) ?> <span class="dim">#<?= (int)$p['id'] ?></span></h1>

<?php if ($acc): ?>
<div class="note">
    Tài khoản chủ: <b><?= e($acc['username']) ?></b> (#<?= (int)$acc['id'] ?>)
    · VNĐ <?= number_format((int)$acc['vnd']) ?> · Vàng <?= number_format((int)$acc['vang']) ?>
    <?php if ((int)$acc['admin'] === 1 || (int)$acc['is_admin'] === 1): ?><span class="tag admin">ADMIN</span><?php endif; ?>
    <?php if ((int)$acc['ban'] === 1): ?><span class="tag ban">Khoá</span><?php endif; ?>
    · <a href="accounts.php?q=<?= urlencode($acc['username']) ?>">Quản lý tài khoản</a>
</div>
<?php endif; ?>

<div class="grid2">
  <div class="box">
    <h2>Sửa nhanh (trường an toàn)</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e($tok) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <label>Tên nhân vật</label>
        <input type="text" name="name" value="<?= e($p['name']) ?>" maxlength="20">
        <div class="row2">
            <div><label>Hành tinh</label>
                <select name="gender">
                    <?php foreach ($genders as $gv => $gn): ?>
                        <option value="<?= $gv ?>" <?= (int)$p['gender']===$gv?'selected':'' ?>><?= $gn ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Head (icon đầu)</label><input type="number" name="head" value="<?= (int)$p['head'] ?>"></div>
        </div>
        <div class="row2">
            <div><label>Clan ID (-1 = không)</label><input type="number" name="clan_id" value="<?= (int)$p['clan_id'] ?>"></div>
            <div><label>Rank</label><input type="number" name="rank" value="<?= (int)$p['rank'] ?>"></div>
        </div>
        <div class="row2">
            <div><label>Điểm sự kiện</label><input type="number" name="event_point" value="<?= (int)$p['event_point'] ?>"></div>
            <div><label>Đặt nhiệm vụ chính (0-100)</label><input type="number" name="set_task" placeholder="để trống = giữ nguyên" min="0" max="100"></div>
        </div>
        <button type="submit">Lưu thay đổi</button>
    </form>
    <p class="dim">Chỉ sửa các trường an toàn. Túi đồ, chỉ số, kỹ năng... lưu mã hoá riêng — chỉ xem bên phải.</p>
  </div>

  <div class="box">
    <h2>Toàn bộ dữ liệu (chỉ xem)</h2>
    <div class="tablewrap kv">
    <table>
    <tbody>
    <?php foreach ($p as $k => $v):
        $val = (string)$v;
        $long = strlen($val) > 60;
    ?>
        <tr>
            <th><?= e($k) ?></th>
            <td class="<?= $long ? 'mono dim' : '' ?>"><?= e($long ? mb_strimwidth($val, 0, 60, '…') : $val) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
