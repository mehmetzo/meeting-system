<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/ldap.php';
require_once __DIR__ . '/../includes/lang.php';

if (isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';

    if ($username && $password) {
        $db = getDB();

        // Once DB kullanicisi kontrol et
        $stmt = $db->prepare(
            "SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in']     = true;
            $_SESSION['admin_id']            = $user['id'];
            $_SESSION['admin_username']      = $user['username'];
            $_SESSION['admin_name']          = $user['full_name'];
            $_SESSION['admin_role']          = $user['role'];
            $_SESSION['admin_last_activity'] = time();
            $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")
               ->execute([$user['id']]);
            logAccess('admin_login', "Admin girisi (DB): {$username}", 'success', $user['id']);
            header('Location: /admin/index.php');
            exit;
        }

        // LDAP dene
        $ldap = new LdapAuth();
        if ($ldap->isEnabled()) {
            $result = $ldap->authenticate($username, $password);
            error_log('[ADMIN LDAP] user=' . $username . ' success=' . ($result['success'] ? 'true' : 'false'));

            if ($result['success']) {
                $ldapUser = $result['user'];

                $stmt2 = $db->prepare(
                    "SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1"
                );
                $stmt2->execute([$username]);
                $dbUser = $stmt2->fetch();

                if (!$dbUser) {
                    $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
                    $db->prepare(
                        "INSERT INTO admin_users (username, password, full_name, email, role, is_active)
                         VALUES (?, ?, ?, ?, 'viewer', 1)
                         ON DUPLICATE KEY UPDATE
                         full_name=VALUES(full_name), email=VALUES(email), is_active=1"
                    )->execute([
                        $username,
                        $hash,
                        $ldapUser['full_name'] ?: $username,
                        $ldapUser['email']     ?: '',
                    ]);
                    $stmt2->execute([$username]);
                    $dbUser = $stmt2->fetch();
                }

                if ($dbUser && $dbUser['is_active']) {
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in']     = true;
                    $_SESSION['admin_id']            = $dbUser['id'];
                    $_SESSION['admin_username']      = $dbUser['username'];
                    $_SESSION['admin_name']          = $dbUser['full_name'];
                    $_SESSION['admin_role']          = $dbUser['role'];
                    $_SESSION['admin_last_activity'] = time();
                    $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")
                       ->execute([$dbUser['id']]);
                    logAccess('admin_login', "Admin girisi (LDAP): {$username}", 'success', $dbUser['id']);
                    header('Location: /admin/index.php');
                    exit;
                }
            }
        }

        $error = $LANG['login_error'];
        logAccess('admin_login_fail', "Basarisiz giris: {$username}", 'warning');

    } else {
        $error = $LANG['login_empty'];
    }
}

$hospitalName = getSetting('hospital_name', 'Dijital Toplanti Sistemi');
$footerText   = getSetting('footer_text',   '');
$logoPath     = getSetting('logo_path', '');

