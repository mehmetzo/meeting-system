<?php
require_once __DIR__ . '/database.php';

class LdapAuth {
    private string $host;
    private int    $port;
    private string $baseDn;
    private string $domain;
    private string $bindUser;
    private string $bindPass;
    private string $group;
    private string $tcAttr;
    private string $phoneAttr;
    private bool   $enabled;

    public function __construct() {
        $this->enabled   = getSetting('ldap_enabled', '0') === '1';
        $this->host      = getSetting('ldap_host', '');
        $this->port      = (int)getSetting('ldap_port', '389');
        $this->baseDn    = getSetting('ldap_base_dn', 'dc=domain,dc=local');
        $this->domain    = getSetting('ldap_domain', 'domain.local');
        $this->bindUser  = getSetting('ldap_bind_user', '');
        $this->bindPass  = getSetting('ldap_bind_password', '');
        $this->group     = getSetting('ldap_group', '');
        $this->tcAttr    = getSetting('ldap_tc_attribute', 'employeeID');
        $this->phoneAttr = getSetting('ldap_phone_attribute', 'mobile');
    }

    public function isEnabled(): bool { return $this->enabled; }

    public function authenticate(string $username, string $password): array {
        if (!$this->enabled || empty($this->host)) {
            return ['success' => false, 'error' => 'LDAP yapilandirilmamis'];
        }
        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Kullanici adi veya sifre bos'];
        }

        $conn = @ldap_connect("ldap://{$this->host}", $this->port);
        if (!$conn) {
            return ['success' => false, 'error' => 'LDAP sunucusuna baglanılamadı'];
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);

        // Kullanıcı kimlik doğrulama
        $userDn = $username . '@' . $this->domain;
        $bound  = @ldap_bind($conn, $userDn, $password);

        if (!$bound) {
            ldap_close($conn);
            return ['success' => false, 'error' => 'Kullanici adi veya sifre hatali'];
        }

        // Servis hesabı ile yeniden bağlan (arama için)
        if ($this->bindUser && $this->bindPass) {
            @ldap_bind($conn, $this->bindUser . '@' . $this->domain, $this->bindPass);
        }

        // Kullanıcı bilgilerini çek
        $userData = $this->getUserInfo($conn, $username);

        // Grup kontrolü (grup tanımlıysa)
        if (!empty($this->group)) {
            $inGroup = $this->checkGroup($conn, $username);
            if (!$inGroup) {
                ldap_close($conn);
                return [
                    'success' => false,
                    'error'   => 'Bu gruba erisim yetkiniz yok: ' . $this->group
                ];
            }
        }

        ldap_close($conn);

        if ($userData) {
            $this->cacheUser($username, $userData);
            return ['success' => true, 'user' => $userData];
        }

