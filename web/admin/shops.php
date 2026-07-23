<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'shops', 'title' => 'Cửa hàng (shop)',
    'table' => 'shop', 'pk' => 'id', 'name' => 'tag_name', 'self' => 'shops.php', 'reload' => 'shop',
    'list_cols' => ['id','npc_id','tag_name','type_shop'],
    'labels' => ['npc_id'=>'NPC bán','tag_name'=>'Tên tab','type_shop'=>'Loại shop'],
    'note' => 'Quản lý cửa hàng gắn với NPC. Tab & vật phẩm trong shop quản lý ở trang Tab/Item shop (sắp thêm).',
]);
