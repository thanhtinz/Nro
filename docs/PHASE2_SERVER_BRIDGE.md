# Phase 2 — Cầu nối Web Admin ↔ Server game

Cho phép trang admin điều khiển server game **đang chạy** (bảo trì, EXP, reset boss, reset BXH, thông báo in-game, restart) và hiển thị trạng thái sống của server (online, uptime, EXP, bảo trì).

Vì web (PHP) không gọi trực tiếp được server (Java), ta dùng **2 bảng DB làm cầu nối**:
- `server_control` — hàng đợi lệnh: web ghi → server đọc & thực thi.
- `server_status` — server ghi trạng thái sống → web đọc hiển thị.

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

## Các lệnh hỗ trợ

| Lệnh (web gửi) | Hành động trong server |
|----------------|------------------------|
| `notify_all`   | `Service.gI().sendThongBaoAllPlayer(text)` — thông báo tới tất cả người chơi online |
| `set_exp`      | `Manager.RATE_EXP_SERVER = n` (1–127) |
| `reset_boss`   | `BossManager.gI().loadBoss()` |
| `reset_rank`   | Xoá bảng `super_rank` (reset BXH) |
| `maintenance`  | `Maintenance.gI().startSeconds(n)` — đếm ngược rồi vào bảo trì |
| `restart`      | `ServerManager.gI().close()` — chạy `restart_server.bat` & thoát |

Server xử lý hàng đợi mỗi **~3 giây**, ghi kết quả vào `server_control.result` và cập nhật
`server_status` (online, uptime, exp, maintenance, heartbeat).

## Kiểm tra hoạt động
- Vào admin → **⚙ Server**: nếu thẻ "Trạng thái server" hiện 🟢 Online nghĩa là server đang gửi heartbeat (cầu nối chạy tốt).
- Gửi thử lệnh "Gửi thông báo" — trong ít giây trạng thái lệnh chuyển sang **Xong** và người chơi thấy thông báo.

## Mở rộng thêm lệnh
Thêm `case "ten_lenh":` trong `WebControlService.execute()` (server) và thêm lệnh vào whitelist
`$CMDS` cùng nút bấm trong `web/admin/server.php`.

## An toàn
- Trang `server.php` yêu cầu đăng nhập admin, dùng CSRF + whitelist lệnh.
- Các lệnh nguy hiểm (bảo trì, restart, reset BXH) đều có xác nhận.
