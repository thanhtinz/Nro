# Audit — Admin quản lý được gì trong game

Cập nhật theo tiến độ. ✅ = đã có trong panel, ⏳ = cần làm thêm.

## Tài khoản & người chơi
- ✅ Tài khoản: tìm, khoá/mở, cấp/gỡ admin, kích hoạt, cộng VNĐ/vàng/VIP/điểm
- ✅ Nhân vật: tìm, next nhiệm vụ, xoá, xem/sửa chi tiết
- ✅ Nạp thẻ: xem lịch sử (SePay/thẻ)
- ✅ Giftcode: tạo/xoá + phần thưởng (cũng là cách cấp vật phẩm)

## Dữ liệu game (sửa DB, hiệu lực sau reload/restart)
- ✅ Vật phẩm (`item_template`)
- ✅ Quái/Boss (`mob_template`)
- ✅ NPC (`npc_template`)
- ✅ Bản đồ (`map_template`)
- ✅ Danh hiệu (`achievement_template`)
- ✅ Cửa hàng (`shop`)
- ✅ Bang hội (`clan`)
- ✅ Nhiệm vụ chính (`task_main_template`)
- ⏳ Kỹ năng (`skill_template`) — khoá chính ghép `(nclass_id,id)`, cần trang xử lý khoá kép
- ⏳ Tab/Item trong shop (`tab_shop`, `item_shop`, `item_shop_option`) — quan hệ lồng nhau
- ⏳ Nhiệm vụ phụ (`side_task_template`), danh hiệu nhiệm vụ (`task_badges_template`)

## Điều khiển server (runtime — config-sync, chỉnh là server tự áp dụng)
- ✅ Hệ số EXP (`rate_exp`) — chỉnh giá trị
- ✅ Bảo trì (`maintenance`) — bật/tắt
- ✅ Bật/tắt sự kiện (`event_*`): Tết, 8/3, Giáng Sinh, Halloween, Hùng Vương, Trung Thu, Top Up
- ✅ Thông báo in-game tới tất cả (hành động)
- ✅ Reset boss / Reset BXH / Restart (hành động 1 lần)
- ✅ Trạng thái sống: online, uptime, EXP, bảo trì, sự kiện, heartbeat

## Lịch hoạt động (config-sync)
- ✅ **Lịch hoạt động** (`server_schedule`): admin đặt giờ (HH:MM) + hành động (thông báo, reset boss, reset BXH, bật/tắt sự kiện, bảo trì); server tự chạy đúng giờ, mỗi lịch 1 lần/ngày. Trang **Lịch**.

## Còn thiếu / cần thiết kế thêm
- ⏳ **Phúc lợi / điểm danh (daily gift) chi tiết**: phần thưởng daily gift **hardcode trong code** (`ConstDailyGift`, `DailyGiftService` — 2 mốc), không có bảng phần thưởng → sửa nội dung quà cần **thêm bảng `daily_gift_reward` + sửa server đọc**. (Có thể tạm dùng **Lịch** để phát quà/thông báo phúc lợi theo giờ.)
- ⏳ **Chat thế giới từ web**: hiện có thông báo popup. Chat kênh thế giới cần thêm hành động gọi `ChatGlobalService`.
- ⏳ **Thư (mail) offline**: source không có bảng lưu thư → cần thêm bảng + server phát khi người chơi online.
- ⏳ **Kỹ năng** (`skill_template`) — khoá kép, cần trang riêng.

## Ghi chú vận hành
- Sửa **template** (item/npc/map/boss/...) chỉ vào game sau khi **server reload/restart** (server nạp lúc khởi động).
- Sửa **người chơi đang online** có thể bị server ghi đè khi lưu → nên thao tác khi offline, hoặc bổ sung lệnh qua cầu nối.
- Điều khiển runtime cần đã gắn `WebControlService` (xem `PHASE2_SERVER_BRIDGE.md`).
