package nro.models.server;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.time.LocalDate;
import java.time.LocalTime;
import java.time.format.DateTimeFormatter;
import java.util.HashMap;
import java.util.Map;
import java.util.ArrayList;
import java.util.List;
import nro.models.data.LocalManager;
import nro.models.database.ShopDAO;
import nro.models.boss.Boss_Manager.BossManager;
import nro.models.services.Service;
import nro.models.event.EventManager;
import nro.models.daily_Giftcode.DailyGiftConfig;
import nro.models.data.DataGame;
import nro.models.utils.Logger;

/**
 * Đồng bộ cấu hình Web Admin -> Server game (config-sync).
 *
 * Admin chỉ CHỈNH giá trị trong bảng `server_config`; luồng này đọc mỗi vài giây
 * và ÁP DỤNG ngay vào server đang chạy (không cần "gửi lệnh"):
 *   - setting (rate_exp, maintenance, event_*): đặt = trạng thái mong muốn.
 *   - do_* / notify_seq: giá trị tăng dần (timestamp) để kích hoạt hành động 1 lần.
 * Đồng thời ghi trạng thái sống vào `server_status` để web hiển thị.
 *
 * Cách gắn: gọi WebControlService.gI(); một lần trong ServerManager.init().
 * Nhớ chạy web/admin/sql/bridge.sql trước.
 */
public class WebControlService extends Thread {

    private static WebControlService instance;
    private final long startMillis = System.currentTimeMillis();
    private volatile boolean running = true;

    /** Giá trị lần cuối đã xử lý của các khoá kích hoạt (do_*, notify_seq) */
    private final Map<String, String> lastSeen = new HashMap<>();
    private boolean initialized = false;

    private static final String[] EVENTS = {
        "LUNNAR_NEW_YEAR", "INTERNATIONAL_WOMANS_DAY", "CHRISTMAS",
        "HALLOWEEN", "HUNG_VUONG", "TRUNG_THU", "TOP_UP"
    };
    private static final String[] TRIGGERS = {
        "do_reset_boss", "do_reset_rank", "do_restart", "notify_seq", "do_reload_shop", "do_reload_data"
    };

    private WebControlService() {
        this.setName("WebControlService");
        this.start();
    }

    public static WebControlService gI() {
        if (instance == null) {
            instance = new WebControlService();
        }
        return instance;
    }

    public void stopService() {
        this.running = false;
    }

    @Override
    public void run() {
        Logger.success("WebControlService started (config-sync web admin)\n");
        while (running) {
            try {
                Map<String, String> cfg = readConfig();
                applySettings(cfg);
                applyTriggers(cfg);
                loadWelfare();
                syncServerList();
                GiftPackageService.deliverToOnline();
                checkSchedule();
                writeStatus();
            } catch (Exception e) {
                Logger.error("WebControlService loop error: " + e.getMessage() + "\n");
            }
            try {
                Thread.sleep(3000);
            } catch (InterruptedException ignored) {
            }
        }
    }

