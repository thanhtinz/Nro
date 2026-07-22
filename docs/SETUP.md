# Hướng dẫn build & chạy

## Server

Yêu cầu: **JDK 8+**, **Apache Ant**, **MySQL 5.7/8**, (tuỳ chọn) **MongoDB**.

1. **Tạo DB & nạp dữ liệu**
   ```sql
   CREATE DATABASE team2026 CHARACTER SET utf8mb4;
   ```
   ```bash
   mysql -u root -p team2026 < server/database_team2026.sql
   ```
2. **Cấu hình** `server/Config.properties`:
   - `server.ip`, `server.port` (mặc định 14445)
   - `database.host/name/user/pass` (mặc định `team2026`, user `root`)
   - `server.local=true` khi test máy cá nhân
3. **Lấy tài nguyên `data/`**: giải nén `Teamobi2026.rar` từ Release và copy thư mục `SRC/data` vào `server/data/` (không có trong git vì ~732MB).
4. **Build & chạy**:
   ```bash
   cd server
   ant clean jar        # sinh dist/ hoặc 20.jar theo build.xml
   java -server -Dfile.encoding=UTF-8 -jar 20.jar
   ```
   Trên Windows có thể dùng `run.bat`. Server tự khởi động lại bằng `restart_server.bat`.

## Client (Unity)

1. Cài **Unity Hub** + **Unity 2022.3.62f2** (đúng bản, xem `client/ProjectSettings/ProjectVersion.txt`).
2. Trong Unity Hub → **Add** → chọn thư mục `client/`.
3. Lần mở đầu Unity sẽ import lại `Library/` (mất vài phút).
4. Sửa địa chỉ server kết nối trong code client (`Assets/Scripts/.../Game1` hoặc `Game2`) cho khớp `server.ip:port`.
5. Build ra Android/iOS/PC qua **File → Build Settings**.

## Web

Yêu cầu: **PHP 7.4/8**, **MySQL**, web server (Apache/Nginx) hoặc `php -S`.

1. Trỏ document root vào `web/`.
2. Sửa `web/cauhinh.php` và `web/connect.php`: host, user, pass, tên DB (mặc định `ngocrong`).
   > Lưu ý: web dùng DB tên `ngocrong`, server dùng `team2026`. Nếu muốn dùng chung 1 DB, chỉnh cho khớp tên và bảng (`account`, `player`, `napthe`, `payments`…).
3. Chạy thử nhanh:
   ```bash
   cd web && php -S 0.0.0.0:8080
   ```
4. Cấu hình cổng nạp thẻ (Momo/Bank) trong `nap-momo.php`, `momo.php`, `api/bank.php` — **thay khoá thật của bạn**.

## Ghi chú khi cập nhật game
- **Thêm/sửa vật phẩm**: bảng `item_template` (+ `item_option_template`), đối chiếu `server/item.xlsx`.
- **Sửa quái/map**: bảng `mob_template`, `map_template`; tài nguyên trong `server/data/`.
- **Sửa NPC/shop**: bảng `npc_template`, `shop`, `tab_shop`, code trong `nro/models/npc*`, `nro/models/shop*`.
- **Sự kiện**: `nro/models/event*`.
- Sau khi sửa code Java, **build lại jar** rồi restart server; sửa DB thì chạy SQL trực tiếp.
