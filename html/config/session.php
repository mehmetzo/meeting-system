<?php
// ============================================================
// Dijital Toplantı Sistemi — Session & Yetkilendirme
// ============================================================

// Türkçe karakter için mb_string ayarları
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_language('Turkish');

// Session henüz başlatılmadıysa başlat
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime',  '3600');
    ini_set('session.cookie_samesite', 'Lax');
    session_name('MEETINGSESSID');
    session_start();
}

// ------------------------------------------------------------
// Yetkilendirme fonksiyonları
// ------------------------------------------------------------

/**
 * Admin oturumu yoksa login sayfasına yönlendirir.
 */
function requireAdmin(): void
{
    if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: /admin/login.php');
        exit;
    }

    // Session süre kontrolü (60 dakika hareketsizlik)
    if (!empty($_SESSION['admin_last_activity'])) {
        if (time() - $_SESSION['admin_last_activity'] > 3600) {
            session_unset();
            session_destroy();
            header('Location: /admin/login.php?timeout=1');
            exit;
        }
    }
    $_SESSION['admin_last_activity'] = time();
}

/**
 * Sadece superadmin erişebilir; diğerleri dashboard'a yönlendirilir.
 */
function requireSuperAdmin(): void
{
    requireAdmin();
    if (($_SESSION['admin_role'] ?? '') !== 'superadmin') {
        header('Location: /admin/index.php?error=yetkisiz');
        exit;
    }
}

/**
 * Admin oturum durumunu boolean olarak döner.
 */
function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_logged_in'])
        && $_SESSION['admin_logged_in'] === true;
}

/**
 * Oturumdaki admin bilgilerini dizi olarak döner.
 */
function getCurrentAdmin(): array
{
    return [
        'id'       => (int)  ($_SESSION['admin_id']       ?? 0),
        'username' => (string)($_SESSION['admin_username'] ?? ''),
        'name'     => (string)($_SESSION['admin_name']     ?? ''),
        'role'     => (string)($_SESSION['admin_role']     ?? ''),
    ];
}

// ------------------------------------------------------------
// Erişim logu
// ------------------------------------------------------------

/**
 * access_logs tablosuna kayıt düşer.
 *
 * @param string   $action   İşlem adı (örn: 'admin_login')
 * @param string   $details  Ek açıklama
 * @param string   $status   'success' | 'warning' | 'error' | 'info'
 * @param int|null $userId   Belirtilmezse oturumdaki admin ID kullanılır
 */
function logAccess(
    string $action,
    string $details = '',
    string $status  = 'info',
    ?int   $userId  = null
): void {
    try {
        // database.php bu dosyadan önce include edilmiş olmalı;
        // yoksa burada güvenli şekilde yükle.
        if (!function_exists('getDB')) {
            require_once __DIR__ . '/database.php';
        }

        $db    = getDB();
        $admin = getCurrentAdmin();

        $stmt = $db->prepare(
            'INSERT INTO access_logs
             (user_id, username, action, details, ip_address, user_agent, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $userId ?? ($admin['id'] ?: null),
            $admin['username'] ?: 'system',
            mb_substr($action,  0, 200),
            mb_substr($details, 0, 2000),
            $_SERVER['REMOTE_ADDR']     ?? '',
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            $status,
        ]);
    } catch (Exception $e) {
        error_log('[logAccess] ' . $e->getMessage());
    }
}