# Thêm nút "Hòm thư" cố định trên màn hình game (client Unity)

Mục tiêu: 1 nút asset cố định trên HUD, bấm vào mở UI hòm thư (menu hòm thư server-driven đã có).

## Đã làm sẵn (không cần bạn động vào)
- **Server**: opcode `-106` → mở hòm thư. Client chỉ cần gửi `Message(-106)`.
- **Client `Service.cs`** (Game1 + Game2): đã thêm hàm `Service.gI().openMailbox()` gửi `Message(-106)`.

→ Việc còn lại: **vẽ 1 nút trên `GameScr.cs`** rồi gọi `Service.gI().openMailbox()` khi bấm, và **build bằng Unity**.

> Vì mình không build được Unity ở môi trường này nên đây là hướng dẫn dán code. Làm ở tab đang dùng
> (`Game1` hoặc `Game2` — thường là tab bạn build). Các dòng dưới bám theo mẫu nút `cmdMenu` sẵn có.

## Các bước trong `Assembly-CSharp/GameX/GameScr.cs`

### 1) Khai báo biến nút (gần chỗ `public Command cmdMenu;` ~ dòng 418)
```csharp
public Command cmdMailbox;
public static Image imgMailbox;
```

### 2) Khởi tạo nút (trong hàm khởi tạo, ngay sau khối tạo `cmdMenu` ~ dòng 813–828)
```csharp
// Ảnh nút: dùng ảnh riêng nếu có (đặt vào /mainImage/), tạm dùng imgMenu nếu chưa có asset
imgMailbox = GameCanvas.loadImage("/mainImage/myTexture2dmailbox.png");
if (imgMailbox == null) imgMailbox = imgMenu;
cmdMailbox = new Command("mailbox", 11077);   // 11077 = id lệnh riêng cho hòm thư
cmdMailbox.img = imgMailbox;
cmdMailbox.w = mGraphics.getImageWidth(cmdMailbox.img) + 20;
cmdMailbox.h = mGraphics.getImageHeight(cmdMailbox.img) + 20;
cmdMailbox.isPlaySoundButton = false;
// Vị trí: góc trên phải (chỉnh x/y theo ý bạn)
cmdMailbox.x = gW - 40;
cmdMailbox.y = 50;
```

### 3) Vẽ nút (trong hàm `paint(mGraphics g)`, chỗ đang vẽ các nút HUD ~ dòng 4700+)
```csharp
if (cmdMailbox != null && cmdMailbox.img != null)
{
    g.drawImage(cmdMailbox.img, cmdMailbox.x, cmdMailbox.y, mGraphics.HCENTER | mGraphics.VCENTER);
}
```

### 4) Bắt chạm nút (chỗ xử lý pointer, ngay sau khối `cmdMenu.isPointerPressInside()` ~ dòng 2504)
```csharp
if (cmdMailbox != null && cmdMailbox.isPointerPressInside())
{
    cmdMailbox.performAction();
}
```

### 5) Xử lý lệnh khi bấm (trong `switch` lệnh, cạnh `case 11000:` ~ dòng 6479)
```csharp
case 11077:
    Service.gI().openMailbox();
    break;
```

## Build & test
1. (Tuỳ chọn) Thêm ảnh nút `myTexture2dmailbox.png` vào thư mục ảnh `mainImage` cho đẹp; không có thì tự dùng ảnh menu.
2. Mở project bằng **Unity 2022.3.62f2**, build lại client.
3. Vào game → thấy nút Hòm thư → bấm → server mở menu hòm thư → đọc → **Nhận quà**.

## Ghi chú
- `11077` chỉ là id lệnh nội bộ client (không trùng `11000/11001/11038...`). Đổi tuỳ ý nếu trùng.
- Opcode mạng là `-106` (đã chừa trống cả client lẫn server).
- Nếu muốn nút chỉ hiện khi có thư mới: có thể kiểm tra cờ do server gửi, nhưng đơn giản nhất là luôn hiện — bấm lúc nào cũng mở hòm thư (trống thì server báo "Hòm thư trống").