$lYonetim     = $LANG['login_title'];
$lKullanici   = $LANG['login_username'];
$lSifre       = $LANG['login_password'];
$lGiris       = $LANG['login_btn'];
$lPlaceholder = $LANG['login_placeholder'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $lYonetim ?> &mdash; <?= htmlspecialchars($hospitalName, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', system-ui, sans-serif;
      background-color: #1a2e4a;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .login-card {
      background: #fff;
      border-radius: 16px;
      padding: 48px 40px 40px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .login-logo {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 28px;
    }
    .logo-img {
      width: 72px; height: 72px;
      object-fit: contain; border-radius: 50%; margin-bottom: 14px;
    }
    .logo-icon-wrap {
      width: 72px; height: 72px; border-radius: 50%;
      background: #f0f4f8; border: 2px solid #e2e8f0;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 14px;
    }
    .login-logo h2 {
      font-size: 1.3rem; font-weight: 700;
      color: #1a2e4a; text-align: center; margin-bottom: 4px;
    }
    .login-logo p { font-size: 0.82rem; color: #64748b; text-align: center; }
    .alert-error {
      background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
      border-radius: 8px; padding: 10px 14px; font-size: 0.84rem;
      margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
    }
    .form-group { margin-bottom: 16px; }
    .form-group label {
      display: block; font-size: 0.84rem;
      font-weight: 500; color: #374151; margin-bottom: 6px;
    }
    .form-control {
      width: 100%; padding: 11px 14px;
      border: 1.5px solid #e2e8f0; border-radius: 8px;
      font-size: 0.9rem; font-family: 'Inter', sans-serif;
      color: #1a2e4a; background: #fff;
      transition: border-color 0.2s; outline: none;
    }
    .form-control::placeholder { color: #9ca3af; }
    .form-control:focus {
      border-color: #1a5276;
      box-shadow: 0 0 0 3px rgba(26,82,118,0.1);
    }
    .password-wrap { position: relative; }
    .password-wrap .form-control { padding-right: 42px; }
    .toggle-pass {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: #9ca3af; padding: 4px; line-height: 1;
    }
    .toggle-pass:hover { color: #6b7280; }
    .btn-login {
      width: 100%; padding: 13px;
      background: #1a5276; color: #fff;
      border: none; border-radius: 8px;
      font-size: 0.95rem; font-weight: 600;
      font-family: 'Inter', sans-serif;
      cursor: pointer; margin-top: 8px;
      transition: background 0.2s;
    }
    .btn-login:hover { background: #154360; }
    .login-footer {
      text-align: center; margin-top: 24px;
      font-size: 0.72rem; color: #9ca3af;
      border-top: 1px solid #f1f5f9; padding-top: 16px;
    }
    .ldap-hint {
      text-align: center; margin-top: 12px;
      font-size: 0.75rem; color: #9ca3af;
    }
  </style>
</head>
<body>
<div class="login-card">

  <div class="login-logo">
    <?php if ($logoPath && file_exists('/var/www/html' . $logoPath)): ?>
      <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="logo-img">
    <?php else: ?>
      <div class="logo-icon-wrap">
        <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="#1a5276" stroke-width="1.8">
          <rect x="3" y="4" width="18" height="18" rx="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8"  y1="2" x2="8"  y2="6"/>
          <line x1="3"  y1="10" x2="21" y2="10"/>
        </svg>
      </div>
    <?php endif; ?>
    <h2><?= $lYonetim ?></h2>
    <p><?= htmlspecialchars($hospitalName, ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <?php if ($error): ?>
  <div class="alert-error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="12" cy="12" r="10"/>
      <line x1="12" y1="8" x2="12" y2="12"/>
      <line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="form-group">
      <label for="username"><?= $lKullanici ?></label>
      <input type="text" id="username" name="username" class="form-control"
             placeholder="<?= htmlspecialchars($lPlaceholder, ENT_QUOTES, 'UTF-8') ?>"
             value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
             autocomplete="username" autofocus required>
    </div>
    <div class="form-group">
      <label for="password"><?= $lSifre ?></label>
      <div class="password-wrap">
        <input type="password" id="password" name="password" class="form-control"
               placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
               autocomplete="current-password" required>
        <button type="button" class="toggle-pass" onclick="togglePwd()">
          <svg id="eyeIcon" viewBox="0 0 24 24" width="16" height="16" fill="none"
               stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
    </div>
    <button type="submit" class="btn-login"><?= $lGiris ?></button>
  </form>

  <?php if ((new LdapAuth())->isEnabled()): ?>
  <p class="ldap-hint">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" style="vertical-align:middle">
      <rect x="3" y="11" width="18" height="11" rx="2"/>
      <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
    </svg>
    <?= $LANG['ldap_desc'] ?>
  </p>
  <?php endif; ?>

  <div class="login-footer">
    <?= htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8') ?>
  </div>
</div>

<script>
function togglePwd() {
  var inp  = document.getElementById('password');
  var icon = document.getElementById('eyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}
</script>
</body>
</html>