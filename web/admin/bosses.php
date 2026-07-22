<?php require_once __DIR__ . '/crud_lib.php';
crud_page([
    'active' => 'bosses', 'title' => 'Quái / Boss (mob_template)',
    'table' => 'mob_template', 'pk' => 'id', 'name' => 'NAME', 'self' => 'bosses.php',
    'list_cols' => ['id','NAME','TYPE','hp','speed','percent_dame'],
    'labels' => ['NAME'=>'Tên','TYPE'=>'Loại','hp'=>'HP','speed'=>'Tốc độ','percent_dame'=>'% Sát thương','percent_tiem_nang'=>'% Tiềm năng'],
    'note' => 'Sửa chỉ số quái/boss (HP, sát thương...). Reset boss theo thời gian thực nằm ở trang Điều khiển server.',
]);
