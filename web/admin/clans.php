<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'clans', 'title' => 'Bang hội (clan)',
    'table' => 'clan', 'pk' => 'id', 'name' => 'NAME', 'self' => 'clans.php',
    'list_cols' => ['id','NAME','NAME_2','power_point','max_member','clan_point'],
    'labels' => ['NAME'=>'Tên bang','NAME_2'=>'Tag','power_point'=>'Sức mạnh','max_member'=>'Max TV','clan_point'=>'Điểm bang'],
    'note' => 'Quản lý bang hội. Xoá/sửa bang có thể ảnh hưởng thành viên đang chơi — cân nhắc.',
]);
