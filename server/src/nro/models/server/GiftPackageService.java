package nro.models.server;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import nro.models.data.LocalManager;
import nro.models.item.Item;
import nro.models.npc.NpcFactory;
import nro.models.player.Player;
import nro.models.services.ItemService;
import nro.models.services.InventoryService;
import nro.models.services.Service;
import nro.models.map.service.NpcService;
import nro.models.utils.Logger;

/**
 * Hòm thư trong game — gửi gói quà từ web admin, người chơi MỞ ĐỌC rồi BẤM NHẬN.
 *
 * - Web admin gửi gói -> ghi vào gift_mail / gift_mail_item.
 * - WebControlService gọi promptOnline() mỗi ~3s: người chơi online có thư CHƯA nhận
 *   sẽ được tự mở menu "Hòm thư" (giới hạn nhắc mỗi 2 phút để không làm phiền).
 * - Người chơi chọn thư -> đọc nội dung + xem quà -> bấm "Nhận quà" mới cộng vào túi.
 *
 * Dùng menu CON_MEO (server-driven) nên KHÔNG cần sửa client. 2 case menu được
 * thêm trong NpcFactory (CON_MEO.confirmMenu): IDX_MAILBOX_LIST, IDX_MAILBOX_VIEW.
 *
 * item_id: -1=vàng, -2=ngọc, -3=ngọc khoá; >=0 = item template id.
 */
public class GiftPackageService {

    public static final int IDX_MAILBOX_LIST = 770001;
    public static final int IDX_MAILBOX_VIEW = 770002;
    private static final int AVATAR = 1139;

    /** Lần cuối nhắc hòm thư cho mỗi người (chống spam) */
    private static final Map<Long, Long> lastPrompt = new HashMap<>();

    // ================= ENTRY: tự bật hòm thư =================

    public static void promptOnline() {
        List<Player> online;
        try { online = Client.gI().getPlayers(); } catch (Exception e) { return; }
        if (online == null || online.isEmpty()) return;
        long now = System.currentTimeMillis();
        try (Connection con = LocalManager.getConnection()) {
            for (Player p : online) {
                if (p == null) continue;
                Long last = lastPrompt.get(p.id);
                if (last != null && now - last < 120000L) continue; // 2 phút
                if (countUnclaimed(con, (int) p.id) > 0) {
                    lastPrompt.put(p.id, now);
                    try { openMailbox(p); } catch (Exception ignored) {}
                }
            }
        } catch (Exception e) {
            Logger.error("GiftPackage.promptOnline: " + e.getMessage() + "\n");
        }
    }

    private static int countUnclaimed(Connection con, int pid) throws Exception {
        try (PreparedStatement ps = con.prepareStatement(
                "SELECT COUNT(*) FROM gift_mail m WHERE m.enabled = 1 "
                + "AND NOT EXISTS (SELECT 1 FROM gift_mail_received r WHERE r.mail_id = m.id AND r.player_id = ?)")) {
            ps.setInt(1, pid);
            try (ResultSet rs = ps.executeQuery()) { return rs.next() ? rs.getInt(1) : 0; }
        }
    }

    // ================= HIỂN THỊ =================

    /** Mở danh sách thư chưa nhận */
    public static void openMailbox(Player player) {
        try (Connection con = LocalManager.getConnection()) {
            List<int[]> ids = new ArrayList<>();   // [mailId]
            List<String> labels = new ArrayList<>();
            try (PreparedStatement ps = con.prepareStatement(
                    "SELECT m.id, m.title FROM gift_mail m WHERE m.enabled = 1 "
                    + "AND NOT EXISTS (SELECT 1 FROM gift_mail_received r WHERE r.mail_id = m.id AND r.player_id = ?) "
                    + "ORDER BY m.id DESC LIMIT 20")) {
                ps.setInt(1, (int) player.id);
                try (ResultSet rs = ps.executeQuery()) {
                    while (rs.next()) {
                        ids.add(new int[]{rs.getInt("id")});
                        labels.add(rs.getString("title"));
                    }
                }
            }
            if (ids.isEmpty()) {
                Service.gI().sendThongBao(player, "Hòm thư trống.");
                return;
            }
            int[] mailIds = new int[ids.size()];
            for (int i = 0; i < ids.size(); i++) mailIds[i] = ids.get(i)[0];
            String[] opts = new String[labels.size() + 1];
            for (int i = 0; i < labels.size(); i++) opts[i] = labels.get(i);
            opts[labels.size()] = "Đóng";
            NpcService.gI().createMenuConMeo(player, IDX_MAILBOX_LIST, AVATAR,
                    "Hòm thư của bạn (" + mailIds.length + " thư mới):", opts, mailIds);
        } catch (Exception e) {
            Logger.error("openMailbox: " + e.getMessage() + "\n");
        }
    }

