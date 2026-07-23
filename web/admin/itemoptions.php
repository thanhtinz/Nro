<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'itemoptions', 'title' => 'Option vật phẩm (item_option_template)',
    'table' => 'item_option_template', 'pk' => 'id', 'name' => 'NAME', 'self' => 'itemoptions.php',
    'note' => 'Các option/chỉ số gắn lên vật phẩm.',
]);
