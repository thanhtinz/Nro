<?php
// Phương thức nạp cũ (Momo / thẻ cào) đã ngừng — chuyển sang nạp bằng chuyển khoản SePay.
if (session_status() === PHP_SESSION_NONE) session_start();
header("Location: /app/nap-ngoc");
exit("Phuong thuc nap nay da ngung. Vui long nap bang chuyen khoan ngan hang (SePay).");
