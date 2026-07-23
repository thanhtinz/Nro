<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'tabshop', 'title' => 'Tab cửa hàng (tab_shop)',
    'table' => 'tab_shop', 'pk' => 'id', 'name' => 'NAME', 'self' => 'tabshop.php', 'reload' => 'shop',
    'note' => 'Tab của cửa hàng. Sửa xong server tự cập nhật shop.',
]);
