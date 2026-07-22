<?php
session_start();
include_once '../connect.php';
include_once '../forum_data.php';
include_once '../data_nap_the.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Trang Chủ - Chú Bé Rồng Onlines - Ngọc Rồng Online</title>
    <meta name="keywords" content="Chú Bé Rồng Online,ngoc rong mobile, game ngoc rong, game 7 vien ngoc rong, game bay vien ngoc rong" />
    <meta name="description" content="Website chính thức của Chú Bé Rồng Online – Game Bay Vien Ngọc Rồng Mobile nhập vai trực tuyến trên máy tính và điện thoại về Game 7 Viên Ngọc Rồng hấp dẫn nhất hiện nay!" />
    <meta http-equiv="refresh" content="600" />
    <meta name="robots" content="INDEX,FOLLOW" />

    <link rel="apple-touch-icon" href="/images/favicon-48x48.ico" />
    <link rel="icon" href='/images/favicon-48x48.ico' type="image/x-icon" />
    <link rel="shortcut icon" href='/images/favicon-48x48.ico' type="image/x-icon" />
    <link rel="icon" href="/images/favicon-48x48.ico">
    <link rel="icon" type="image/png" href="/images/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/images/favicon-64x64.png" sizes="64x64">
    <link rel="icon" type="image/png" href="/images/favicon-128x128.png" sizes="128x128">
    <link rel="icon" type="image/png" href="/images/favicon-48x48.png" sizes="48x48">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/view/static/css/template.css?v=1.10">
    <link rel="stylesheet" href="/view/static/css/eff.css?v=1.00">
    <link rel="stylesheet" href="/view/static/css/w3.css?v=1.01">
    <link rel="stylesheet" href="/view/static/css/styleSheet.css?v=1.1">
	<link rel="stylesheet" href="/view/static/css/forum.css?v=1.1">
	<script src="/view/static/js/disable_devtools.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY_HERE"></script>
</head>

