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
  ('do_restart', '0'),
  ('do_reload_shop', '0');

INSERT IGNORE INTO `server_status` (`sv_key`, `sv_value`) VALUES
  ('online_players', '0'), ('rate_exp', '1'), ('maintenance', '0'),
  ('last_heartbeat', '0'), ('uptime', '0'), ('events', '');

-- Danh sách máy chủ hiển thị cho người chơi khi đăng nhập (server tự áp dụng vào DataGame.LINK_IP_PORT).
-- Lưu ý: đây chỉ là ENTRY danh sách; tiến trình game của máy chủ mới phải deploy/chạy riêng.
CREATE TABLE IF NOT EXISTS `server_list` (
  `id`      INT(11) NOT NULL AUTO_INCREMENT,
  `name`    VARCHAR(50)  NOT NULL,
  `ip`      VARCHAR(100) NOT NULL,
  `port`    INT(11)      NOT NULL,
  `enabled` TINYINT(1)   NOT NULL DEFAULT 1,
  `sort`    INT(11)      NOT NULL DEFAULT 0,
  `note`    VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Phúc lợi: kho quà bùa miễn phí hằng ngày (Bà Hạt Mít). Server bốc ngẫu nhiên 1 dòng enabled.
CREATE TABLE IF NOT EXISTS `daily_gift_reward` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `item_id`      INT(11)      NOT NULL,          -- id vật phẩm (bùa)
  `duration_min` INT(11)      NOT NULL DEFAULT 60, -- thời hạn (phút)
  `enabled`      TINYINT(1)   NOT NULL DEFAULT 1,
  `note`         VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mặc định giống code cũ: bùa 213..219, thời hạn 60 phút
INSERT IGNORE INTO `daily_gift_reward` (`id`,`item_id`,`duration_min`,`enabled`,`note`) VALUES
  (1,213,60,1,'Bùa mặc định'),(2,214,60,1,'Bùa mặc định'),(3,215,60,1,'Bùa mặc định'),
  (4,216,60,1,'Bùa mặc định'),(5,217,60,1,'Bùa mặc định'),(6,218,60,1,'Bùa mặc định'),
  (7,219,60,1,'Bùa mặc định');

-- Lịch hoạt động: admin đặt giờ + hành động; server tự chạy mỗi ngày
CREATE TABLE IF NOT EXISTS `server_schedule` (
  `id`       INT(11) NOT NULL AUTO_INCREMENT,
  `run_time` CHAR(5)      NOT NULL,            -- 'HH:MM' (giờ VN)
  `action`   VARCHAR(30)  NOT NULL,            -- notify, reset_boss, reset_rank, event_on, event_off, maintenance
  `params`   VARCHAR(500) DEFAULT NULL,        -- vd nội dung thông báo, hoặc tên sự kiện
  `enabled`  TINYINT(1)   NOT NULL DEFAULT 1,
  `last_run` DATE         DEFAULT NULL,         -- ngày chạy gần nhất (server ghi)
  `note`     VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_enabled_time` (`enabled`, `run_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
