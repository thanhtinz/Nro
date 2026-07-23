package nro.models.daily_Giftcode;

import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.ThreadLocalRandom;

/**
 * Kho quà bùa miễn phí hằng ngày (Bà Hạt Mít), nạp từ bảng `daily_gift_reward`.
 * WebControlService cập nhật danh sách này mỗi ~3 giây (config-sync) nên
 * admin chỉnh bảng là có hiệu lực ngay, KHÔNG cần restart server.
 *
 * Mỗi phần tử: int[]{itemId, durationMinutes}.
 */
public class DailyGiftConfig {

    // volatile: đọc/ghi giữa luồng WebControlService và luồng game
    private static volatile List<int[]> pool = new ArrayList<>();

    /** WebControlService gọi để nạp lại kho quà từ DB */
    public static void setPool(List<int[]> newPool) {
        pool = (newPool != null) ? newPool : new ArrayList<>();
    }

    /** Bốc ngẫu nhiên 1 phần thưởng; trả về null nếu chưa cấu hình (dùng mặc định code) */
    public static int[] getRandom() {
        List<int[]> p = pool;
        if (p == null || p.isEmpty()) {
            return null;
        }
        return p.get(ThreadLocalRandom.current().nextInt(p.size()));
    }
}
