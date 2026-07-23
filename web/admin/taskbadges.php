<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'taskbadges', 'title' => 'Nhiệm vụ huy hiệu (task_badges_template)',
    'table' => 'task_badges_template', 'pk' => 'id', 'name' => 'NAME', 'self' => 'taskbadges.php',
    'note' => 'Nhiệm vụ đạt huy hiệu.',
]);
