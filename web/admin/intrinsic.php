<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'intrinsic', 'title' => 'Nội tại (intrinsic)',
    'table' => 'intrinsic', 'pk' => 'id', 'name' => 'NAME', 'self' => 'intrinsic.php',
    'list_cols' => ['id','NAME','param_from_1','param_to_1','param_from_2','param_to_2','gender'],
    'labels' => ['NAME'=>'Tên nội tại','gender'=>'Hành tinh'],
    'note' => 'Nội tại (tiềm năng) nhân vật. Áp dụng khi server nạp lại dữ liệu.',
]);
