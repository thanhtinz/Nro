package nro.models.server;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import nro.models.data.LocalManager;
import nro.models.boss.Boss_Manager.BossManager;
import nro.models.services.Service;
import nro.models.utils.Logger;

/**
 * Cầu nối Web Admin -> Server game.
 *
 * - Đọc hàng đợi lệnh ở bảng `server_control` (do web admin ghi) và thực thi.
 * - Ghi trạng thái sống của server vào bảng `server_status` để web hiển thị.
 *
 * Cách gắn: gọi WebControlService.gI(); một lần khi server khởi động
 * (ví dụ trong ServerManager.init()). Nhớ chạy web/admin/sql/bridge.sql trước.
 *
 * Chu kỳ mặc định 3 giây.
 */
public class WebControlService extends Thread {

    private static WebControlService instance;
    private final long startMillis = System.currentTimeMillis();
    private volatile boolean running = true;

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
        Logger.success("WebControlService started (cầu nối web admin)\n");
        while (running) {
            try {
                writeStatus();
                processCommands();
            } catch (Exception e) {
                Logger.error("WebControlService loop error: " + e.getMessage() + "\n");
            }
            try {
                Thread.sleep(3000);
            } catch (InterruptedException ignored) {
            }
        }
    }

    /** Ghi trạng thái sống của server vào bảng server_status */
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

    /** Đọc & thực thi các lệnh đang chờ */
    private void processCommands() throws Exception {
        try (Connection con = LocalManager.getConnection();
             PreparedStatement ps = con.prepareStatement(
                     "SELECT id, command, params FROM server_control WHERE status = 0 ORDER BY id ASC LIMIT 20");
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                int id = rs.getInt("id");
                String cmd = rs.getString("command");
                String params = rs.getString("params");
                String result;
                int status = 1;
                try {
                    result = execute(cmd, params);
                } catch (Exception ex) {
                    result = "Lỗi: " + ex.getMessage();
                    status = 2;
                }
                markDone(con, id, status, result);
            }
        }
    }

    private void markDone(Connection con, int id, int status, String result) throws Exception {
        try (PreparedStatement ps = con.prepareStatement(
                "UPDATE server_control SET status = ?, result = ?, processed_at = NOW() WHERE id = ?")) {
            ps.setInt(1, status);
            ps.setString(2, result != null && result.length() > 500 ? result.substring(0, 500) : result);
            ps.setInt(3, id);
            ps.executeUpdate();
        }
    }

    /** Ánh xạ lệnh -> hành động trong server */
    private String execute(String cmd, String params) throws Exception {
        if (cmd == null) return "Lệnh rỗng";
        switch (cmd) {
            case "notify_all": {
                String text = params == null ? "" : params;
                Service.gI().sendThongBaoAllPlayer(text);
                return "Đã gửi thông báo tới tất cả người chơi";
            }
            case "set_exp": {
                int val = Integer.parseInt(params.trim());
                if (val < 1) val = 1;
                if (val > 127) val = 127;
                Manager.RATE_EXP_SERVER = (byte) val;
                return "Đã đặt hệ số EXP = " + val;
            }
            case "reset_boss": {
                BossManager.gI().loadBoss();
                return "Đã nạp lại / reset boss";
            }
            case "reset_rank": {
                int n = resetRank();
                return "Đã reset bảng xếp hạng (" + n + " dòng)";
            }
            case "maintenance": {
                int seconds = 60;
                try { if (params != null && !params.isEmpty()) seconds = Integer.parseInt(params.trim()); } catch (Exception ignored) {}
                Maintenance.gI().startSeconds(seconds);
                return "Đã bật bảo trì, đếm ngược " + seconds + "s";
            }
            case "restart": {
                new Thread(() -> {
                    try { Thread.sleep(1500); } catch (InterruptedException ignored) {}
                    ServerManager.gI().close();
                }).start();
                return "Đang khởi động lại server...";
            }
            default:
                return "Không hỗ trợ lệnh: " + cmd;
        }
    }

    /** Reset bảng xếp hạng super_rank; trả về số dòng bị xoá */
    private int resetRank() throws Exception {
        try (Connection con = LocalManager.getConnection();
             PreparedStatement ps = con.prepareStatement("DELETE FROM super_rank")) {
            return ps.executeUpdate();
        }
    }
}
