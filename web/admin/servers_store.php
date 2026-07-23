<?php
/**
 * Quản lý danh sách máy chủ admin có thể quản trị (lưu ở servers.config.php).
 */

define('ADMIN_SERVERS_FILE', __DIR__ . '/servers.config.php');

/** Đọc danh sách máy chủ; luôn trả về mảng (tối thiểu 1 mặc định) */
function admin_servers(): array
{
    $list = @include ADMIN_SERVERS_FILE;
    if (!is_array($list) || !$list) {
        return [[
            'key' => 'sv1', 'name' => 'Server 1',
            'host' => 'localhost', 'dbname' => 'team2026', 'user' => 'root', 'pass' => '',
        ]];
    }
    return array_values($list);
}

/** Ghi danh sách máy chủ ra file PHP */
function admin_save_servers(array $list): bool
{
    $clean = [];
    foreach ($list as $s) {
        if (empty($s['key']) || empty($s['dbname'])) continue;
        $clean[] = [
            'key'    => (string)$s['key'],
            'name'   => (string)($s['name'] ?? $s['key']),
            'host'   => (string)($s['host'] ?? 'localhost'),
            'dbname' => (string)$s['dbname'],
            'user'   => (string)($s['user'] ?? 'root'),
            'pass'   => (string)($s['pass'] ?? ''),
        ];
    }
    if (!$clean) return false;
    $php = "<?php\n// Danh sách máy chủ admin quản lý (tự sinh). Sửa qua trang 'Máy chủ QL'.\nreturn "
         . var_export($clean, true) . ";\n";
    return @file_put_contents(ADMIN_SERVERS_FILE, $php) !== false;
}

/** Máy chủ theo key */
function admin_server_by_key(string $key): ?array
{
    foreach (admin_servers() as $s) {
        if ($s['key'] === $key) return $s;
    }
    return null;
}

/** Key máy chủ đang chọn (session) hoặc máy chủ đầu tiên */
function admin_current_key(): string
{
    $servers = admin_servers();
    $keys = array_column($servers, 'key');
    $cur = $_SESSION['admin_sv'] ?? '';
    if ($cur !== '' && in_array($cur, $keys, true)) return $cur;
    return $servers[0]['key'];
}

/** Máy chủ đang chọn (mảng) */
function admin_current_server(): array
{
    return admin_server_by_key(admin_current_key()) ?? admin_servers()[0];
}
