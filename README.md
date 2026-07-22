# NRO — Ngọc Rồng Online (nền TeaMobi)

Kho mã nguồn game **Ngọc Rồng Online** gồm 3 thành phần: **Server** (Java), **Client** (Unity) và **Web** (PHP). Mã nguồn được tải từ [Release "Source game"](https://github.com/thanhtinz/Nro/releases/tag/Download) và tổ chức lại thành monorepo để dễ quản lý, chỉnh sửa và cập nhật bằng git.

> ⚠️ Đây là source học tập/tự vận hành server riêng. Hãy đổi toàn bộ mật khẩu DB, khoá bí mật cổng nạp thẻ trước khi chạy thật.

## Cấu trúc kho

| Thư mục | Thành phần | Công nghệ | Ghi chú |
|---------|-----------|-----------|---------|
| [`server/`](server/) | Server game | Java (NetBeans/Ant), MySQL, MongoDB | Lõi logic game — 548 file `.java`, 41 bảng DB |
| [`client/`](client/) | Client người chơi | Unity **2022.3.62f2** (C#) | Project "NRO QUEEN", 2 tab game (`Game1`, `Game2`) |
| [`web/`](web/) | Trang chủ / nạp thẻ / forum | PHP + MySQL (`mysqli`) | Trang chủ, admin, nạp Momo/Bank, forum |
| [`docs/`](docs/) | Tài liệu | — | Kiến trúc, hướng dẫn build/chạy, ghi chú chỉnh sửa |

## Những gì KHÔNG nằm trong git (để trong Release)

Vì GitHub giới hạn file 100MB và để repo gọn nhẹ, các phần **tài nguyên/binary nặng** không được commit — tải từ Release khi cần:

- `server/data/` — tài nguyên game (~732MB): ảnh, hiệu ứng, map, mob, icon…
- `client/Library/` — cache Unity (~1.3GB, Unity tự sinh lại khi mở project)
- Bản build: `APK_srcnrofree.online.apk`, `IPA_srcnrofree.online.ipa`, `PC_srcnrofree.online.rar`
- 16 bản backup web cũ khác (trong `sourceweb.rar`) và folder `LÂU CỒ MOD`

## Bắt đầu nhanh

- **Chạy server**: xem [`docs/SETUP.md`](docs/SETUP.md#server) — nạp `server/database_team2026.sql`, sửa `server/Config.properties`, build bằng Ant rồi `java -jar 20.jar`.
- **Mở client**: mở thư mục `client/` bằng Unity **2022.3.62f2**.
- **Chạy web**: đặt `web/` lên PHP + MySQL, sửa `web/cauhinh.php` (thông tin DB).

Xem chi tiết kiến trúc và cách sửa/thêm tính năng trong [`docs/`](docs/).

## Nhánh phát triển

Các thay đổi được phát triển trên nhánh `claude/game-update-support-erdkdq` và tạo Pull Request vào `main`.
