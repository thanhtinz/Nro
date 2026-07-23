<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'parts', 'title' => 'Bộ phận cơ thể (part)',
    'table' => 'part', 'pk' => 'id', 'name' => '', 'self' => 'parts.php',
    'list_cols' => ['id','TYPE','DATA'],
    'labels' => ['TYPE'=>'Loại','DATA'=>'Dữ liệu'],
    'note' => 'Dữ liệu bộ phận (part) dùng dựng hình nhân vật. Sửa cẩn thận đúng định dạng.',
]);
