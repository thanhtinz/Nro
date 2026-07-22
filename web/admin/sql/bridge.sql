-- ============================================================
-- Bảng cầu nối Web Admin <-> Server game (Phase 2)
-- Chạy 1 lần trên DB game (team2026).
-- ============================================================

-- Hàng đợi lệnh: web ghi, server đọc & thực thi
CREATE TABLE IF NOT EXISTS `server_control` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `command`      VARCHAR(50)  NOT NULL,          -- maintenance, restart, set_exp, reset_boss, reset_rank, notify_all
  `params`       VARCHAR(500) DEFAULT NULL,      -- tham số (vd giá trị exp, nội dung thông báo)
  `status`       TINYINT(1)   NOT NULL DEFAULT 0,-- 0=chờ, 1=xong, 2=lỗi
  `result`       VARCHAR(500) DEFAULT NULL,      -- server ghi kết quả
  `created_by`   VARCHAR(50)  DEFAULT NULL,      -- admin đã gửi
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trạng thái sống của server (server ghi định kỳ, web đọc để hiển thị "từ sv")
CREATE TABLE IF NOT EXISTS `server_status` (
  `sv_key`     VARCHAR(50)  NOT NULL,            -- online_players, uptime, rate_exp, maintenance, last_heartbeat
  `sv_value`   VARCHAR(500) DEFAULT NULL,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sv_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `server_status` (`sv_key`, `sv_value`) VALUES
  ('online_players', '0'),
  ('rate_exp', '1'),
  ('maintenance', '0'),
  ('last_heartbeat', '0'),
  ('uptime', '0');
