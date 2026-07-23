# Phase 2 — Cầu nối Web Admin ↔ Server game

Cho phép trang admin điều khiển server game **đang chạy** (bảo trì, EXP, reset boss, reset BXH, thông báo in-game, restart) và hiển thị trạng thái sống của server (online, uptime, EXP, bảo trì).

Vì web (PHP) không gọi trực tiếp được server (Java), ta dùng **mô hình config-sync** với 2 bảng:
- `server_config` — **admin chỉ chỉnh giá trị**, server đọc mỗi ~3 giây và **tự áp dụng** (không cần "gửi lệnh").
- `server_status` — server ghi trạng thái sống → web đọc hiển thị.

Cách hoạt động:
- **Setting** (`rate_exp`, `maintenance`, `event_*`): đặt = trạng thái mong muốn → server đồng bộ liên tục.
- **Hành động 1 lần** (`do_reset_boss`, `do_reset_rank`, `do_restart`, `notify_seq`): web đổi giá trị (timestamp) → server phát hiện thay đổi và chạy đúng 1 lần.

## Cài đặt (3 bước)

### 1. Tạo bảng cầu nối
Chạy trên **DB game** (mặc định `team2026`):
```bash
mysql -u root -p team2026 < web/admin/sql/bridge.sql
```

### 2. Gắn luồng đọc vào server
File Java đã có sẵn: `server/src/nro/models/server/WebControlService.java`.
Chỉ cần **khởi động** nó khi server chạy — thêm 1 dòng vào `ServerManager.init()`
(trong `server/src/nro/models/server/ServerManager.java`):

```java
public void init() {
    Manager.gI();
    HistoryTransactionDAO.deleteHistory();
    WebControlService.gI();   // <-- THÊM DÒNG NÀY
}
```

### 3. Build lại & chạy server
```bash
cd server
ant clean jar
java -server -Dfile.encoding=UTF-8 -jar 20.jar
```

> Cầu nối dùng chung DB với server (`LocalManager.getConnection()`), nên **web và server phải trỏ cùng 1 DB** (đã đồng bộ web → `team2026`).

## Khoá cấu hình (`server_config`)

| Khoá | Kiểu | Server áp dụng |
|------|------|----------------|
| `rate_exp` | setting (1–127) | `Manager.RATE_EXP_SERVER` |
| `maintenance` | setting (0/1) | 1 → `Maintenance.gI().startSeconds(60)` |
| `event_LUNNAR_NEW_YEAR` … `event_TOP_UP` | setting (0/1) | `EventManager.*` |
| `notify_text` + `notify_seq` | hành động | đổi `notify_seq` → `Service.gI().sendThongBaoAllPlayer(notify_text)` |
| `do_reset_boss` | hành động | đổi giá trị → `BossManager.gI().loadBoss()` |
| `do_reset_rank` | hành động | đổi giá trị → xoá `super_rank` |
| `do_restart` | hành động | đổi giá trị → `ServerManager.gI().close()` |

Server đọc `server_config` mỗi **~3 giây**, áp dụng setting (idempotent) + chạy hành động khi giá trị đổi,
đồng thời cập nhật `server_status` (online, uptime, exp, maintenance, events, heartbeat).
**Admin chỉ cần chỉnh giá trị & lưu — không cần bấm "gửi lệnh".**

## Lịch hoạt động (`server_schedule`)

Admin đặt lịch (giờ VN `HH:MM` + hành động) ở trang **Lịch**; server kiểm tra mỗi ~3 giây và
chạy đúng giờ, **mỗi lịch 1 lần/ngày** (cột `last_run`). Hành động: `notify`, `reset_boss`,
`reset_rank`, `event_on`/`event_off` (params = tên sự kiện), `maintenance`.
Bật/tắt sự kiện qua lịch sẽ cập nhật `server_config` để đồng bộ.

## Phúc lợi — quà bùa miễn phí (`daily_gift_reward`)

Kho bùa miễn phí hằng ngày (NPC Bà Hạt Mít). WebControlService nạp các dòng `enabled=1`
vào `DailyGiftConfig` mỗi ~3 giây; `BaHatMit.java` bốc ngẫu nhiên từ kho (fallback về mặc định
cũ `213–219 / 60 phút` nếu kho trống). Admin chỉnh bảng ở trang **Phúc lợi** → **có hiệu lực ngay,
không cần restart**. (File liên quan: `daily_Giftcode/DailyGiftConfig.java`, sửa nhỏ trong `npc_list/BaHatMit.java`.)

## Kiểm tra hoạt động
- Vào admin → **⚙ Server**: nếu thẻ "Trạng thái server" hiện 🟢 Online nghĩa là server đang gửi heartbeat (cầu nối chạy tốt).
- Gửi thử lệnh "Gửi thông báo" — trong ít giây trạng thái lệnh chuyển sang **Xong** và người chơi thấy thông báo.

## Mở rộng thêm cấu hình/hành động
- Setting mới: thêm khoá vào `server_config`, đọc & áp dụng trong `WebControlService.applySettings()`,
  thêm ô nhập trong `web/admin/server.php`.
- Hành động mới: thêm khoá `do_...` vào mảng `TRIGGERS` + `fireTrigger()` (server) và nút bấm ở web
  (ghi `time()` vào khoá đó).

## An toàn
- Trang `server.php` / `events.php` yêu cầu đăng nhập admin, dùng CSRF + whitelist khoá.
- Các hành động nguy hiểm (bảo trì, restart, reset BXH) đều có xác nhận.
