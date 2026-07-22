<?php
// Đăng ký trên web đã tắt — chỉ đăng ký trong game.
if (session_status() === PHP_SESSION_NONE) session_start();
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng ký trong game - Ngọc Rồng Online</title>
<style>
body{margin:0;font-family:system-ui,Arial,sans-serif;background:#1a0f0a;color:#fff;
    display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center}
.card{background:#2a1a10;border:1px solid #5a3a20;border-radius:14px;padding:32px 26px;max-width:360px;margin:16px}
h1{color:#ff7a45;font-size:20px;margin:0 0 12px}
p{color:#e6d8cc;line-height:1.6}
a.btn{display:inline-block;margin-top:18px;background:#ff5601;color:#fff;text-decoration:none;
    padding:10px 20px;border-radius:8px;font-weight:600}
</style>
</head>
<body>
<div class="card">
    <h1>Đăng ký trong game</h1>
    <p>Việc tạo tài khoản chỉ thực hiện <b>trong game</b>: tải game, nhập tên tài khoản và mật khẩu ở màn hình đăng nhập lần đầu — hệ thống sẽ tự tạo tài khoản cho bạn.</p>
    <p>Website không còn hỗ trợ đăng ký.</p>
    <a class="btn" href="/app/login">← Về trang đăng nhập</a>
</div>
</body>
</html>