<body>
    <div class="snowEffect">
        <canvas id="snowcanvas" height="100%" width="100%"></canvas>
    </div>

    <div style="position: relative;" class="body_body">
        <a href="#" id="backTop"><img id='backTopimg' src='/images/favicon-32x32.png' alt='top' /> </a>

        <div class="div-12">
            <img height=12 src="/images/12.png" style="vertical-align: middle;" />
            <span style="vertical-align: middle;">Dành cho người chơi trên 12 tuổi. Chơi quá 180 phút mỗi ngày sẽ hại sức khỏe.
            </span>
        </div>
        <div class="left_top"></div>
        <div class="bg_top">
            <div class="right_top"></div>
        </div>
        <div class="body-content">
            <div class="bg-content2">
                <h1 class="a">
                    <a href="/" title="game bảy viên Chú Bé Rồng Online">
                        <img height=90 src="/images/logo_sk_he.png" alt="game bảy viên Chú Bé Rồng Online" /></a>
                </h1>
                <div id="top">
                    <div class="link-more">
                        <div class="h">
                            <div class="bg_noel"></div>
                            <div class="h">
                                <div class="menu2">
                                    <table width="100%" cellspacing="4">
                                        <tr class="menu">
                                            <td>
                                                <a href="/Trang-Chu">Trang Chủ</a>
                                            </td>
                                            <td>
                                                <a href="/Gioi-Thieu">Giới Thiệu</a>
                                            </td>
                                            <td>
                                                <a href="https://103.162.30.23/forum" title="Diễn Đàn">Diễn Đàn</a>
                                            </td>
                                            <td>
                                                              <a href="https://zalo.me/g/atqsvzxmfalbhc3n4d7d" target="_blank">Box Zalo</a>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    var currentUrl = window.location.pathname;
                                    document.querySelectorAll(".menu a").forEach(function(link) {
                                        var linkHref = link.getAttribute("href");
                                        if (linkHref === "/Trang-Chu" && currentUrl === "/") {
                                            document.querySelector("#selected")?.removeAttribute("id");
                                            link.parentElement.id = "selected";
                                        }
                                        else if (linkHref === currentUrl) {
                                            document.querySelector("#selected")?.removeAttribute("id");
                                            link.parentElement.id = "selected";
                                        }
                                    });
                                });
                            </script>
                            <div class="body">
                                <div class="box_inputboxx" style="width:100%">
                                    <?php if ($is_logged_in) : ?>
                                        <div id="user-info" style="color:white; text-align:center; padding: 10px; background-color: #f38500; border-radius: 8px;">
                                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" class="user-avatar" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 10px;">
                                            <div class="user-details"> <br>
                                                <span style="font-weight: bold;">Xin chào: <?php echo htmlspecialchars($display_player_name); ?></span><br>
                                                <span style="white-space: nowrap; color: yellow; font-weight: bold;">Số dư: <?php echo number_format($user_vnd, 0, ',', '.'); ?> VND</span><br>
                                                <a href="/app/change-password" style="color: cyan;">Đổi mật khẩu</a> <br>
                                                <a href="/app/logout" style="color: cyan;">Đăng xuất</a> <br>

                                                <div class="center-buttons">
                                                    <div style="display: flex; justify-content: center; align-items: center; margin-top: 5px;">
                                                        <!-- <a href="/app/nap-vang" style="color: cyan; transform: translateX(-21px); display: inline-block;">Nạp Vàng</a>
                                                        <a href="/app/nap-ngoc" style="color: cyan; margin-left: 0px;">Nạp Tiền</a> -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <div class="box_button_login" style="width:100%; position: relative; text-align:center;">
                                            <a id="tab-login-btn" href="/app/login">
                                                <button class="w3-button w3-red w3-small w3-hover-green">Đăng nhập</button>
                                            </a>
                                            <a id="tab-register-btn" href="/app/register">
                                                <button class="w3-button w3-blue w3-small w3-hover-green">Đăng ký</button>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <br>
                                <div class="body">
                                    <table width="100%" border="0" cellspacing="0">
                                        <tbody>
                                            <tr class="menu1">
                                                <td id="recharge-selected-tab" style="width:50%; background-color: #ff5601;">Nạp Tiền Tự Động</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                                                        <div style="text-align:center; margin: 10px 0 20px;">
                                        <span style="display:inline-block; padding:10px 20px; background-color:#ff5601; color:#fff; border-radius:4px; font-weight:bold;">Chuyển Khoản Ngân Hàng (SePay)</span>
                                    </div>

                                    <div id="transferSection">
    <div class="box_list_chuyenmuc">
        <div class="box_midss">
            <div class="box_detai" style="padding:5px;">
                <center>
                    <b style="color:green">Thông tin chuyển khoản</b>
                    <br>
                    <div id="transfer_error" style="color:red;"></div>
                </center>
                <div style="max-width:520px; margin:0 auto; border:1px solid #ccc; border-radius:8px; overflow:hidden; box-shadow:0px 4px 10px rgba(0,0,0,0.1);">
                    <div style="padding:12px; background:#f5f5f5; text-align:center;">
                        <label style="font-weight:bold;">Chọn số tiền nạp:</label>
                        <select id="napAmount" style="border-radius:4px; border:1px solid #CCC; padding:4px; margin-left:6px;">
                            <option value="10000">10.000</option>
                            <option value="20000">20.000</option>
                            <option value="50000" selected>50.000</option>
                            <option value="100000">100.000</option>
                            <option value="200000">200.000</option>
                            <option value="500000">500.000</option>
                            <option value="1000000">1.000.000</option>
                        </select>
                    </div>
                    <div style="display:flex; align-items:center; flex-wrap:wrap;">
                        <div style="flex:0 0 45%; background:#f5f5f5; text-align:center; padding:10px; min-width:180px;">
                            <img id="qrCodeImage" src="" alt="QR SePay" style="max-width:100%; height:auto;">
                        </div>
                        <div style="flex:1; padding:10px; font-family:'Times New Roman', serif; min-width:220px;">
                            <p style="margin:5px 0;"><b>Ngân Hàng:</b> <?php echo htmlspecialchars($sepay['bank_name']); ?></p>
                            <p style="margin:5px 0;"><b>Số Tài Khoản:</b> <?php echo htmlspecialchars($sepay['account_no']); ?></p>
                            <p style="margin:5px 0;"><b>Tên Tài Khoản:</b> <?php echo htmlspecialchars($sepay['account_name']); ?></p>
                            <p style="margin:5px 0;"><b>Số Tiền:</b> <span id="napAmountDisplay"></span> VNĐ</p>
                            <p style="margin:5px 0;"><b>Nội Dung:</b> <span id="transferContentDisplay"></span></p>
                            <span id="actualTransferContent" style="display:none;"></span>
                            <div style="margin-top:8px;">
                                <button type="button" onclick="copyToClipboard('<?php echo htmlspecialchars($sepay['account_no']); ?>', 'Số Tài Khoản')" style="background-color:#4CAF50; color:white; padding:5px 10px; border:none; border-radius:4px; cursor:pointer; margin:2px;">Copy Số TK</button>
                                <button type="button" onclick="copyTransferContent()" style="background-color:#4CAF50; color:white; padding:5px 10px; border:none; border-radius:4px; cursor:pointer; margin:2px;">Copy Nội Dung</button>
                            </div>
                        </div>
                    </div>
                </div>
                <p style="text-align:center; color:#555; font-size:13px; margin-top:10px;">
                    Quét mã QR hoặc chuyển khoản đúng <b>số tiền</b> và <b>nội dung</b>. Tiền sẽ được cộng tự động sau vài giây.
                </p>

