package nro.models.database;

import java.io.BufferedReader;
import java.io.FileInputStream;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.Properties;
import nro.models.data.LocalResultSet;
import nro.models.data.LocalManager;
import org.json.simple.JSONObject;
import org.json.simple.JSONValue;

/**
 * Xác thực tập trung qua cổng game (Gate Game portal).
 *
 * Cấu hình trong Config.properties:
 *   auth.enabled=true
 *   auth.url=http://127.0.0.1:8080/api/game-auth/verify
 *   auth.key=<central_auth_key trong admin cổng game>
 *
 * Luồng: đăng nhập game -> hỏi cổng.
 *   OK             -> đồng bộ dòng account trong DB game (tạo nếu chưa có) rồi đăng nhập.
 *   WRONG/LOCKED/UNVERIFIED -> từ chối ngay (tài khoản thuộc cổng / chưa xác minh email).
 *   NOT_FOUND      -> tài khoản cũ chưa có trên cổng: fallback kiểm tra DB game như cũ.
 *   ERROR/DISABLED -> cổng tắt/lỗi: fallback DB game để không chặn người chơi.
 */
public final class CentralAuth {

    public static final int OK = 0;
    public static final int WRONG_PASSWORD = 1;
    public static final int LOCKED = 2;
    public static final int NOT_FOUND = 3;
    public static final int UNVERIFIED = 6;
    public static final int DISABLED = 4;
    public static final int ERROR = 5;

    private static boolean enabled = false;
    private static String url = "";
    private static String key = "";
    private static boolean loaded = false;

    private CentralAuth() {
    }

    private static synchronized void loadConfig() {
        if (loaded) {
            return;
        }
        loaded = true;
        try (InputStream is = new FileInputStream("Config.properties")) {
            Properties p = new Properties();
            p.load(is);
            enabled = "true".equalsIgnoreCase(p.getProperty("auth.enabled", "false"));
            url = p.getProperty("auth.url", "").trim();
            key = p.getProperty("auth.key", "").trim();
            if (url.isEmpty() || key.isEmpty()) {
                enabled = false;
            }
        } catch (Exception e) {
            enabled = false;
        }
    }

    /** Gọi API cổng để xác thực. Không ném exception. */
    public static int verify(String username, String password) {
        loadConfig();
        if (!enabled) {
            return DISABLED;
        }
        HttpURLConnection conn = null;
        try {
            conn = (HttpURLConnection) new URL(url).openConnection();
            conn.setRequestMethod("POST");
            conn.setConnectTimeout(3000);
            conn.setReadTimeout(5000);
            conn.setDoOutput(true);
            conn.setRequestProperty("X-Auth-Key", key);
            conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
            String body = "username=" + URLEncoder.encode(username, "UTF-8")
                    + "&password=" + URLEncoder.encode(password, "UTF-8");
            try (OutputStream os = conn.getOutputStream()) {
                os.write(body.getBytes(StandardCharsets.UTF_8));
            }
            int status = conn.getResponseCode();
            InputStream is = status >= 400 ? conn.getErrorStream() : conn.getInputStream();
            StringBuilder sb = new StringBuilder();
            try (BufferedReader br = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8))) {
                String line;
                while ((line = br.readLine()) != null) {
                    sb.append(line);
                }
            }
            Object parsed = JSONValue.parse(sb.toString());
            if (!(parsed instanceof JSONObject)) {
                return ERROR;
            }
            String code = String.valueOf(((JSONObject) parsed).get("code"));
            switch (code) {
                case "ok":
                    return OK;
                case "wrong_password":
                    return WRONG_PASSWORD;
                case "locked":
                    return LOCKED;
                case "not_found":
                    return NOT_FOUND;
                case "unverified":
                    return UNVERIFIED;
                default:
                    return ERROR;
            }
        } catch (Exception e) {
            return ERROR;
        } finally {
            if (conn != null) {
                conn.disconnect();
            }
        }
    }

    /**
     * Đồng bộ dòng account trong DB game sau khi cổng xác thực OK:
     * tạo mới nếu chưa có, cập nhật password để câu SELECT username+password cũ khớp.
     */
    public static boolean syncLocalAccount(String username, String password) {
        LocalResultSet rs = null;
        try {
            rs = LocalManager.executeQuery("select id from account where username = ? limit 1", username);
            if (rs.first()) {
                LocalManager.executeUpdate("update account set password = ? where username = ?", password, username);
            } else {
                LocalManager.executeUpdate(
                        "insert into account (username, password, email, token, xsrf_token, newpass) values (?, ?, '', '', '', '')",
                        username, password);
            }
            return true;
        } catch (Exception e) {
            return false;
        } finally {
            if (rs != null) {
                rs.dispose();
            }
        }
    }
}
