<?php
/**
 * Thư viện CRUD dùng chung cho các trang quản lý dữ liệu game.
 * Mỗi trang riêng gọi crud_page($cfg) với cấu hình bảng của mình.
 * - Bảng & khoá chính: whitelist trong từng trang -> an toàn khi nội suy SQL.
 * - Tên cột: lấy từ information_schema, đối chiếu cột thật -> chống injection.
 * - Giá trị: luôn bind tham số (MySQL tự ép kiểu số).
 * Dữ liệu 100% lấy từ DB thật của game.
 */
require_once __DIR__ . '/config.php';

/** Báo cho server nạp lại (config-sync): bump 1 khoá do_reload_* = thời gian hiện tại */
function crud_signal_reload(mysqli $c, string $what): void
{
    // chỉ bump nếu đã cài cầu nối (bảng server_config tồn tại)
    $r = $c->query("SELECT 1 FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_name = 'server_config' LIMIT 1");
    if (!$r || !$r->num_rows) return;
    $key = 'do_reload_' . $what;
    $val = (string)time();
    $stmt = $c->prepare('INSERT INTO server_config (cfg_key, cfg_value) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE cfg_value = VALUES(cfg_value)');
    $stmt->bind_param('ss', $key, $val);
    $stmt->execute(); $stmt->close();
}

/** Lấy cột thật của bảng: [ten => kieu] */
function crud_columns(mysqli $c, string $table): array
{
    $stmt = $c->prepare(
        'SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ORDINAL_POSITION'
    );
    $stmt->bind_param('s', $table); $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    $cols = [];
    foreach ($rows as $r) $cols[$r['COLUMN_NAME']] = $r['DATA_TYPE'];
    return $cols;
}

/**
 * Vẽ trang CRUD hoàn chỉnh.
 * $cfg = [
 *   'active'   => key menu, 'title' => tiêu đề trang,
 *   'table'    => tên bảng (whitelist), 'pk' => cột khoá,
 *   'name'     => cột để tìm theo tên (tuỳ chọn),
 *   'self'     => tên file trang (vd 'items.php'),
 *   'list_cols'=> mảng cột hiển thị ở bảng danh sách (tuỳ chọn),
 *   'labels'   => [col => nhãn tiếng Việt] (tuỳ chọn),
 *   'note'     => ghi chú dưới bảng (tuỳ chọn),
 * ]
 */
