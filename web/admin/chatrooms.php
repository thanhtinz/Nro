<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'chatrooms', 'title' => 'Phòng chat (phongchat)',
    'table' => 'phongchat', 'pk' => 'id', 'name' => '', 'self' => 'chatrooms.php',
    'note' => 'Quản lý phòng chat trong game.',
]);
