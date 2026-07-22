<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'maps', 'title' => 'Dữ liệu bản đồ (map_template)',
    'table' => 'map_template', 'pk' => 'id', 'name' => 'NAME', 'self' => 'maps.php',
    'list_cols' => ['id','NAME','zones','max_player','type','planet_id'],
    'labels' => ['NAME'=>'Tên map','zones'=>'Số khu','max_player'=>'Max người','type'=>'Loại','planet_id'=>'Hành tinh'],
    'note' => 'Sửa bản đồ (khu, số người, mob/npc trong map...). Có hiệu lực sau khi server reload/restart.',
]);
