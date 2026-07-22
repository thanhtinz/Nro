<?php
/**
 * lib_game.php - Tiện ích thao tác dữ liệu game (vật phẩm, giftcode).
 *
 * Định dạng dữ liệu tham chiếu từ server (nro.models.database.MrBlue):
 *  - Mỗi ô túi/rương/người là 1 CHUỖI JSON: [tempId, quantity, "optionsString", createTime]
 *      + tempId = -1  => ô trống
 *      + optionsString là 1 chuỗi JSON dạng mảng chuỗi:  ["[optId,param]", ...]
 *  - detail của giftcode là mảng object:
 *      [{"id":457,"quantity":50,"options":[{"id":30,"param":0}]}, ...]
 */

require_once __DIR__ . '/config.php';

/** Thời gian hiện tại theo mili-giây (khớp createTime kiểu Long của server). */
function now_ms(): int
{
    return (int) round(microtime(true) * 1000);
}

/** Tra tên vật phẩm theo id trong item_template (null nếu không có). */
function game_item_name(mysqli $c, int $id): ?string
{
    $stmt = $c->prepare('SELECT NAME FROM item_template WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();
    return $row ? (string) $row[0] : null;
}

/**
 * Phân tích chuỗi option người dùng nhập: "30:0, 7:5" => [[30,0],[7,5]].
 * Bỏ qua phần rỗng / sai định dạng.
 */
function parse_options_str(string $s): array
{
    $out = [];
    foreach (preg_split('/\s*,\s*/', trim($s), -1, PREG_SPLIT_NO_EMPTY) as $pair) {
        if (preg_match('/^(-?\d+)\s*:\s*(-?\d+)$/', $pair, $m)) {
            $out[] = [(int) $m[1], (int) $m[2]];
        }
    }
    return $out;
}

/**
 * Dựng chuỗi 1 ô vật phẩm đúng định dạng server đọc được.
 * @param array $opts danh sách cặp [optId, param]
 * @return string ví dụ: [457,50,"[\"[30,0]\"]",1712345678901]
 */
function build_item_slot(int $id, int $qty, array $opts, int $timeMs): string
{
    $optStrings = [];
    foreach ($opts as $o) {
        $optStrings[] = json_encode([(int) $o[0], (int) $o[1]]); // "[30,0]"
    }
    $optionsField = json_encode($optStrings); // chuỗi: ["[30,0]"]
    return json_encode([$id, $qty, $optionsField, $timeMs]);
}

/** Ô này có phải ô trống (tempId == -1) không? */
function slot_is_empty(string $slot): bool
{
    $arr = json_decode($slot, true);
    return is_array($arr) && isset($arr[0]) && (int) $arr[0] === -1;
}

/** Tạo ô trống chuẩn. */
function empty_slot(int $timeMs): string
{
    return json_encode([-1, 0, '[]', $timeMs]);
}

/**
 * Nạp thêm 1 vật phẩm vào 1 cột dữ liệu túi/rương (JSON mảng chuỗi).
 * Ưu tiên lấp ô trống; nếu hết ô trống thì nối thêm (append).
 *
 * @param string $rawJson giá trị hiện tại của cột (items_bag / items_box ...)
 * @param string $slot    ô vật phẩm đã dựng bằng build_item_slot()
 * @param string &$err    thông báo lỗi nếu có
 * @return string|null    JSON mới, hoặc null nếu lỗi
 */
function inventory_add_item(string $rawJson, string $slot, ?string &$err = null): ?string
{
    $rawJson = trim($rawJson);
    if ($rawJson === '') {
        $rawJson = '[]';
    }
    $arr = json_decode($rawJson, true);
    if (!is_array($arr)) {
        $err = 'Dữ liệu túi/rương không hợp lệ (không phải JSON).';
        return null;
    }
    // Lấp ô trống đầu tiên
    foreach ($arr as $i => $existing) {
        if (is_string($existing) && slot_is_empty($existing)) {
            $arr[$i] = $slot;
            return json_encode($arr);
        }
    }
    // Không còn ô trống -> nối thêm
    $arr[] = $slot;
    return json_encode($arr);
}

/** Đếm số ô trống trong 1 cột dữ liệu túi/rương. */
function inventory_count_empty(string $rawJson): int
{
    $arr = json_decode(trim($rawJson) ?: '[]', true);
    if (!is_array($arr)) return 0;
    $n = 0;
    foreach ($arr as $s) {
        if (is_string($s) && slot_is_empty($s)) $n++;
    }
    return $n;
}
