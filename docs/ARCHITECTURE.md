# Kiến trúc source NRO

Tài liệu khảo sát mã nguồn, dùng làm bản đồ để chỉnh sửa / cập nhật game.

## 1. Server (`server/`) — Java

Project NetBeans/Ant, tên `NgocRongOnline`. Điểm khởi động: `nro.models.server.ServerManager` (`main()`), chạy qua `run.bat` → `java -jar 20.jar`.

### Thư viện (`server/lib/`)
- **HikariCP 5.1.0** + `mysql-connector-java8` — pool kết nối MySQL
- **mongodb-driver-sync 5.1.3** — MongoDB (lưu dữ liệu người chơi dạng document)
- `gson`, `json-simple`, `java-json`, `bson` — JSON
- `lombok`, `apache-commons-lang`, `log4j`, `slf4j`

### Các package chính (`server/src/nro/models/`)
| Package | Vai trò |
|---------|--------|
| `server/` | Khởi động server, mạng, bảo trì (`ServerManager`, `Maintenance`, `Manager`) |
| `network/` | Socket, session, mã hoá khoá (`Network`, `MySession`, `MyKeyHandler`) |
| `player/`, `player_system/` | Nhân vật người chơi, chỉ số, hệ thống |
| `boss/` | **159 file** — toàn bộ hệ thống boss & manager boss (Broly, Yardart, Red Ribbon…) |
| `npc/`, `npc_list/` | NPC và hội thoại (**59 file**) |
| `item/`, `shop/`, `shop_ky_gui/`, `tab_shop` | Vật phẩm, cửa hàng, shop ký gửi |
| `combine/` | Ép/khảm/cường hoá đồ (**32 file**) |
| `skill/`, `intrinsic/` | Kỹ năng, nội tại |
| `map/`, `mob/`, `mob_bigboss/` | Bản đồ, quái, big boss |
| `task/`, `services/` | Nhiệm vụ chính/phụ, service game |
| `matches/` | Giải đấu (Thiên hạ đệ nhất võ đài, sinh tử…) |
| `event/`, `event_list/`, `ievent/` | Sự kiện |
| `clan/` | Bang hội |
| `minigame/` | Minigame (Chọn Ai Đây, Con Số May Mắn) |
| `daily_Giftcode/`, `GiftCode` | Giftcode |
| `Bot/` | Bot NPC ảo |
| `database/` | DAO truy vấn MySQL |

### Cấu hình
- `server/Config.properties` — IP/port server, thông tin MySQL (`database.name=team2026`), số người tối đa, tỉ lệ EXP…
- `server/maintenanceConfig.txt` — bật/tắt bảo trì
- Lệnh admin: `server/admin_commands.docx` (vd: `item getitem`, `hp <so>`, `ki <so>`, `up <power>`, `bien hinh`…)

### Cơ sở dữ liệu — `server/database_team2026.sql` (41 bảng)
Nhóm bảng quan trọng khi cân bằng/cập nhật game:
- **Người chơi**: `account`, `player`, `super_rank`, `data_badges`
- **Vật phẩm**: `item_template`, `item_option_template`, `item_shop`, `bg_item_template`, `head_avatar`, `part`
- **Thế giới**: `map_template`, `mob_template`, `npc_template`
- **Kỹ năng**: `skill_template`, `intrinsic`, `achievement_template`
- **Nhiệm vụ**: `task_main_template`, `side_task_template`, `clan_task_template`, `task_badges_template`
- **Shop/Nạp**: `shop`, `tab_shop`, `napthe`, `payments`, `bank_transfers`, `history_transaction`
- **Khác**: `clan`, `giftcode`, `notify`, `settings`, `posts`, `comments`, `phongchat`

> `item.xlsx` ở `server/` là bảng tra cứu item (id, tên, chỉ số) — tiện khi thêm/sửa vật phẩm.

## 2. Client (`client/`) — Unity 2022.3.62f2

- Product: **NRO QUEEN** (`ProjectSettings/ProjectSettings.asset`).
- `Assets/Scripts/Assembly-CSharp/` chia làm **`Game1`** và **`Game2`** (2 tab game) + folder `Mod`.
- `Assets/Resources/` — tài nguyên client (ảnh theo `res/x4/...`, giao diện).
- `Assets/Scenes/` — scene Unity.
- Mở bằng đúng phiên bản Unity **2022.3.62f2**; `Library/` sẽ tự sinh lại (không có trong git).

## 3. Web (`web/`) — PHP

- Kết nối DB qua `mysqli` — cấu hình ở `web/cauhinh.php` / `web/connect.php` (DB mặc định tên `ngocrong`).
- Trang chính: `index.php`, `trang-chu.php`, `gioi-thieu.php`.
- Tài khoản: `mo-thanh-vien.php` (đăng ký), `forgot-password.php`, `pass2.php`.
- Nạp thẻ / thanh toán: `nap-momo.php`, `momo.php`, `nap-so-du.php`, `data_nap_the.php`, `api/bank.php`.
- Forum: `forum.php`, `forum_data.php`, `dang-bai.php`, `post_detail_logic.php`.
- Bảng xếp hạng: `bang-xep-hang.php`, `top-nap.php`, `top-nhiem-vu.php`.
- Thư mục con: `api/`, `app/`, `assets/`, `view/`, `images/`.

## Lưu ý bảo mật (đọc trước khi vận hành thật)
1. Đổi mật khẩu MySQL trong `server/Config.properties`, `web/cauhinh.php`.
2. Kiểm tra và thay khoá bí mật / thông tin merchant ở các file nạp thẻ (`momo.php`, `api/bank.php`).
3. Không public server test ra Internet khi chưa rà soát lỗ hổng SQL injection ở lớp web PHP.
