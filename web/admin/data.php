<?php
$__active = 'data';
$__title  = 'Dữ liệu game';
require_once __DIR__ . '/config.php';
require_admin();

$GROUPS = [
    'Vật phẩm & trang bị' => [
        ['items.php', 'Vật phẩm', 'item_template'],
        ['itemoptions.php', 'Option vật phẩm', 'item_option_template'],
        ['bgitems.php', 'Đồ nền / thời trang', 'bg_item_template'],
        ['headavatar.php', 'Avatar đầu', 'head_avatar'],
    ],
    'Cửa hàng (sửa là áp dụng ngay)' => [
        ['shops.php', 'Cửa hàng', 'shop'],
        ['tabshop.php', 'Tab cửa hàng', 'tab_shop'],
        ['itemshop.php', 'Vật phẩm trong shop', 'item_shop'],
        ['consign.php', 'Shop ký gửi', 'shop_ky_gui'],
    ],
    'Thế giới' => [
        ['bosses.php', 'Boss / Quái', 'mob_template'],
        ['npcs.php', 'NPC', 'npc_template'],
        ['maps.php', 'Bản đồ', 'map_template'],
    ],
    'Nhiệm vụ & danh hiệu' => [
        ['tasks.php', 'Nhiệm vụ chính', 'task_main_template'],
        ['subtasks.php', 'Nhiệm vụ con', 'task_sub_template'],
        ['sidetasks.php', 'Nhiệm vụ phụ', 'side_task_template'],
        ['clantasks.php', 'Nhiệm vụ bang', 'clan_task_template'],
        ['badges.php', 'Danh hiệu', 'achievement_template'],
        ['taskbadges.php', 'Nhiệm vụ huy hiệu', 'task_badges_template'],
        ['databadges.php', 'Huy hiệu', 'data_badges'],
    ],
    'Bang hội & cộng đồng' => [
        ['clans.php', 'Bang hội', 'clan'],
        ['posts.php', 'Bài viết forum', 'posts'],
        ['comments.php', 'Bình luận forum', 'comments'],
        ['chatrooms.php', 'Phòng chat', 'phongchat'],
    ],
];

require_once __DIR__ . '/header.php';
?>
<h1>Dữ liệu game</h1>
<p class="dim">Mỗi mục là 1 trang riêng, sửa trực tiếp bảng DB thật. Cửa hàng sửa là server tự cập nhật ngay (không cần restart); các template khác áp dụng khi server nạp lại dữ liệu.</p>

<?php foreach ($GROUPS as $title => $items): ?>
    <h2><?= e($title) ?></h2>
    <div class="quick">
        <?php foreach ($items as $it): ?>
            <a class="qbtn" href="<?= e($it[0]) ?>"><?= e($it[1]) ?> <span class="dim mono"><?= e($it[2]) ?></span></a>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php require_once __DIR__ . '/footer.php'; ?>
