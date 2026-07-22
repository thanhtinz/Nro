<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'badges', 'title' => 'Danh hiệu (achievement_template)',
    'table' => 'achievement_template', 'pk' => 'id', 'name' => 'info1', 'self' => 'badges.php',
    'list_cols' => ['id','info1','info2','money','max_count'],
    'labels' => ['info1'=>'Tên/Mô tả','info2'=>'Chi tiết','money'=>'Thưởng','max_count'=>'Mốc'],
    'note' => 'Thêm/sửa danh hiệu & mốc phần thưởng. Có hiệu lực sau khi server reload/restart.',
]);
