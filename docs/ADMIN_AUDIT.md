# Audit — Admin quản lý được gì trong game

Cập nhật theo tiến độ. ✅ = đã có trong panel, ⏳ = cần làm thêm.

## Tài khoản & người chơi
- ✅ Tài khoản: tìm, khoá/mở, cấp/gỡ admin, kích hoạt, cộng VNĐ/vàng/VIP/điểm
- ✅ Nhân vật: tìm, next nhiệm vụ, xoá, xem/sửa chi tiết
- ✅ Nạp thẻ: xem lịch sử (SePay/thẻ)
- ✅ Giftcode: tạo/xoá + phần thưởng (cũng là cách cấp vật phẩm)

## Dữ liệu game (trang riêng, gom ở hub **Dữ liệu game**)
**Sửa là áp dụng ngay, không restart** (hot-reload) cho: vật phẩm, option, quái/boss, NPC, nội tại, danh hiệu, cửa hàng. Các template còn lại (kỹ năng, nhiệm vụ, bản đồ, đồ nền/avatar/part/huy hiệu): sửa DB ngay, áp dụng khi server nạp lại dữ liệu (bổ sung vào `reloadTemplatesFromWeb()` được).
- ✅ Vật phẩm (`item_template`), Option vật phẩm (`item_option_template`), Đồ nền (`bg_item_template`), Avatar đầu (`head_avatar`)
- ✅ **Cửa hàng LIVE**: `shop`, `tab_shop`, `item_shop`, shop ký gửi (`shop_ky_gui`)
- ✅ Quái/Boss (`mob_template`), NPC (`npc_template`), Bản đồ (`map_template`)
- ✅ Nhiệm vụ: chính (`task_main_template`), con (`task_sub_template`), phụ (`side_task_template`), bang (`clan_task_template`), huy hiệu (`task_badges_template`)
- ✅ Danh hiệu (`achievement_template`), Huy hiệu (`data_badges`)
- ✅ Bang hội (`clan`)
- ✅ Forum: bài (`posts`), bình luận (`comments`), phòng chat (`phongchat`)
- ✅ Kỹ năng (`skill_template`) — trang riêng theo class (khoá kép `nclass_id,id`)
- ✅ Nội tại (`intrinsic`), Bộ phận (`part`)

## Điều khiển server (runtime — config-sync, chỉnh là server tự áp dụng)
- ✅ Hệ số EXP (`rate_exp`) — chỉnh giá trị
- ✅ Bảo trì (`maintenance`) — bật/tắt
- ✅ Bật/tắt sự kiện (`event_*`): Tết, 8/3, Giáng Sinh, Halloween, Hùng Vương, Trung Thu, Top Up
- ✅ Thông báo in-game tới tất cả (hành động)
- ✅ Reset boss / Reset BXH / Restart (hành động 1 lần)
- ✅ Trạng thái sống: online, uptime, EXP, bảo trì, sự kiện, heartbeat

## Lịch hoạt động (config-sync)
- ✅ **Lịch hoạt động** (`server_schedule`): admin đặt giờ (HH:MM) + hành động (thông báo, reset boss, reset BXH, bật/tắt sự kiện, bảo trì); server tự chạy đúng giờ, mỗi lịch 1 lần/ngày. Trang **Lịch**.

## Phúc lợi (config-sync)
- ✅ **Phúc lợi — quà bùa miễn phí hằng ngày** (`daily_gift_reward`): admin chỉnh kho bùa (item id, thời hạn, bật/tắt); server nạp lại mỗi ~3s (`DailyGiftConfig`) và Bà Hạt Mít bốc ngẫu nhiên từ kho — **chỉnh là chạy, không cần restart**. Trang **Phúc lợi**.

## Còn thiếu / cần thiết kế thêm
- ⏳ **Chat thế giới từ web**: hiện có thông báo popup. Chat kênh thế giới cần thêm hành động gọi `ChatGlobalService`.
- ⏳ **Thư (mail) offline**: source không có bảng lưu thư → cần thêm bảng + server phát khi người chơi online.
- ⏳ **Kỹ năng** (`skill_template`) — khoá kép, cần trang riêng.

## Ghi chú vận hành
- Sửa **template** (item/npc/map/boss/...) chỉ vào game sau khi **server reload/restart** (server nạp lúc khởi động).
- Sửa **người chơi đang online** có thể bị server ghi đè khi lưu → nên thao tác khi offline, hoặc bổ sung lệnh qua cầu nối.
- Điều khiển runtime cần đã gắn `WebControlService` (xem `PHASE2_SERVER_BRIDGE.md`).
