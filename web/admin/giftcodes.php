<?php
$__active = 'giftcodes';
$__title  = 'Giftcode';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib_game.php';
require_admin();
$c = db();

/**
 * Dựng chuỗi JSON `detail` của giftcode từ các dòng vật phẩm người dùng nhập.
 * Nhận 3 mảng song song: ids[], qty[], opts[] (opts dạng "30:0,7:5").
 * @return array{0:?string,1:?string} [json|null, err|null]
 */
function build_giftcode_detail(array $ids, array $qtys, array $opts): array
{
    $items = [];
    $n = max(count($ids), count($qtys));
    for ($i = 0; $i < $n; $i++) {
        $id = (int)($ids[$i] ?? 0);
        $qty = (int)($qtys[$i] ?? 0);
        if ($id <= 0 && $qty <= 0) continue; // dòng trống -> bỏ qua
        if ($id <= 0 || $qty <= 0) {
            return [null, 'Mỗi vật phẩm cần ID > 0 và số lượng > 0.'];
        }
        $optPairs = parse_options_str((string)($opts[$i] ?? ''));
        $optionsArr = [];
        foreach ($optPairs as $p) {
            $optionsArr[] = ['id' => $p[0], 'param' => $p[1]];
        }
        $items[] = ['id' => $id, 'quantity' => $qty, 'options' => $optionsArr];
    }
    if (!$items) {
        return [null, 'Giftcode phải có ít nhất 1 vật phẩm.'];
    }
    return [json_encode($items, JSON_UNESCAPED_UNICODE), null];
}

/** Chuẩn hoá timestamp 'YYYY-MM-DDTHH:MM' -> 'YYYY-MM-DD HH:MM:00'. */
function normalize_expired(string $s): string
{
    $s = trim($s);
    if ($s === '') return '2037-12-31 17:00:00';
    $s = str_replace('T', ' ', $s);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $s)) {
        $s .= ':00';
    }
    return $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $code    = trim((string)($_POST['code'] ?? ''));
        $count   = (int)($_POST['count_left'] ?? 0);
        $expired = normalize_expired((string)($_POST['expired'] ?? ''));
        [$detail, $err] = build_giftcode_detail(
            $_POST['item_id'] ?? [],
            $_POST['item_qty'] ?? [],
            $_POST['item_opt'] ?? []
        );

        if ($code === '') {
            flash('Mã giftcode không được để trống.');
        } elseif ($err !== null) {
            flash($err);
        } elseif ($action === 'create') {
            $stmt = $c->prepare('INSERT INTO giftcode (code, count_left, detail, expired) VALUES (?,?,?,?)');
            $stmt->bind_param('siss', $code, $count, $detail, $expired);
            $stmt->execute(); $stmt->close();
            flash("Đã tạo giftcode \"$code\".");
        } else { // update
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $c->prepare('UPDATE giftcode SET code=?, count_left=?, detail=?, expired=? WHERE id=?');
            $stmt->bind_param('sissi', $code, $count, $detail, $expired, $id);
            $stmt->execute(); $stmt->close();
            flash("Đã cập nhật giftcode #$id.");
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $c->prepare('DELETE FROM giftcode WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute(); $ok = $stmt->affected_rows; $stmt->close();
        flash($ok ? "Đã xoá giftcode #$id." : "Không tìm thấy giftcode #$id.");
    }

    header('Location: giftcodes.php');
    exit();
}

// ---- Nạp dữ liệu để hiển thị ----
$rows = $c->query('SELECT id, code, count_left, detail, datecreate, expired FROM giftcode ORDER BY id DESC')
    ->fetch_all(MYSQLI_ASSOC);

// Nếu đang sửa 1 giftcode -> nạp sẵn để đổ vào form
$edit = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    foreach ($rows as $r) {
        if ((int)$r['id'] === $editId) { $edit = $r; break; }
    }
}

/** Diễn giải detail JSON thành các dòng [id, name, qty, optStr] để hiển thị/sửa. */
function decode_detail(mysqli $c, ?string $json): array
{
    $arr = json_decode((string)$json, true);
    if (!is_array($arr)) return [];
    $out = [];
    foreach ($arr as $it) {
        if (!is_array($it)) continue;
        $id  = (int)($it['id'] ?? 0);
        $qty = (int)($it['quantity'] ?? 0);
        $opt = '';
        if (!empty($it['options']) && is_array($it['options'])) {
            $parts = [];
            foreach ($it['options'] as $o) {
                $parts[] = (int)($o['id'] ?? 0) . ':' . (int)($o['param'] ?? 0);
            }
            $opt = implode(',', $parts);
        }
        $out[] = ['id' => $id, 'name' => game_item_name($c, $id), 'qty' => $qty, 'opt' => $opt];
    }
    return $out;
}

$editRows = $edit ? decode_detail($c, $edit['detail']) : [];

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Quản lý Giftcode</h1>