        return ['success' => true, 'user' => [
            'username'   => $username,
            'full_name'  => $username,
            'email'      => '',
            'tc_no'      => '',
            'phone'      => '',
            'department' => '',
            'title'      => '',
        ]];
    }

    private function checkGroup($conn, string $username): bool {
        // Yöntem 1: Kullanıcının memberOf listesini kontrol et
        $filter = "(sAMAccountName={$username})";
        $search = @ldap_search($conn, $this->baseDn, $filter, ['memberOf', 'dn']);
        if ($search) {
            $entries = ldap_get_entries($conn, $search);
            if ($entries['count'] > 0) {
                $memberOf = $entries[0]['memberof'] ?? [];
                for ($i = 0; $i < ($memberOf['count'] ?? 0); $i++) {
                    // CN=GrupAdi,... içinde ara
                    if (stripos($memberOf[$i], 'CN=' . $this->group . ',') !== false) {
                        return true;
                    }
                    // Sadece grup adı ile de kontrol et
                    if (stripos($memberOf[$i], $this->group) !== false) {
                        return true;
                    }
                }
            }
        }

        // Yöntem 2: Grubun member listesinde ara
        $groupFilter = "(&(objectClass=group)(cn={$this->group}))";
        $groupSearch = @ldap_search($conn, $this->baseDn, $groupFilter, ['member']);
        if ($groupSearch) {
            $groupEntries = ldap_get_entries($conn, $groupSearch);
            if ($groupEntries['count'] > 0) {
                $members = $groupEntries[0]['member'] ?? [];
                for ($i = 0; $i < ($members['count'] ?? 0); $i++) {
                    if (stripos($members[$i], 'CN=' . $username . ',') !== false ||
                        stripos($members[$i], $username) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function getUserInfo($conn, string $username): ?array {
        try {
            $filter = "(sAMAccountName={$username})";
            $attrs  = ['cn', 'displayName', 'mail', 'department', 'title',
                       $this->tcAttr, $this->phoneAttr, 'memberOf', 'telephoneNumber'];
            $search = @ldap_search($conn, $this->baseDn, $filter, $attrs);
            if (!$search) return null;

            $entries = ldap_get_entries($conn, $search);
            if ($entries['count'] === 0) return null;

            $entry = $entries[0];
            return [
                'username'   => $username,
                'full_name'  => $this->getLdapAttr($entry, 'displayname') ?:
                                $this->getLdapAttr($entry, 'cn') ?: $username,
                'email'      => $this->getLdapAttr($entry, 'mail'),
                'tc_no'      => $this->getLdapAttr($entry, strtolower($this->tcAttr)),
                'phone'      => $this->getLdapAttr($entry, strtolower($this->phoneAttr)) ?:
                                $this->getLdapAttr($entry, 'telephonenumber'),
                'department' => $this->getLdapAttr($entry, 'department'),
                'title'      => $this->getLdapAttr($entry, 'title'),
            ];
        } catch (Exception $e) {
            error_log('[LDAP getUserInfo] ' . $e->getMessage());
            return null;
        }
    }

    private function getLdapAttr(array $entry, string $attr): string {
        return isset($entry[$attr][0]) ? (string)$entry[$attr][0] : '';
    }

    private function cacheUser(string $username, array $data): void {
        try {
            $db   = getDB();
            $stmt = $db->prepare(
                "INSERT INTO ldap_users_cache
                 (username, full_name, email, tc_no, phone, department, title)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 full_name=VALUES(full_name), email=VALUES(email),
                 tc_no=VALUES(tc_no), phone=VALUES(phone),
                 department=VALUES(department), title=VALUES(title),
                 last_sync=NOW()"
            );
            $stmt->execute([
                $username, $data['full_name'], $data['email'],
                $data['tc_no'], $data['phone'], $data['department'], $data['title'],
            ]);
        } catch (Exception $e) {
            error_log('[LDAP cache] ' . $e->getMessage());
        }
    }

    public function testConnection(): array {
        if (empty($this->host)) {
            return ['success' => false, 'message' => 'LDAP host tanimlanmamis'];
        }

        $conn = @ldap_connect("ldap://{$this->host}", $this->port);
        if (!$conn) {
            return ['success' => false, 'message' => 'Sunucuya baglanilamiyor: ' . $this->host . ':' . $this->port];
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);

        if ($this->bindUser && $this->bindPass) {
            $bindDn = $this->bindUser . '@' . $this->domain;
            $bound  = @ldap_bind($conn, $bindDn, $this->bindPass);
            if (!$bound) {
                $err = ldap_error($conn);
                ldap_close($conn);
                return ['success' => false, 'message' => 'Servis hesabi dogrulanamadi. Hata: ' . $err];
            }

            // Grup kontrolü
            if (!empty($this->group)) {
                $filter = "(&(objectClass=group)(cn={$this->group}))";
                $search = @ldap_search($conn, $this->baseDn, $filter, ['cn', 'member']);
                if ($search) {
                    $entries = ldap_get_entries($conn, $search);
                    if ($entries['count'] > 0) {
                        $memberCount = $entries[0]['member']['count'] ?? 0;
                        ldap_close($conn);
                        return [
                            'success' => true,
                            'message' => 'Baglanti basarili. Grup bulundu: "' . $this->group . '" (' . $memberCount . ' uye)'
                        ];
                    } else {
                        ldap_close($conn);
                        return [
                            'success' => false,
                            'message' => 'Baglanti basarili ANCAK grup bulunamadi: "' . $this->group . '". Grup adini kontrol edin.'
                        ];
                    }
                }
            }

            ldap_close($conn);
            return ['success' => true, 'message' => 'Baglanti ve servis hesabi dogrulamasi basarili'];
        }

        $bound = @ldap_bind($conn);
        ldap_close($conn);
        return $bound
            ? ['success' => true,  'message' => 'Sunucuya ulasildi (anonim baglanti)']
            : ['success' => false, 'message' => 'Anonim baglanti reddedildi'];
    }
}