<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'tasks', 'title' => 'Nhiệm vụ chính (task_main_template)',
    'table' => 'task_main_template', 'pk' => 'id', 'name' => 'NAME', 'self' => 'tasks.php',
    'list_cols' => ['id','NAME','detail'],
    'labels' => ['NAME'=>'Tên nhiệm vụ','detail'=>'Chi tiết'],
    'note' => 'Sửa nhiệm vụ chính. Có hiệu lực sau khi server reload/restart.',
]);
