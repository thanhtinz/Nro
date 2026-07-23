<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'posts', 'title' => 'Bài viết forum (posts)',
    'table' => 'posts', 'pk' => 'id', 'name' => '', 'self' => 'posts.php',
    'note' => 'Kiểm duyệt bài viết — xoá bài vi phạm.',
]);
