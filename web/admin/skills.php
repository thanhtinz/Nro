<?php
$__active = 'skills';
$__title  = 'Kỹ năng';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

$TABLE = 'skill_template';
// cột thật của bảng
function cols(mysqli $c, string $t): array {
    $stmt = $c->prepare('SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.columns
                          WHERE table_schema=DATABASE() AND table_name=? ORDER BY ORDINAL_POSITION');
    $stmt->bind_param('s', $t); $stmt->execute();
    $out = []; foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $out[$r['COLUMN_NAME']] = $r['DATA_TYPE'];
    $stmt->close(); return $out;
}
$COLS = cols($c, $TABLE);
$colNames = array_keys($COLS);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    $nclass = (int)($_POST['nclass_id'] ?? -1);
    $id = (int)($_POST['id'] ?? -1);
    $back = 'skills.php?class=' . $nclass;

    if ($do === 'delete') {
        $stmt = $c->prepare("DELETE FROM `$TABLE` WHERE nclass_id=? AND id=? LIMIT 1");
        $stmt->bind_param('ii', $nclass, $id); $stmt->execute(); $stmt->close();
        flash("Đã xoá kỹ năng ($nclass,$id).");
        header("Location: $back"); exit();
    }
    if ($do === 'save' || $do === 'create') {
        $data = $_POST['f'] ?? [];
        $use = array_intersect(array_keys($data), $colNames);
        try {
            if ($do === 'create') {
                $fields = array_values($use);
                $ph = implode(',', array_fill(0, count($fields), '?'));
                $cs = implode(',', array_map(fn($x)=>"`$x`", $fields));
                $stmt = $c->prepare("INSERT INTO `$TABLE` ($cs) VALUES ($ph)");
                $stmt->bind_param(str_repeat('s', count($fields)), ...array_map(fn($x)=>(string)$data[$x], $fields));
                $stmt->execute(); $stmt->close();
                flash('Đã thêm kỹ năng.');
            } else {
                $setCols = array_values(array_diff($use, ['nclass_id','id']));
                if ($setCols) {
                    $set = implode(',', array_map(fn($x)=>"`$x`=?", $setCols));
                    $stmt = $c->prepare("UPDATE `$TABLE` SET $set WHERE nclass_id=? AND id=?");
                    $vals = array_map(fn($x)=>(string)$data[$x], $setCols);
                    $vals[] = $nclass; $vals[] = $id;
                    $stmt->bind_param(str_repeat('s', count($setCols)) . 'ii', ...$vals);
                    $stmt->execute(); $stmt->close();
                }
                flash("Đã lưu kỹ năng ($nclass,$id).");
            }
        } catch (Throwable $ex) { flash('Lỗi: ' . $ex->getMessage()); }
        header("Location: $back"); exit();
    }
}

// danh sách class có sẵn
$classes = [];
$r = $c->query("SELECT DISTINCT nclass_id FROM `$TABLE` ORDER BY nclass_id");
if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $row) $classes[] = (int)$row['nclass_id'];
$CLASS_NAME = [0=>'Trái Đất', 1=>'Namek', 2=>'Xayda']; // theo nclass phổ biến

$class = isset($_GET['class']) ? (int)$_GET['class'] : ($classes[0] ?? 0);
$editing = null; $isNew = isset($_GET['new']);
if (isset($_GET['edit'])) {
    $stmt = $c->prepare("SELECT * FROM `$TABLE` WHERE nclass_id=? AND id=? LIMIT 1");
    $eid = (int)$_GET['edit'];
    $stmt->bind_param('ii', $class, $eid); $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc(); $stmt->close();
}
$stmt = $c->prepare("SELECT * FROM `$TABLE` WHERE nclass_id=? ORDER BY id");
$stmt->bind_param('i', $class); $stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Kỹ năng (skill_template)</h1>

<div class="dtabs" style="margin-bottom:14px">
    <?php foreach ($classes as $cl): ?>
        <a href="skills.php?class=<?= $cl ?>" class="dtab <?= $cl===$class?'on':'' ?>">Class <?= $cl ?><?= isset($CLASS_NAME[$cl]) ? ' · '.e($CLASS_NAME[$cl]) : '' ?></a>
    <?php endforeach; ?>
</div>

<?php if ($editing || $isNew):
    $rec = $editing ?: array_fill_keys($colNames, ''); if ($isNew) $rec['nclass_id'] = $class;
?>
    <div class="box">
        <h2><?= $isNew ? 'Thêm kỹ năng (class '.$class.')' : "Sửa kỹ năng ($class, ".(int)$editing['id'].')' ?></h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($tok) ?>">
            <input type="hidden" name="do" value="<?= $isNew?'create':'save' ?>">
            <input type="hidden" name="nclass_id" value="<?= $class ?>">
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
            <div class="fgrid">
            <?php foreach ($colNames as $cn):
                $type=$COLS[$cn]; $val=(string)($rec[$cn]??'');
                $ro = (($cn==='nclass_id') || ($cn==='id' && $editing));
                $long = in_array($type,['text','longtext','mediumtext']) || strlen($val)>60; ?>
                <div class="fitem <?= $long?'wide':'' ?>">
                    <label><?= e($cn) ?> <span class="dim">(<?= e($type) ?>)</span></label>
                    <?php if ($long): ?><textarea name="f[<?= e($cn) ?>]" rows="2" <?= $ro?'readonly':'' ?>><?= e($val) ?></textarea>
                    <?php else: ?><input type="text" name="f[<?= e($cn) ?>]" value="<?= e($val) ?>" <?= $ro?'readonly':'' ?>><?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
            <button type="submit"><?= $isNew?'Thêm':'Lưu' ?></button>
            <a class="btn" href="skills.php?class=<?= $class ?>">Huỷ</a>
        </form>
    </div>
<?php else: ?>
    <p><a class="btn ok" href="skills.php?class=<?= $class ?>&new=1">＋ Thêm kỹ năng</a></p>
    <div class="tablewrap">
    <table>
    <thead><tr><th>id</th><th>Tên</th><th>Max điểm</th><th>Loại</th><th>Slot</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="empty">Class này chưa có kỹ năng.</td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= e($r['NAME']) ?></td>
            <td><?= (int)($r['max_point']??0) ?></td>
            <td><?= (int)($r['TYPE']??0) ?></td>
            <td><?= (int)($r['slot']??0) ?></td>
            <td class="actions">
                <a class="btn" href="skills.php?class=<?= $class ?>&edit=<?= (int)$r['id'] ?>">Sửa</a>
                <form method="post" onsubmit="return confirm('Xoá kỹ năng này?')">
                    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="do" value="delete">
                    <input type="hidden" name="nclass_id" value="<?= $class ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn danger">Xoá</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
    </div>
    <p class="dim">Kỹ năng theo từng class (khoá kép nclass_id + id). Áp dụng khi server nạp lại dữ liệu.</p>
<?php endif; ?>
<?php require_once __DIR__ . '/footer.php'; ?>