<script>
    var sepayAcc = "<?php echo $sepay['account_no']; ?>";
    var sepayBank = "<?php echo $sepay['bank_code']; ?>";
    var currentUser = "<?php echo $_SESSION['username'] ?? 'guest'; ?>";
    var basePrefix = "<?php echo $sepay['prefix']; ?>";

    function napGetAmount() {
        var el = document.getElementById('napAmount');
        return el ? (parseInt(el.value, 10) || 0) : 0;
    }
    function updateTransferInfo() {
        var errEl = document.getElementById('transfer_error');
        if (!currentUser || currentUser === 'guest') {
            if (errEl) errEl.innerText = "Vui lòng đăng nhập để lấy nội dung chuyển khoản chính xác.";
        } else if (errEl) {
            errEl.innerText = "";
        }
        var content = "[" + currentUser + "] " + basePrefix;
        var amount = napGetAmount();
        document.getElementById('transferContentDisplay').innerText = content;
        document.getElementById('actualTransferContent').innerText = content;
        document.getElementById('napAmountDisplay').innerText = amount.toLocaleString('vi-VN');
        var qr = "https://qr.sepay.vn/img?acc=" + encodeURIComponent(sepayAcc)
               + "&bank=" + encodeURIComponent(sepayBank)
               + "&amount=" + amount
               + "&des=" + encodeURIComponent(content);
        document.getElementById('qrCodeImage').src = qr;
    }
    window.copyToClipboard = function(text, label) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function(){ notifyCopied(label, text); },
                function(){ fallbackCopy(text, label); });
        } else {
            fallbackCopy(text, label);
        }
    };
    function fallbackCopy(text, label) {
        var t = document.createElement("input");
        t.value = text; document.body.appendChild(t); t.select();
        try { document.execCommand("copy"); } catch (e) {}
        document.body.removeChild(t);
        notifyCopied(label, text);
    }
    function notifyCopied(label, text) {
        if (window.toastr) { toastr.success(label + ': ' + text, 'Đã sao chép!'); }
        else { alert('Đã copy ' + label + ': ' + text); }
    }
    function copyTransferContent() {
        copyToClipboard(document.getElementById('actualTransferContent').innerText, 'Nội Dung Chuyển Khoản');
    }
    document.addEventListener('DOMContentLoaded', function () {
        updateTransferInfo();
        var sel = document.getElementById('napAmount');
        if (sel) sel.addEventListener('change', updateTransferInfo);
    });
