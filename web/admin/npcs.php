<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'npcs', 'title' => 'Quản lý NPC (npc_template)',
    'table' => 'npc_template', 'pk' => 'id', 'name' => 'NAME', 'self' => 'npcs.php',
    'list_cols' => ['id','NAME','head','body','leg','avatar'],
    'labels' => ['NAME'=>'Tên NPC','head'=>'Đầu','body'=>'Thân','leg'=>'Chân'],
    'note' => 'Thêm/sửa NPC. Chức năng (menu) của NPC do code server xử lý; đổi tên/hình có hiệu lực sau reload/restart.',
]);
