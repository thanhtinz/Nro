package nro.models.server;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.List;
import nro.models.data.LocalManager;
import nro.models.item.Item;
import nro.models.player.Player;
import nro.models.services.ItemService;
import nro.models.services.InventoryService;
import nro.models.services.Service;
import nro.models.map.service.NpcService;
import nro.models.utils.Logger;

/**
 * Phát gói quà từ web admin tới người chơi (hộp quà).
 *
 * Cơ chế: WebControlService gọi deliverToOnline() mỗi ~3 giây. Với mỗi người
 * đang online, phát các lượt gửi (gift_mail enabled) mà họ CHƯA nhận
 * (không có trong gift_mail_received) — cộng vật phẩm vào túi + hiện nội dung mail.
 * Ai vừa đăng nhập cũng nhận trong ~3 giây (không cần sửa code login).
 *
 * item_id: -1=vàng, -2=ngọc, -3=ngọc khoá; >=0 = item template id.
 */
public class GiftPackageService {

    private static class MailItem {
        int itemId, quantity, optionId, optionParam;
        boolean hasOption;
    }

    /** Phát cho toàn bộ người chơi đang online các mail chưa nhận */
    public static void deliverToOnline() {
        List<Player> online;
        try {
            online = Client.gI().getPlayers();
        } catch (Exception e) {
            return;
        }
        if (online == null || online.isEmpty()) return;

        try (Connection con = LocalManager.getConnection()) {
            // các lượt gửi đang bật
            List<Integer> mailIds = new ArrayList<>();
            try (PreparedStatement ps = con.prepareStatement(
                    "SELECT id FROM gift_mail WHERE enabled = 1 ORDER BY id");
                 ResultSet rs = ps.executeQuery()) {
                while (rs.next()) mailIds.add(rs.getInt("id"));
            }
            if (mailIds.isEmpty()) return;

            for (Player p : online) {
                if (p == null) continue;
                int pid = (int) p.id;
                for (int mailId : mailIds) {
                    try {
                        if (alreadyReceived(con, mailId, pid)) continue;
                        // đánh dấu trước (chống phát trùng nếu lỗi giữa chừng)
                        if (!markReceived(con, mailId, pid)) continue;
                        deliverMailToPlayer(con, mailId, p);
                    } catch (Exception exItem) {
                        Logger.error("GiftPackage deliver mail " + mailId + " -> " + pid + ": " + exItem.getMessage() + "\n");
                    }
                }
            }
        } catch (Exception e) {
            Logger.error("GiftPackageService.deliverToOnline: " + e.getMessage() + "\n");
        }
    }

    private static boolean alreadyReceived(Connection con, int mailId, int pid) throws Exception {
        try (PreparedStatement ps = con.prepareStatement(
                "SELECT 1 FROM gift_mail_received WHERE mail_id = ? AND player_id = ? LIMIT 1")) {
            ps.setInt(1, mailId); ps.setInt(2, pid);
            try (ResultSet rs = ps.executeQuery()) { return rs.next(); }
        }
    }

    /** insert claim; false nếu đã tồn tại (đua) */
    private static boolean markReceived(Connection con, int mailId, int pid) {
        try (PreparedStatement ps = con.prepareStatement(
                "INSERT INTO gift_mail_received (mail_id, player_id) VALUES (?, ?)")) {
            ps.setInt(1, mailId); ps.setInt(2, pid);
            ps.executeUpdate();
            return true;
        } catch (Exception e) {
            return false; // trùng khoá -> đã nhận
        }
    }

    private static void deliverMailToPlayer(Connection con, int mailId, Player player) throws Exception {
        String title = "", content = "";
        try (PreparedStatement ps = con.prepareStatement(
                "SELECT title, content FROM gift_mail WHERE id = ?")) {
            ps.setInt(1, mailId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) { title = rs.getString("title"); content = rs.getString("content"); }
            }
        }
        List<MailItem> items = new ArrayList<>();
        try (PreparedStatement ps = con.prepareStatement(
                "SELECT item_id, quantity, option_id, option_param FROM gift_mail_item WHERE mail_id = ?")) {
            ps.setInt(1, mailId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    MailItem mi = new MailItem();
                    mi.itemId = rs.getInt("item_id");
                    mi.quantity = rs.getInt("quantity");
                    int opt = rs.getInt("option_id");
                    mi.hasOption = !rs.wasNull();
                    mi.optionId = opt;
                    mi.optionParam = rs.getInt("option_param");
                    items.add(mi);
                }
            }
        }

        // Cấp quà trên đối tượng player (đồng bộ để giảm tranh chấp luồng)
        synchronized (player) {
            StringBuilder text = new StringBuilder("|0|" + (title == null ? "" : title) + "\b");
            if (content != null && !content.isEmpty()) text.append("|0|").append(content).append("\b");
            for (MailItem mi : items) {
                switch (mi.itemId) {
                    case -1 -> {
                        player.inventory.gold = Math.min(player.inventory.gold + (long) mi.quantity, 2000000000L);
                        text.append("|2|").append(mi.quantity).append(" vàng\b");
                    }
                    case -2 -> {
                        player.inventory.gem = Math.min(player.inventory.gem + mi.quantity, 200000000);
                        text.append("|3|").append(mi.quantity).append(" ngọc\b");
                    }
                    case -3 -> {
                        player.inventory.ruby = Math.min(player.inventory.ruby + mi.quantity, 200000000);
                        text.append("|4|").append(mi.quantity).append(" ngọc khoá\b");
                    }
                    default -> {
                        Item tmpl = ItemService.gI().createNewItem((short) mi.itemId);
                        if (tmpl != null) {
                            Item it = new Item((short) mi.itemId);
                            if (mi.hasOption) {
                                it.itemOptions.add(new Item.ItemOption(mi.optionId, mi.optionParam));
                            }
                            it.quantity = Math.max(1, mi.quantity);
                            InventoryService.gI().addItemBag(player, it);
                            text.append("|1|x").append(mi.quantity).append(' ').append(it.template.name).append("\b");
                        }
                    }
                }
            }
            try {
                InventoryService.gI().sendItemBags(player);
                NpcService.gI().createTutorial(player, 1139, text.toString());
            } catch (Exception e) {
                Service.gI().sendThongBao(player, "Bạn nhận được quà: " + title);
            }
        }
    }
}
