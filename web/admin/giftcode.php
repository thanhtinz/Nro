<?php
$__active = 'giftcode';
$__title  = 'Giftcode';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

/**
 * detail giftcode có định dạng JSON:
 * [{"id":<itemId>,"quantity":<sl>,"options":[{"id":<optId>,"param":<val>}]}, ...]
 * Hàm dựng JSON an toàn từ các mảng input.
 */
function build_detail(array $ids, array $qtys, array $optIds, array $optParams): string
{
    $items = [];
    foreach ($ids as $i => $rawId) {
        $id = (int)$rawId;
        $qty = (int)($qtys[$i] ?? 0);
        if ($id <= 0 || $qty <= 0) continue;
        $options = [];
        $oId = isset($optIds[$i]) && $optIds[$i] !== '' ? (int)$optIds[$i] : null;
        $oPar = isset($optParams[$i]) ? (int)$optParams[$i] : 0;
        if ($oId !== null) {
            $options[] = ['id' => $oId, 'param' => $oPar];
        }
        $items[] = ['id' => $id, 'quantity' => $qty, 'options' => $options];
    }
    return json_encode($items, JSON_UNESCAPED_UNICODE);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $code    = trim($_POST['code'] ?? '');
        $count   = (int)($_POST['count_left'] ?? 0);
        $expired = trim($_POST['expired'] ?? '');
        $ids     = $_POST['item_id']     ?? [];
        $qtys    = $_POST['item_qty']    ?? [];
        $optIds  = $_POST['opt_id']      ?? [];
        $optPars = $_POST['opt_param']   ?? [];

        // Cho phép nhập detail JSON thủ công (ưu tiên nếu hợp lệ)
        $manual = trim($_POST['detail_json'] ?? '');
        if ($manual !== '') {
            json_decode($manual);
            $detail = json_last_error() === JSON_ERROR_NONE ? $manual : '[]';
            if ($detail === '[]' && $manual !== '[]') {
                flash('JSON phần thưởng không hợp lệ.');
                header('Location: giftcode.php'); exit();
            }
        } else {
            $detail = build_detail((array)$ids, (array)$qtys, (array)$optIds, (array)$optPars);
        }

        if ($code === '') { flash('Vui lòng nhập code.'); header('Location: giftcode.php'); exit(); }
        if ($expired === '') $expired = '2037-12-31 17:00:00';

        if ($action === 'create') {
            $stmt = $c->prepare('INSERT INTO giftcode (code, count_left, detail, expired) VALUES (?,?,?,?)');
            $stmt->bind_param('siss', $code, $count, $detail, $expired);
            $stmt->execute(); $stmt->close();
            flash('Đã tạo giftcode "' . $code . '".');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $c->prepare('UPDATE giftcode SET code=?, count_left=?, detail=?, expired=? WHERE id=?');
            $stmt->bind_param('sissi', $code, $count, $detail, $expired, $id);
            $stmt->execute(); $stmt->close();
            flash('Đã cập nhật giftcode #' . $id . '.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $c->prepare('DELETE FROM giftcode WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        flash('Đã xoá giftcode #' . $id . '.');
    }
    header('Location: giftcode.php'); exit();
}

// Danh sách giftcode
$rows = $c->query('SELECT id, code, count_left, detail, datecreate, expired FROM giftcode ORDER BY id DESC LIMIT 200')
          ->fetch_all(MYSQLI_ASSOC);

// Tra cứu vật phẩm (giúp chọn id)
$itemQ = trim($_GET['item'] ?? '');
$items = [];
if ($itemQ !== '') {
    $like = '%' . $itemQ . '%';
    $iq = ctype_digit($itemQ) ? (int)$itemQ : 0;
    $stmt = $c->prepare('SELECT id, NAME, gender FROM item_template WHERE NAME LIKE ? OR id = ? ORDER BY id LIMIT 40');
    $stmt->bind_param('si', $like, $iq);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

require_once __DIR__ . '/header.php';
$tok = csrf_token();
$genders = [0 => 'TĐ', 1 => 'Namek', 2 => 'Xayda', -1 => 'Chung'];
?>
<h1>Quản lý Giftcode</h1>

<div class="grid2">
  <div class="box">
    <h2>Tạo giftcode mới</h2>
    <form method="post" id="gcform">
        <input type="hidden" name="csrf" value="<?= e($tok) ?>">
        <input type="hidden" name="action" value="create">
        <label>Code</label>
        <input type="text" name="code" required placeholder="vd: tanthu2026">
        <div class="row2">
            <div><label>Số lượt dùng</label><input type="number" name="count_left" value="100" min="0"></div>
            <div><label>Hết hạn</label><input type="text" name="expired" placeholder="2037-12-31 17:00:00"></div>
        </div>

        <label>Phần thưởng (vật phẩm)</label>
        <table class="mini" id="itemRows">
            <thead><tr><th>Item ID</th><th>SL</th><th>Option ID</th><th>Param</th><th></th></tr></thead>
            <tbody>
                <tr>
                    <td><input type="number" name="item_id[]" min="0" placeholder="457"></td>
                    <td><input type="number" name="item_qty[]" min="0" placeholder="1"></td>
                    <td><input type="number" name="opt_id[]" placeholder="(trống)"></td>
                    <td><input type="number" name="opt_param[]" placeholder="0"></td>
                    <td><button type="button" class="btn small" onclick="addRow()">＋</button></td>
                </tr>
            </tbody>
        </table>
        <details class="adv">
            <summary>Hoặc dán JSON phần thưởng thủ công</summary>
            <textarea name="detail_json" rows="3" placeholder='[{"id":457,"quantity":50,"options":[{"id":30,"param":0}]}]'></textarea>
            <p class="dim">Nếu điền ô này, hệ thống dùng JSON này thay cho bảng trên.</p>
        </details>
        <button type="submit">Tạo giftcode</button>
    </form>
  </div>

  <div class="box">
    <h2>Tra cứu Item ID</h2>
    <form method="get" class="search">
        <input type="text" name="item" value="<?= e($itemQ) ?>" placeholder="Tên hoặc ID vật phẩm...">
        <button>Tìm</button>
    </form>
    <?php if ($itemQ !== ''): ?>
        <div class="tablewrap">
        <table>
        <thead><tr><th>ID</th><th>Tên</th><th>Hành tinh</th></tr></thead>
        <tbody>
        <?php if (!$items): ?><tr><td colspan="3" class="empty">Không thấy.</td></tr>
        <?php else: foreach ($items as $it): ?>
            <tr><td><?= (int)$it['id'] ?></td><td><?= e($it['NAME']) ?></td>
                <td><?= e($genders[(int)$it['gender']] ?? '?') ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody></table>
        </div>
    <?php else: ?>
        <p class="dim">Nhập tên/ID để tra Item ID dùng cho phần thưởng.</p>
    <?php endif; ?>
  </div>
</div>

<h2>Danh sách giftcode (<?= count($rows) ?>)</h2>
<div class="tablewrap">
<table>
<thead><tr><th>ID</th><th>Code</th><th>Còn lại</th><th>Phần thưởng</th><th>Hết hạn</th><th>Thao tác</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="6" class="empty">Chưa có giftcode.</td></tr>
<?php else: foreach ($rows as $r): ?>
    <tr>
        <td><?= (int)$r['id'] ?></td>
        <td class="mono"><?= e($r['code']) ?></td>
        <td><?= (int)$r['count_left'] ?></td>
        <td class="detail"><code><?= e(mb_strimwidth($r['detail'], 0, 70, '…')) ?></code></td>
        <td class="dim"><?= e($r['expired']) ?></td>
        <td class="actions">
            <form method="post" onsubmit="return confirm('Xoá giftcode này?')">
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
<p class="dim">Phần thưởng lưu dạng JSON <code>[{"id":itemId,"quantity":sl,"options":[...]}]</code>. Server tự phát khi người chơi nhập code trong game. Muốn cấp riêng cho 1 người: đặt số lượt = 1 và đưa code cho họ.</p>

<script>
function addRow(){
    var tb = document.querySelector('#itemRows tbody');
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><input type="number" name="item_id[]" min="0"></td>'+
        '<td><input type="number" name="item_qty[]" min="0"></td>'+
        '<td><input type="number" name="opt_id[]" placeholder="(trống)"></td>'+
        '<td><input type="number" name="opt_param[]" placeholder="0"></td>'+
        '<td><button type="button" class="btn small" onclick="this.closest(\'tr\').remove()">✕</button></td>';
    tb.appendChild(tr);
}
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
