<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'servers', 'title' => 'Máy chủ (danh sách người chơi thấy khi đăng nhập)',
    'table' => 'server_list', 'pk' => 'id', 'name' => 'name', 'self' => 'servers.php',
    'list_cols' => ['id','name','ip','port','enabled','sort','note'],
    'labels' => ['name'=>'Tên máy chủ','ip'=>'IP/Domain','port'=>'Port','enabled'=>'Bật (1/0)','sort'=>'Thứ tự','note'=>'Ghi chú'],
    'note' => 'Thêm/sửa máy chủ hiển thị cho người chơi. Server tự áp dụng vào game trong ~3 giây (config-sync). LƯU Ý: đây chỉ là ENTRY danh sách — tiến trình game của máy chủ mới (JVM, port, DB riêng) phải deploy & chạy riêng. Nếu bảng trống, game dùng danh sách từ Config.properties.',
]);
