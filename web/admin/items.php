<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'items', 'title' => 'Kho vật phẩm (item_template)',
    'table' => 'item_template', 'pk' => 'id', 'name' => 'NAME', 'self' => 'items.php',
    'list_cols' => ['id','NAME','TYPE','gender','gold','gem','level'],
    'labels' => ['NAME'=>'Tên','TYPE'=>'Loại','gender'=>'Giới tính','gold'=>'Vàng','gem'=>'Ngọc','level'=>'Cấp','power_require'=>'Sức mạnh y/c'],
    'note' => 'Sửa vật phẩm: giá, loại, chỉ số... Có hiệu lực sau khi server reload/restart.',
]);
