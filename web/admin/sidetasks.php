<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'sidetasks', 'title' => 'Nhiệm vụ phụ (side_task_template)',
    'table' => 'side_task_template', 'pk' => 'id', 'name' => 'NAME', 'self' => 'sidetasks.php',
    'note' => 'Sửa nhiệm vụ phụ. Áp dụng sau khi server nạp lại dữ liệu.',
]);