    /** Mở 1 thư: nội dung + danh sách quà + nút Nhận */
    private static void openMail(Player player, int mailId) {
        try (Connection con = LocalManager.getConnection()) {
            String title = "", content = "";
            try (PreparedStatement ps = con.prepareStatement("SELECT title, content FROM gift_mail WHERE id=?")) {
                ps.setInt(1, mailId);
                try (ResultSet rs = ps.executeQuery()) {
                    if (rs.next()) { title = rs.getString("title"); content = rs.getString("content"); }
                }
            }
            StringBuilder sb = new StringBuilder();
            sb.append(title == null ? "" : title).append("\b");
            if (content != null && !content.isEmpty()) sb.append(content).append("\b");
            sb.append("Phần quà:\b");
            try (PreparedStatement ps = con.prepareStatement(
                    "SELECT item_id, quantity FROM gift_mail_item WHERE mail_id=?")) {
                ps.setInt(1, mailId);
                try (ResultSet rs = ps.executeQuery()) {
                    while (rs.next()) {
                        int id = rs.getInt("item_id"), q = rs.getInt("quantity");
                        sb.append("- ").append(q).append(' ').append(itemLabel(id)).append("\b");
                    }
                }
            }
            NpcService.gI().createMenuConMeo(player, IDX_MAILBOX_VIEW, AVATAR, sb.toString(),
                    new String[]{"Nhận quà", "Đóng"}, Integer.valueOf(mailId));
        } catch (Exception e) {
            Logger.error("openMail: " + e.getMessage() + "\n");
        }
    }

    private static String itemLabel(int id) {
        switch (id) {
            case -1: return "vàng";
            case -2: return "ngọc";
            case -3: return "ngọc khoá";
            default:
                try {
                    Item it = new Item((short) id);
                    if (it.template != null) return it.template.name;
                } catch (Exception ignored) {}
                return "vật phẩm #" + id;
        }
    }

    // ================= XỬ LÝ CHỌN (gọi từ NpcFactory CON_MEO) =================

    public static void onSelectList(Player player, int select) {
        Object obj = NpcFactory.PLAYERID_OBJECT.get(player.id);
        if (!(obj instanceof int[] mailIds)) { return; }
        if (select >= 0 && select < mailIds.length) {
            openMail(player, mailIds[select]);
        }
        // select == length => "Đóng": không làm gì
    }

    public static void onSelectView(Player player, int select) {
        Object obj = NpcFactory.PLAYERID_OBJECT.get(player.id);
        if (!(obj instanceof Integer mailId)) { return; }
        if (select == 0) {
            claim(player, mailId);
            openMailbox(player); // mở lại để nhận tiếp thư khác (nếu còn)
        }
    }

    // ================= NHẬN QUÀ =================

    private static void claim(Player player, int mailId) {
        try (Connection con = LocalManager.getConnection()) {
            int pid = (int) player.id;
            if (alreadyReceived(con, mailId, pid)) {
                Service.gI().sendThongBao(player, "Bạn đã nhận thư này rồi.");
                return;
            }
            if (!markReceived(con, mailId, pid)) return; // đua -> đã nhận
            deliverItems(con, mailId, player);
        } catch (Exception e) {
            Logger.error("claim mail " + mailId + ": " + e.getMessage() + "\n");
        }
    }

    private static boolean alreadyReceived(Connection con, int mailId, int pid) throws Exception {
        try (PreparedStatement ps = con.prepareStatement(
                "SELECT 1 FROM gift_mail_received WHERE mail_id=? AND player_id=? LIMIT 1")) {
            ps.setInt(1, mailId); ps.setInt(2, pid);
            try (ResultSet rs = ps.executeQuery()) { return rs.next(); }
        }
    }

    private static boolean markReceived(Connection con, int mailId, int pid) {
        try (PreparedStatement ps = con.prepareStatement(
                "INSERT INTO gift_mail_received (mail_id, player_id) VALUES (?, ?)")) {
            ps.setInt(1, mailId); ps.setInt(2, pid);
            ps.executeUpdate();
            return true;
        } catch (Exception e) { return false; }
    }

    private static void deliverItems(Connection con, int mailId, Player player) throws Exception {
        List<int[]> items = new ArrayList<>(); // [itemId, qty, optId(-9999=none), optParam]
        try (PreparedStatement ps = con.prepareStatement(
                "SELECT item_id, quantity, option_id, option_param FROM gift_mail_item WHERE mail_id=?")) {
            ps.setInt(1, mailId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    int optId = rs.getInt("option_id");
                    boolean hasOpt = !rs.wasNull();
                    items.add(new int[]{rs.getInt("item_id"), rs.getInt("quantity"),
                            hasOpt ? optId : -9999, rs.getInt("option_param")});
                }
            }
        }
        synchronized (player) {
            StringBuilder text = new StringBuilder("|0|Bạn vừa nhận được:\b");
            for (int[] mi : items) {
                int id = mi[0], q = mi[1];
                switch (id) {
                    case -1 -> { player.inventory.gold = Math.min(player.inventory.gold + (long) q, 2000000000L); text.append("|2|").append(q).append(" vàng\b"); }
                    case -2 -> { player.inventory.gem = Math.min(player.inventory.gem + q, 200000000); text.append("|3|").append(q).append(" ngọc\b"); }
                    case -3 -> { player.inventory.ruby = Math.min(player.inventory.ruby + q, 200000000); text.append("|4|").append(q).append(" ngọc khoá\b"); }
                    default -> {
                        Item tmpl = ItemService.gI().createNewItem((short) id);
                        if (tmpl != null) {
                            Item it = new Item((short) id);
                            if (mi[2] != -9999) it.itemOptions.add(new Item.ItemOption(mi[2], mi[3]));
                            it.quantity = Math.max(1, q);
                            InventoryService.gI().addItemBag(player, it);
                            text.append("|1|x").append(q).append(' ').append(it.template.name).append("\b");
                        }
                    }
                }
            }
            try {
                InventoryService.gI().sendItemBags(player);
                Service.gI().sendThongBao(player, "Đã nhận quà từ hòm thư!");
            } catch (Exception e) {
                Service.gI().sendThongBao(player, "Đã nhận quà.");
            }
        }
    }
}
