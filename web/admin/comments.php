<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'comments', 'title' => 'Bình luận forum (comments)',
    'table' => 'comments', 'pk' => 'id', 'name' => '', 'self' => 'comments.php',
    'note' => 'Kiểm duyệt bình luận.',
]);
