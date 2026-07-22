<?php
/**
 * item_lookup.php - API nội bộ: tra tên vật phẩm theo id.
 * Trả JSON {id, name} | {id, name:null}. Chỉ dùng cho admin đã đăng nhập.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib_game.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');
$id = (int)($_GET['id'] ?? -999);
echo json_encode(['id' => $id, 'name' => $id >= 0 ? game_item_name(db(), $id) : null]);