</script>
                </div>
            </div>
        </div>
    </div>
                                                    <br>
                                                    <hr>
                                                    <h3>Lịch Sử Chuyển Khoản</h3>
                                                    <table width="100%" border="1" cellspacing="0" style="width: 100%; border-collapse: collapse; background-color: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);">
                                                        <thead>
                                                            <tr style="background-color: #2a9d8f; color: white; font-weight: bold;">
                                                                <th style="padding: 12px; text-align: center; border-bottom: 1px solid #ddd;">Số Tiền</th>
                                                                <th style="padding: 12px; text-align: center; border-bottom: 1px solid #ddd;">Trạng Thái</th>
                                                                <th style="padding: 12px; text-align: center; border-bottom: 1px solid #ddd;">Thời Gian</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($history_bank_transfers)) : ?>
                                                                <?php foreach ($history_bank_transfers as $transfer) : ?>
                                                                    <tr style="transition: 0.3s; cursor: pointer;" onmouseover="this.style.backgroundColor='#f5f5f5'" onmouseout="this.style.backgroundColor='transparent'">
                                                                        <td style="padding: 12px; text-align: center; border-bottom: 1px solid #ddd; color: black;"><?php echo number_format($transfer['amount'], 0, ',', '.'); ?> VNĐ</td>
                                                                        <td style="padding: 12px; text-align: center; border-bottom: 1px solid #ddd; color: <?php echo ($transfer['status'] == 'Thành Công') ? '#2a9d8f' : 'red'; ?>; font-weight: bold; color: black;">
                                                                            <?php echo htmlspecialchars($transfer['status']); ?>
                                                                        </td>
                                                                        <td style="padding: 12px; text-align: center; border-bottom: 1px solid #ddd;">
                                                                            <?php echo htmlspecialchars($transfer['created_at']); ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else : ?>
                                                                <tr>
                                                                    <td colspan="3" style="padding: 12px; text-align: center; color: black;">Chưa có lịch sử chuyển khoản.</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                    <div id="pagination2" style="text-align:center; padding:15px 0;">
                                                        <?php for ($i = 1; $i <= $total_pages_transfer; $i++) : ?>
                                                            <a href="?page_transfer=<?php echo $i; ?>&tab=transfer" class="pagination-link" data-type="transfer" style="display: inline-block; padding: 6px 12px; margin-right: 4px; text-decoration: none; border: 1px solid #2a9d8f; border-radius: 44px; transition: 0.3s; <?php echo ($i == $current_page_transfer) ? 'background:#2a9d8f; color:#fff;' : 'background:#fff; color:#2a9d8f;'; ?>" onmouseover="this.style.backgroundColor='#2a9d8f'; this.style.color='white';" onmouseout="this.style.backgroundColor='<?php echo ($i == $current_page_transfer) ? '#2a9d8f' : '#fff'; ?>'; this.style.color='<?php echo ($i == $current_page_transfer) ? '#fff' : '#2a9d8f'; ?>';">
                                                                <?php echo $i; ?>
                                                            </a>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="bg_tree"></div>
                    <div class="foot_bg"></div>
                    <div class="left_b_bottom">
                        <div class="right_b_bottom">
                            <div class="footer">
                                <div class="left_bottom"></div>
                                <div class="right_bottom"></div>
                            </div>
                            <div class="copyright" style="line-height: 7px">
                                <p>Giấy phép thiết lập Mạng Xã Hội trên mạng số: 374/GP-BTTTT <br><br>do Bộ Thông Tin và Truyền Thông cấp ngày: 07/08/2015</p>
                                Bản Quyền thuộc về Gomobi
                            </div>
                        </div>
                    </div>
                    <script src="/view/static/js/ThreeCanvas.js" type="text/javascript"></script>
                    <script src="/view/static/js/Snow3d.js" type="text/javascript"></script>
                    <script src="/view/static/js/animation.js?v5" type="text/javascript"></script>
                    <script src="/view/static/js/rocket-loader.min.js" data-cf-settings="3248e74b3f0d3f240922716b-|49" defer></script>
                    <script>
                        $(document).ready(function () {
                            // Toàn bộ thanh toán được xử lý qua SePay ở phần Nạp Tiền phía trên.
                        });
                    </script>
           </body>
</html>