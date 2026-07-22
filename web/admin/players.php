<?php
$__active = 'players';
$__title  = 'Nhân vật';
require_once __DIR__ . '/config.php';
require_admin();
$c = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['player_id'] ?? 0);
    if ($id <= 0) { flash('ID nhân vật không hợp lệ.'); header('Location: players.php'); exit(); }

    switch ($action) {
        case 'next_task':
            // Tăng nhiệm vụ chính hiện tại lên 1 (giới hạn tối đa 100), giữ format [task,0,0]
            $stmt = $c->prepare("SELECT JSON_EXTRACT(data_task, '$[0]') FROM player WHERE id=?");
            $stmt->bind_param('i', $id); $stmt->execute();
            $cur = (int)($stmt->get_result()->fetch_row()[0] ?? 0); $stmt->close();
            if ($cur >= 100) { flash('Nhân vật đã ở nhiệm vụ tối đa.'); break; }
            $next = $cur + 1;
            $data = "[$next,0,0]";
            $stmt = $c->prepare('UPDATE player SET data_task=? WHERE id=?');
            $stmt->bind_param('si', $data, $id); $stmt->execute(); $stmt->close();
            flash("Đã chuyển nhân vật #$id sang nhiệm vụ $next.");
            break;

        case 'delete_player':
            $stmt = $c->prepare('DELETE FROM player WHERE id=? LIMIT 1');
            $stmt->bind_param('i', $id); $stmt->execute();
            $ok = $stmt->affected_rows; $stmt->close();
            flash($ok ? "Đã xoá nhân vật #$id." : "Không tìm thấy nhân vật #$id.");
            break;

        default:
            flash('Hành động không hợp lệ.');
    }
    header('Location: players.php' . (!empty($_POST['q']) ? '?q=' . urlencode($_POST['q']) : ''));
    exit();
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = '%' . $q . '%';
    $idq  = ctype_digit($q) ? (int)$q : 0;
    $stmt = $c->prepare(
        "SELECT p.id, p.name, p.account_id, p.head, p.gender, p.clan_id, p.rank, p.create_time,
                JSON_EXTRACT(p.data_task, '$[0]') AS task, a.username, a.ban
           FROM player p LEFT JOIN account a ON a.id = p.account_id
          WHERE p.name LIKE ? OR p.id = ? OR p.account_id = ?
          ORDER BY p.id DESC LIMIT 100"
    );
    $stmt->bind_param('sii', $like, $idq, $idq);
} else {
    $stmt = $c->prepare(
        "SELECT p.id, p.name, p.account_id, p.head, p.gender, p.clan_id, p.rank, p.create_time,
                JSON_EXTRACT(p.data_task, '$[0]') AS task, a.username, a.ban
           FROM player p LEFT JOIN account a ON a.id = p.account_id
          ORDER BY p.id DESC LIMIT 50"
    );
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$genders = [0 => 'Trái Đất', 1 => 'Namek', 2 => 'Xayda'];

require_once __DIR__ . '/header.php';
$tok = csrf_token();
?>
<h1>Quản lý nhân vật</h1>

<form class="search" method="get">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Tìm theo tên nhân vật / ID / account_id...">
    <button type="submit">Tìm</button>
    <?php if ($q !== ''): ?><a class="clear" href="players.php">Xoá lọc</a><?php endif; ?>
</form>

<div class="tablewrap">
<table>
<thead><tr>
    <th>ID</th><th>Tên</th><th>Tài khoản</th><th>Hành tinh</th><th>Clan</th>
    <th>Rank</th><th>Nhiệm vụ</th><th>Tạo lúc</th><th>Thao tác</th>
</tr></thead>
<tbody>
<?php if (!$rows): ?>
    <tr><td colspan="9" class="empty">Không có nhân vật nào.</td></tr>
<?php else: foreach ($rows as $r): ?>
    <tr>
        <td><?= (int)$r['id'] ?></td>
        <td>
            <?= e($r['name']) ?>
            <?php if ((int)$r['ban'] === 1): ?><span class="tag ban">TK khoá</span><?php endif; ?>
        </td>
        <td class="dim"><?= $r['username'] !== null ? e($r['username']) : '—' ?> <span class="dim">#<?= (int)$r['account_id'] ?></span></td>
        <td><?= e($genders[(int)$r['gender']] ?? '?') ?></td>
        <td><?= (int)$r['clan_id'] === -1 ? '—' : (int)$r['clan_id'] ?></td>
        <td><?= (int)$r['rank'] ?></td>
        <td><?= $r['task'] !== null ? (int)$r['task'] : '—' ?></td>
        <td class="dim"><?= e($r['create_time']) ?></td>
        <td class="actions">
            <a class="btn" href="player_detail.php?id=<?= (int)$r['id'] ?>">Chi tiết</a>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e($tok) ?>">
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <input type="hidden" name="player_id" value="<?= (int)$r['id'] ?>">
                <button name="action" value="next_task" class="btn"
                        onsubmit="return confirm('Next nhiệm vụ?')">Next NV</button>
                <button name="action" value="delete_player" class="btn danger"
                        onclick="return confirm('XOÁ nhân vật <?= e($r['name']) ?>? Không thể hoàn tác!')">Xoá</button>
            </form>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
<p class="dim">Xoá nhân vật là thao tác không hoàn tác — cân nhắc backup DB trước.</p>
<?php require_once __DIR__ . '/footer.php'; ?>
