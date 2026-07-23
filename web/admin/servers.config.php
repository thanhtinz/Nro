<?php
/**
 * Danh sách MÁY CHỦ mà admin panel có thể quản lý (mỗi máy chủ 1 DB riêng).
 * File PHP (không phải .json) để không bị tải lộ qua URL.
 * Quản lý qua trang "Máy chủ QL" trong panel, hoặc sửa tay ở đây.
 * key: định danh duy nhất; các trường host/dbname/user/pass = kết nối MySQL của máy chủ đó.
 */
return [
    [
        'key'    => 'sv1',
        'name'   => 'Server 1',
        'host'   => 'localhost',
        'dbname' => 'team2026',
        'user'   => 'root',
        'pass'   => '',
    ],
];