<section class="gc-form">
    <h2><?= $edit ? 'Sửa giftcode #' . (int)$edit['id'] : 'Tạo giftcode mới' ?></h2>
    <form method="post" id="gcForm">
        <input type="hidden" name="csrf" value="<?= e($tok) ?>">
        <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <div class="grid3">
            <label>Mã code
                <input type="text" name="code" required value="<?= e($edit['code'] ?? '') ?>" placeholder="vd: tanthu">
            </label>
            <label>Số lượt còn lại
                <input type="number" name="count_left" step="1" value="<?= (int)($edit['count_left'] ?? 100) ?>">
                <span class="hint">-1 = không giới hạn</span>
            </label>
            <label>Hết hạn
                <input type="datetime-local" name="expired"
                       value="<?= e($edit ? str_replace(' ', 'T', substr((string)$edit['expired'], 0, 16)) : '') ?>">
            </label>
        </div>

        <h3>Vật phẩm trong giftcode</h3>
        <table class="items" id="itemsTable">
            <thead><tr><th>ID vật phẩm</th><th>Tên</th><th>Số lượng</th><th>Options (id:param, id:param)</th><th></th></tr></thead>
            <tbody>
            <?php
            $seed = $editRows ?: [['id' => '', 'name' => '', 'qty' => '', 'opt' => '']];
            foreach ($seed as $it): ?>
                <tr>
                    <td><input type="number" name="item_id[]" value="<?= e((string)$it['id']) ?>" class="it-id" step="1"></td>
                    <td class="it-name dim"><?= e($it['name'] ?? '') ?></td>
                    <td><input type="number" name="item_qty[]" value="<?= e((string)$it['qty']) ?>" step="1" style="width:100px"></td>
                    <td><input type="text" name="item_opt[]" value="<?= e($it['opt']) ?>" placeholder="vd: 30:0"></td>
                    <td><button type="button" class="btn danger rm-row">✕</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" class="btn" id="addRow">+ Thêm vật phẩm</button>

        <div class="submit-row">
            <button type="submit"><?= $edit ? 'Lưu thay đổi' : 'Tạo giftcode' ?></button>
            <?php if ($edit): ?><a class="clear" href="giftcodes.php">Huỷ</a><?php endif; ?>
        </div>
    </form>
</section>

<h2>Danh sách giftcode</h2>
<div class="tablewrap">
<table>
<thead><tr><th>ID</th><th>Code</th><th>Còn lại</th><th>Vật phẩm</th><th>Tạo lúc</th><th>Hết hạn</th><th>Thao tác</th></tr></thead>
<tbody>
<?php if (!$rows): ?>
    <tr><td colspan="7" class="empty">Chưa có giftcode nào.</td></tr>
<?php else: foreach ($rows as $r):
    $items = decode_detail($c, $r['detail']);
    $summary = [];
    foreach ($items as $it) {
        $summary[] = ($it['name'] ?? ('#' . $it['id'])) . ' x' . $it['qty'];
    }
?>
    <tr>
        <td><?= (int)$r['id'] ?></td>
        <td class="mono"><b><?= e($r['code']) ?></b></td>
        <td><?= (int)$r['count_left'] === -1 ? '∞' : number_format((int)$r['count_left']) ?></td>
        <td class="dim" style="white-space:normal;max-width:320px"><?= e(implode(', ', $summary)) ?: '—' ?></td>
        <td class="dim"><?= e($r['datecreate']) ?></td>
        <td class="dim"><?= e($r['expired']) ?></td>
        <td class="actions">
            <a class="btn" href="giftcodes.php?edit=<?= (int)$r['id'] ?>">Sửa</a>
            <form method="post" onsubmit="return confirm('Xoá giftcode <?= e($r['code']) ?>?')">
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
<p class="dim">Lưu ý: server nạp danh sách giftcode lúc khởi động. Giftcode mới/sửa có thể cần khởi động lại server để áp dụng.</p>

<script>
(function () {
    const table = document.getElementById('itemsTable').querySelector('tbody');

    function lookupName(idInput, nameCell) {
        const id = parseInt(idInput.value, 10);
        if (isNaN(id) || id < 0) { nameCell.textContent = ''; return; }
        fetch('item_lookup.php?id=' + id)
            .then(r => r.json())
            .then(d => { nameCell.textContent = d.name || '❓ không tìm thấy'; })
            .catch(() => {});
    }

    function bindRow(tr) {
        const idInput = tr.querySelector('.it-id');
        const nameCell = tr.querySelector('.it-name');
        idInput.addEventListener('change', () => lookupName(idInput, nameCell));
        tr.querySelector('.rm-row').addEventListener('click', function () {
            if (table.rows.length > 1) tr.remove();
            else tr.querySelectorAll('input').forEach(i => i.value = '');
        });
    }
    table.querySelectorAll('tr').forEach(bindRow);

    document.getElementById('addRow').addEventListener('click', function () {
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="number" name="item_id[]" class="it-id" step="1"></td>' +
            '<td class="it-name dim"></td>' +
            '<td><input type="number" name="item_qty[]" step="1" style="width:100px"></td>' +
            '<td><input type="text" name="item_opt[]" placeholder="vd: 30:0"></td>' +
            '<td><button type="button" class="btn danger rm-row">✕</button></td>';
        table.appendChild(tr);
        bindRow(tr);
    });
})();
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
