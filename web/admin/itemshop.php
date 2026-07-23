<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'itemshop', 'title' => 'Vật phẩm trong shop (item_shop)',
    'table' => 'item_shop', 'pk' => 'id', 'name' => '', 'self' => 'itemshop.php', 'reload' => 'shop',
    'note' => 'Vật phẩm bán trong cửa hàng. Sửa xong server tự cập nhật shop (không cần restart).',
]);
