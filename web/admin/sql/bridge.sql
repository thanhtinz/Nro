-- ============================================================
-- Cầu nối Web Admin <-> Server game (mô hình config-sync)
-- Admin chỉ CHỈNH giá trị trong server_config; server tự đọc & áp dụng.
-- Chạy 1 lần trên DB game (team2026).
-- ============================================================

-- Cấu hình runtime: admin ghi, server đọc & áp dụng liên tục
CREATE TABLE IF NOT EXISTS `server_config` (
  `cfg_key`    VARCHAR(50)  NOT NULL,
  `cfg_value`  VARCHAR(500) DEFAULT NULL,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cfg_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trạng thái sống của server (server ghi, web đọc hiển thị)
CREATE TABLE IF NOT EXISTS `server_status` (
  `sv_key`     VARCHAR(50)  NOT NULL,
  `sv_value`   VARCHAR(500) DEFAULT NULL,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sv_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Giá trị mặc định (setting = trạng thái mong muốn; do_* = kích hoạt 1 lần)
INSERT IGNORE INTO `server_config` (`cfg_key`, `cfg_value`) VALUES
  ('rate_exp', '1'),
  ('maintenance', '0'),
  ('event_LUNNAR_NEW_YEAR', '1'),
  ('event_INTERNATIONAL_WOMANS_DAY', '1'),
  ('event_CHRISTMAS', '1'),
  ('event_HALLOWEEN', '1'),
  ('event_HUNG_VUONG', '1'),
  ('event_TRUNG_THU', '1'),
  ('event_TOP_UP', '1'),
  ('notify_text', ''),
  ('notify_seq', '0'),
  ('do_reset_boss', '0'),
  ('do_reset_rank', '0'),
  ('do_restart', '0');

INSERT IGNORE INTO `server_status` (`sv_key`, `sv_value`) VALUES
  ('online_players', '0'), ('rate_exp', '1'), ('maintenance', '0'),
  ('last_heartbeat', '0'), ('uptime', '0'), ('events', '');