function crud_page(array $cfg): void
{
    $c = db();
    require_admin();

    $table = $cfg['table'];
    $pk    = $cfg['pk'];
    $self  = $cfg['self'];
    $nameCol = $cfg['name'] ?? null;
    $labels  = $cfg['labels'] ?? [];

    $cols = crud_columns($c, $table);
    $colNames = array_keys($cols);
    if (!$colNames) { die('Bảng không tồn tại: ' . e($table)); }

    // ---- Ghi ----
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $action = $_POST['action'] ?? '';
        $back = $self . (!empty($_POST['q']) ? '?q=' . urlencode($_POST['q']) : '');

        if ($action === 'delete') {
            $id = $_POST['pk'] ?? '';
            $stmt = $c->prepare("DELETE FROM `$table` WHERE `$pk` = ? LIMIT 1");
            $stmt->bind_param('s', $id); $stmt->execute(); $stmt->close();
            crud_signal_reload($c, 'data'); if (!empty($cfg['reload'])) crud_signal_reload($c, $cfg['reload']);
            flash("Đã xoá #$id.");
            header("Location: $back"); exit();
        }
        if ($action === 'save' || $action === 'create') {
            $data = $_POST['f'] ?? [];
            $use = array_intersect(array_keys($data), $colNames);
            try {
                if ($action === 'create') {
                    $fields = array_values($use);
                    if (!$fields) throw new RuntimeException('Không có dữ liệu.');
                    $place  = implode(',', array_fill(0, count($fields), '?'));
                    $colsql = implode(',', array_map(fn($x) => "`$x`", $fields));
                    $stmt = $c->prepare("INSERT INTO `$table` ($colsql) VALUES ($place)");
                    $stmt->bind_param(str_repeat('s', count($fields)), ...array_map(fn($x) => (string)$data[$x], $fields));
                    $stmt->execute(); $stmt->close();
                    flash('Đã thêm bản ghi mới.');
                } else {
                    $id = $_POST['pk'] ?? '';
                    $setCols = array_values(array_diff($use, [$pk]));
                    if ($setCols) {
                        $set = implode(',', array_map(fn($x) => "`$x`=?", $setCols));
                        $stmt = $c->prepare("UPDATE `$table` SET $set WHERE `$pk`=?");
                        $vals = array_map(fn($x) => (string)$data[$x], $setCols);
                        $vals[] = (string)$id;
                        $stmt->bind_param(str_repeat('s', count($vals)), ...$vals);
                        $stmt->execute(); $stmt->close();
                    }
                    flash("Đã lưu #$id.");
                }
            } catch (Throwable $ex) {
                flash('Lỗi: ' . $ex->getMessage());
            }
            crud_signal_reload($c, 'data'); if (!empty($cfg['reload'])) crud_signal_reload($c, $cfg['reload']);
            header("Location: $back"); exit();
        }
    }

    // ---- Sửa/thêm ----
    $editing = null; $isNew = isset($_GET['new']);
    if (isset($_GET['edit'])) {
        $stmt = $c->prepare("SELECT * FROM `$table` WHERE `$pk` = ? LIMIT 1");
        $stmt->bind_param('s', $_GET['edit']); $stmt->execute();
        $editing = $stmt->get_result()->fetch_assoc(); $stmt->close();
    }

    // ---- Danh sách ----
    $q = trim($_GET['q'] ?? '');
    if ($q !== '' && !$editing && !$isNew) {
        $like = '%' . $q . '%';
        if ($nameCol && in_array($nameCol, $colNames, true)) {
            $stmt = $c->prepare("SELECT * FROM `$table` WHERE `$pk`=? OR `$nameCol` LIKE ? ORDER BY `$pk` LIMIT 100");
            $stmt->bind_param('ss', $q, $like);
        } else {
            $stmt = $c->prepare("SELECT * FROM `$table` WHERE `$pk`=? ORDER BY `$pk` LIMIT 100");
            $stmt->bind_param('s', $q);
        }
        $stmt->execute(); $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    } else {
        $rows = $c->query("SELECT * FROM `$table` ORDER BY `$pk` DESC LIMIT 60")->fetch_all(MYSQLI_ASSOC);
    }

    $listCols = $cfg['list_cols'] ?? array_slice($colNames, 0, 7);
    $__active = $cfg['active']; $__title = $cfg['title'];
    require __DIR__ . '/header.php';
    $tok = csrf_token();
    $lbl = fn($cn) => $labels[$cn] ?? $cn;
    ?>
    <h1><?= e($cfg['title']) ?></h1>

    <?php if ($editing || $isNew):
        $rec = $editing ?: array_fill_keys($colNames, '');
    ?>
        <div class="box">
            <h2><?= $isNew ? 'Thêm mới' : ('Sửa #' . e($editing[$pk])) ?></h2>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="action" value="<?= $isNew ? 'create' : 'save' ?>">
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <?php if ($editing): ?><input type="hidden" name="pk" value="<?= e($editing[$pk]) ?>"><?php endif; ?>
                <div class="fgrid">
                <?php foreach ($colNames as $cn):
                    $type = $cols[$cn]; $val = (string)($rec[$cn] ?? '');
                    $isPk = ($cn === $pk);
                    $long = in_array($type, ['text','longtext','mediumtext','blob']) || strlen($val) > 60;
                ?>
                    <div class="fitem <?= $long ? 'wide' : '' ?>">
                        <label><?= e($lbl($cn)) ?> <span class="dim">(<?= e($type) ?>)</span></label>
                        <?php if ($long): ?>
                            <textarea name="f[<?= e($cn) ?>]" rows="2" <?= $isPk && $editing ? 'readonly' : '' ?>><?= e($val) ?></textarea>
                        <?php else: ?>
                            <input type="text" name="f[<?= e($cn) ?>]" value="<?= e($val) ?>" <?= $isPk && $editing ? 'readonly' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
                <button type="submit"><?= $isNew ? 'Thêm' : 'Lưu' ?></button>
                <a class="btn" href="<?= e($self) ?>">Huỷ</a>
            </form>
        </div>
    <?php else: ?>
        <form class="search" method="get">
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Tìm theo ID<?= $nameCol ? ' hoặc tên' : '' ?>...">
            <button>Tìm</button>
            <a class="btn ok" href="<?= e($self) ?>?new=1">＋ Thêm mới</a>
            <?php if ($q !== ''): ?><a class="clear" href="<?= e($self) ?>">Xoá lọc</a><?php endif; ?>
        </form>
        <div class="tablewrap">
        <table>
        <thead><tr>
            <?php foreach ($listCols as $cn): ?><th><?= e($lbl($cn)) ?></th><?php endforeach; ?>
            <th>Thao tác</th>
        </tr></thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="<?= count($listCols) + 1 ?>" class="empty">Không có dữ liệu.</td></tr>
        <?php else: foreach ($rows as $r): ?>
            <tr>
                <?php foreach ($listCols as $cn): $v = (string)($r[$cn] ?? ''); ?>
                    <td class="<?= strlen($v) > 30 ? 'dim mono' : '' ?>"><?= e(mb_strimwidth($v, 0, 34, '…')) ?></td>
                <?php endforeach; ?>
                <td class="actions">
                    <a class="btn" href="<?= e($self) ?>?edit=<?= urlencode($r[$pk]) ?>&q=<?= urlencode($q) ?>">Sửa</a>
                    <form method="post" onsubmit="return confirm('Xoá #<?= e($r[$pk]) ?>?')">
                        <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="q" value="<?= e($q) ?>">
                        <input type="hidden" name="pk" value="<?= e($r[$pk]) ?>">
                        <button class="btn danger">Xoá</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
        </table>
        </div>
        <p class="dim"><?= e($cfg['note'] ?? 'Thay đổi có hiệu lực sau khi server reload/restart.') ?></p>
    <?php endif;
    require __DIR__ . '/footer.php';
}
