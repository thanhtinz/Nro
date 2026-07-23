<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'welfare', 'title' => 'Phúc lợi — Quà bùa miễn phí hằng ngày (Bà Hạt Mít)',
    'table' => 'daily_gift_reward', 'pk' => 'id', 'name' => 'note', 'self' => 'welfare.php',
    'list_cols' => ['id','item_id','duration_min','enabled','note'],
    'labels' => ['item_id'=>'ID vật phẩm (bùa)','duration_min'=>'Thời hạn (phút)','enabled'=>'Bật (1/0)','note'=>'Ghi chú'],
    'note' => 'Mỗi ngày người chơi nhận NGẪU NHIÊN 1 dòng đang Bật. Chỉnh là server tự áp dụng trong ~3 giây, KHÔNG cần restart. Tra ID vật phẩm ở trang Vật phẩm. Để trống (không dòng nào Bật) => server dùng mặc định code (bùa 213–219, 60 phút).',
]);
