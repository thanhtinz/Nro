<?php
/**
 * File công cụ admin cũ (không bảo mật) đã bị VÔ HIỆU HOÁ vì lý do an ninh.
 * Chức năng đã được thay bằng panel admin có xác thực tại /admin/.
 * Xem lịch sử git để lấy lại nội dung gốc nếu cần.
 */
http_response_code(403);
header("Location: /admin/login.php", true, 302);
exit("403 - Da vo hieu hoa. Vui long dung /admin/.");