    private Map<String, String> readConfig() throws Exception {
        Map<String, String> cfg = new HashMap<>();
        try (Connection con = LocalManager.getConnection();
             PreparedStatement ps = con.prepareStatement("SELECT cfg_key, cfg_value FROM server_config");
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                cfg.put(rs.getString("cfg_key"), rs.getString("cfg_value"));
            }
        }
        return cfg;
    }

    /** Áp dụng các cấu hình trạng thái (idempotent) */
    private void applySettings(Map<String, String> cfg) {
        // Hệ số EXP
        String exp = cfg.get("rate_exp");
        if (exp != null) {
            try {
                int v = Integer.parseInt(exp.trim());
                if (v < 1) v = 1; if (v > 127) v = 127;
                Manager.RATE_EXP_SERVER = (byte) v;
            } catch (NumberFormatException ignored) {}
        }
        // Sự kiện
        for (String ev : EVENTS) {
            String val = cfg.get("event_" + ev);
            if (val != null) setEvent(ev, val.trim().equals("1"));
        }
        // Bảo trì: đặt 1 -> bắt đầu bảo trì (nếu chưa chạy)
        String mt = cfg.get("maintenance");
        if (mt != null && mt.trim().equals("1") && !Maintenance.isRunning) {
            Maintenance.gI().startSeconds(60);
        }
    }

    /** Xử lý các khoá kích hoạt 1 lần (khi giá trị thay đổi so với lần trước) */
    private void applyTriggers(Map<String, String> cfg) {
        // Lần đầu: ghi nhận giá trị hiện tại, KHÔNG kích hoạt (tránh chạy lại khi server khởi động)
        if (!initialized) {
            for (String k : TRIGGERS) lastSeen.put(k, cfg.getOrDefault(k, "0"));
            initialized = true;
            return;
        }
        for (String key : TRIGGERS) {
            String cur = cfg.getOrDefault(key, "0");
            String prev = lastSeen.getOrDefault(key, "0");
            if (cur != null && !cur.equals(prev)) {
                lastSeen.put(key, cur);
                try {
                    fireTrigger(key, cfg);
                } catch (Exception ex) {
                    Logger.error("Trigger " + key + " lỗi: " + ex.getMessage() + "\n");
                }
            }
        }
    }

    private void fireTrigger(String key, Map<String, String> cfg) throws Exception {
        switch (key) {
            case "do_reset_boss":
                BossManager.gI().loadBoss();
                Logger.success("[WebAdmin] Reset boss\n");
                break;
            case "do_reset_rank":
                resetRank();
                Logger.success("[WebAdmin] Reset bảng xếp hạng\n");
                break;
            case "do_restart":
                new Thread(() -> {
                    try { Thread.sleep(1500); } catch (InterruptedException ignored) {}
                    ServerManager.gI().close();
                }).start();
                Logger.success("[WebAdmin] Restart server\n");
                break;
            case "notify_seq":
                String text = cfg.getOrDefault("notify_text", "");
                if (text != null && !text.isEmpty()) {
                    Service.gI().sendThongBaoAllPlayer(text);
                    Logger.success("[WebAdmin] Thông báo: " + text + "\n");
                }
                break;
            case "do_reload_shop":
                try (Connection con = LocalManager.getConnection()) {
                    Manager.SHOPS = ShopDAO.getShops(con);
                    Logger.success("[WebAdmin] Nạp lại shop (" + Manager.SHOPS.size() + ")\n");
                }
                break;
            case "do_reload_data":
                Manager.reloadTemplatesFromWeb();
                break;
        }
    }

    /**
     * Đồng bộ danh sách máy chủ hiển thị cho người chơi từ bảng server_list.
     * Nếu bảng có dòng enabled -> ghi đè DataGame.LINK_IP_PORT (định dạng name:ip:port:0,...).
     * Nếu trống -> giữ nguyên giá trị nạp từ Config.properties.
     */
    private void syncServerList() throws Exception {
        StringBuilder sb = new StringBuilder();
        try (Connection con = LocalManager.getConnection();
             PreparedStatement ps = con.prepareStatement(
                     "SELECT name, ip, port FROM server_list WHERE enabled = 1 ORDER BY sort, id");
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                if (sb.length() > 0) sb.append(',');
                sb.append(rs.getString("name")).append(':')
                  .append(rs.getString("ip")).append(':')
                  .append(rs.getInt("port")).append(":0");
            }
        }
        if (sb.length() > 0) {
            DataGame.LINK_IP_PORT = sb.toString();
        }
    }

    /** Nạp kho quà bùa miễn phí hằng ngày từ bảng daily_gift_reward */
    private void loadWelfare() throws Exception {
        List<int[]> pool = new ArrayList<>();
        try (Connection con = LocalManager.getConnection();
             PreparedStatement ps = con.prepareStatement(
                     "SELECT item_id, duration_min FROM daily_gift_reward WHERE enabled = 1");
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                pool.add(new int[]{rs.getInt("item_id"), rs.getInt("duration_min")});
            }
        }
        DailyGiftConfig.setPool(pool);
    }

    /** Kiểm tra lịch hoạt động: chạy hành động đúng giờ, mỗi ngày 1 lần */
    private void checkSchedule() throws Exception {
        String nowHm = LocalTime.now().format(DateTimeFormatter.ofPattern("HH:mm"));
        String today = LocalDate.now().toString();
        try (Connection con = LocalManager.getConnection()) {
            java.util.List<int[]> ran = new java.util.ArrayList<>();
            try (PreparedStatement ps = con.prepareStatement(
                    "SELECT id, action, params FROM server_schedule "
                    + "WHERE enabled = 1 AND run_time = ? AND (last_run IS NULL OR last_run <> ?)")) {
                ps.setString(1, nowHm);
                ps.setString(2, today);
                try (ResultSet rs = ps.executeQuery()) {
                    while (rs.next()) {
                        int id = rs.getInt("id");
                        try {
                            fireScheduleAction(con, rs.getString("action"), rs.getString("params"));
                            Logger.success("[WebAdmin] Lịch #" + id + " chạy: " + rs.getString("action") + "\n");
                        } catch (Exception ex) {
                            Logger.error("Lịch #" + id + " lỗi: " + ex.getMessage() + "\n");
                        }
                        ran.add(new int[]{id});
                    }
                }
            }
            for (int[] r : ran) {
                try (PreparedStatement up = con.prepareStatement(
                        "UPDATE server_schedule SET last_run = ? WHERE id = ?")) {
                    up.setString(1, today);
                    up.setInt(2, r[0]);
                    up.executeUpdate();
                }
            }
        }
    }

    /** Thực thi 1 hành động theo lịch (setting -> ghi config để đồng bộ) */
    private void fireScheduleAction(Connection con, String action, String params) throws Exception {
        if (action == null) return;
        switch (action) {
            case "notify":
                if (params != null && !params.isEmpty()) Service.gI().sendThongBaoAllPlayer(params);
                break;
            case "reset_boss":
                BossManager.gI().loadBoss();
                break;
            case "reset_rank":
                resetRank();
                break;
            case "event_on":
                if (params != null) setConfigValue(con, "event_" + params.trim(), "1");
                break;
            case "event_off":
                if (params != null) setConfigValue(con, "event_" + params.trim(), "0");
                break;
            case "maintenance":
                setConfigValue(con, "maintenance", "1");
                break;
        }
    }

    /** Ghi 1 khoá cấu hình từ server (để lịch cập nhật server_config) */
    private void setConfigValue(Connection con, String key, String value) throws Exception {
        try (PreparedStatement ps = con.prepareStatement(
                "INSERT INTO server_config (cfg_key, cfg_value) VALUES (?, ?) "
                + "ON DUPLICATE KEY UPDATE cfg_value = VALUES(cfg_value)")) {
            ps.setString(1, key);
            ps.setString(2, value);
            ps.executeUpdate();
        }
    }

    /** Ghi trạng thái sống của server */
    private void writeStatus() throws Exception {
        int online = ServerManager.CLIENTS.size();
        long uptime = (System.currentTimeMillis() - startMillis) / 1000L;
        int maintenance = Maintenance.isRunning ? 1 : 0;
        int rateExp = Manager.RATE_EXP_SERVER;
        try (Connection con = LocalManager.getConnection()) {
            setStatus(con, "online_players", String.valueOf(online));
            setStatus(con, "uptime", String.valueOf(uptime));
            setStatus(con, "maintenance", String.valueOf(maintenance));
            setStatus(con, "rate_exp", String.valueOf(rateExp));
            setStatus(con, "events", eventStates());
            setStatus(con, "last_heartbeat", String.valueOf(System.currentTimeMillis() / 1000L));
        }
    }

    private void setStatus(Connection con, String key, String value) throws Exception {
        try (PreparedStatement ps = con.prepareStatement(
                "INSERT INTO server_status (sv_key, sv_value) VALUES (?, ?) "
                + "ON DUPLICATE KEY UPDATE sv_value = VALUES(sv_value)")) {
            ps.setString(1, key);
            ps.setString(2, value);
            ps.executeUpdate();
        }
    }

    private String eventStates() {
        StringBuilder sb = new StringBuilder();
        for (String ev : EVENTS) {
            if (sb.length() > 0) sb.append(',');
            sb.append(ev).append(':').append(getEvent(ev) ? '1' : '0');
        }
        return sb.toString();
    }

    private int resetRank() throws Exception {
        try (Connection con = LocalManager.getConnection();
             PreparedStatement ps = con.prepareStatement("DELETE FROM super_rank")) {
            return ps.executeUpdate();
        }
    }

    private boolean getEvent(String key) {
        switch (key) {
            case "LUNNAR_NEW_YEAR": return EventManager.LUNNAR_NEW_YEAR;
            case "INTERNATIONAL_WOMANS_DAY": return EventManager.INTERNATIONAL_WOMANS_DAY;
            case "CHRISTMAS": return EventManager.CHRISTMAS;
            case "HALLOWEEN": return EventManager.HALLOWEEN;
            case "HUNG_VUONG": return EventManager.HUNG_VUONG;
            case "TRUNG_THU": return EventManager.TRUNG_THU;
            case "TOP_UP": return EventManager.TOP_UP;
            default: return false;
        }
    }

    private void setEvent(String key, boolean on) {
        switch (key) {
            case "LUNNAR_NEW_YEAR": EventManager.LUNNAR_NEW_YEAR = on; break;
            case "INTERNATIONAL_WOMANS_DAY": EventManager.INTERNATIONAL_WOMANS_DAY = on; break;
            case "CHRISTMAS": EventManager.CHRISTMAS = on; break;
            case "HALLOWEEN": EventManager.HALLOWEEN = on; break;
            case "HUNG_VUONG": EventManager.HUNG_VUONG = on; break;
            case "TRUNG_THU": EventManager.TRUNG_THU = on; break;
            case "TOP_UP": EventManager.TOP_UP = on; break;
        }
    }
}
