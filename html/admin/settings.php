<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../config/ldap.php';
requireSuperAdmin();

$pageTitle = $LANG['settings_title'];
$success   = '';
$error     = '';
$tab       = $_GET['tab'] ?? 'hospital';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_hospital') {
        setSetting('institution_name', trim($_POST['institution_name'] ?? ''));
        setSetting('hospital_name',    trim($_POST['hospital_name']    ?? ''));
        setSetting('footer_text',      trim($_POST['footer_text']      ?? ''));
        setSetting('primary_color',    trim($_POST['primary_color']    ?? '#1a5276'));

        if (!empty($_FILES['logo']['tmp_name'])) {
            $file    = $_FILES['logo'];
            $allowed = ['image/png','image/jpeg','image/svg+xml'];
            if (in_array($file['type'], $allowed) && $file['size'] <= 2*1024*1024) {
                $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
                $dest = '/var/www/html/assets/img/logo.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    setSetting('logo_path', '/assets/img/logo.' . $ext);
                }
            } else {
                $error = 'Logo format' . chr(0xC4).chr(0xB1) . ' ge' . chr(0xC3).chr(0xA7) . 'ersiz veya 2MB den b' . chr(0xC3).chr(0xBC) . 'y' . chr(0xC3).chr(0xBC) . 'k.';
            }
        }
        if (isset($_POST['remove_logo'])) setSetting('logo_path', '');
        if (!$error) {
            $success = 'Kurum bilgileri kaydedildi.';
        }
        logAccess('settings_hospital', 'Kurum ayarlari guncellendi', 'success');
        $tab = 'hospital';
    }

    if ($action === 'save_ldap') {
        setSetting('ldap_enabled',         isset($_POST['ldap_enabled']) ? '1' : '0');
        setSetting('ldap_host',            trim($_POST['ldap_host']             ?? ''));
        setSetting('ldap_port',            trim($_POST['ldap_port']             ?? '389'));
        setSetting('ldap_base_dn',         trim($_POST['ldap_base_dn']          ?? ''));
        setSetting('ldap_domain',          trim($_POST['ldap_domain']           ?? ''));
        setSetting('ldap_bind_user',       trim($_POST['ldap_bind_user']        ?? ''));
        setSetting('ldap_group',           trim($_POST['ldap_group']            ?? ''));
        setSetting('ldap_tc_attribute',    trim($_POST['ldap_tc_attribute']     ?? 'employeeID'));
        setSetting('ldap_phone_attribute', trim($_POST['ldap_phone_attribute']  ?? 'mobile'));
        if (!empty($_POST['ldap_bind_password'])) {
            setSetting('ldap_bind_password', $_POST['ldap_bind_password']);
        }
        $success = 'LDAP ayarlar' . chr(0xC4).chr(0xB1) . ' kaydedildi.';
        logAccess('settings_ldap', 'LDAP ayarlari guncellendi', 'success');
        $tab = 'ldap';
    }

    if ($action === 'change_password') {
        $cur  = $_POST['current_password'] ?? '';
        $new  = $_POST['new_password']     ?? '';
        $new2 = $_POST['new_password2']    ?? '';
        $aid  = getCurrentAdmin()['id'];
        $db   = getDB();
        $user = $db->query("SELECT password FROM admin_users WHERE id={$aid}")->fetch();

        if (!password_verify($cur, $user['password'])) {
            $error = 'Mevcut ' . chr(0xC5).chr(0x9F) . 'ifre hatal' . chr(0xC4).chr(0xB1) . '.';
        } elseif (strlen($new) < 8) {
            $error = 'Yeni ' . chr(0xC5).chr(0x9F) . 'ifre en az 8 karakter olmal' . chr(0xC4).chr(0xB1) . '.';
        } elseif ($new !== $new2) {
            $error = 'Yeni ' . chr(0xC5).chr(0x9F) . 'ifreler e' . chr(0xC5).chr(0x9F) . 'le' . chr(0xC5).chr(0x9F) . 'miyor.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE admin_users SET password=? WHERE id=?")->execute([$hash, $aid]);
            $success = chr(0xC5).chr(0x9E) . 'ifreniz ba' . chr(0xC5).chr(0x9F) . 'ar' . chr(0xC4).chr(0xB1) . 'yla de' . chr(0xC4).chr(0x9F) . 'i' . chr(0xC5).chr(0x9F) . 'tirildi.';
            logAccess('password_change', 'Admin sifresi degistirildi', 'success');
        }
        $tab = 'password';
    }

    if ($action === 'test_ldap') {
        header('Content-Type: application/json; charset=UTF-8');
        $ldap   = new LdapAuth();
        $result = $ldap->testConnection();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Türkçe etiketler
$lKurumBilgi  = 'Kurum Bilgileri';
$lKurumAdi    = 'Kurum Ad' . chr(0xC4).chr(0xB1);
$lHastaneAdi  = 'Hastane / Birim Ad' . chr(0xC4).chr(0xB1);
$lFooter      = 'Footer Metni';
$lAnaRenk     = 'Ana Renk';
$lLogo        = 'Kurum Logosu';
$lLogoKaldir  = 'Logoyu Kald' . chr(0xC4).chr(0xB1) . 'r';
$lKaydet      = 'Kaydet';
$lLDAPAktif   = 'LDAP Aktif';
$lLDAPDesc    = 'LDAP ile kimlik do' . chr(0xC4).chr(0x9F) . 'rulama kullan';
$lLDAPHost    = 'LDAP Host (IP Adresi)';
$lSifreDeg    = chr(0xC5).chr(0x9E) . 'ifre De' . chr(0xC4).chr(0x9F) . 'i' . chr(0xC5).chr(0x9F) . 'tir';
$lMevSifre    = 'Mevcut ' . chr(0xC5).chr(0x9E) . 'ifre';
$lYeniSifre   = 'Yeni ' . chr(0xC5).chr(0x9E) . 'ifre';
$lYeniSifre2  = 'Yeni ' . chr(0xC5).chr(0x9E) . 'ifre (Tekrar)';
$lSifreGun    = chr(0xC5).chr(0x9E) . 'ifreyi G' . chr(0xC3).chr(0xBC) . 'ncelle';
$lTestEt      = 'Ba' . chr(0xC4).chr(0x9F) . 'lant' . chr(0xC4).chr(0xB1) . 'y' . chr(0xC4).chr(0xB1) . ' Test Et';
$lSifreKayitli= chr(0xC5).chr(0x9E) . 'ifre kay' . chr(0xC4).chr(0xB1) . 'tl' . chr(0xC4).chr(0xB1);
$lAdminKul    = 'Admin Kullan' . chr(0xC4).chr(0xB1) . 'c' . chr(0xC4).chr(0xB1) . 'lar';
$lServisHesap = 'Servis Hesab' . chr(0xC4).chr(0xB1) . ' (Bind User)';
$lServisSifre = 'Servis Hesab' . chr(0xC4).chr(0xB1) . ' ' . chr(0xC5).chr(0x9E) . 'ifresi';
$lDegistir    = 'De' . chr(0xC4).chr(0x9F) . 'i' . chr(0xC5).chr(0x9F) . 'tirmek i' . chr(0xC3).chr(0xA7) . 'in girin';
$lGrupAdi     = 'Grup Ad' . chr(0xC4).chr(0xB1) . ' (Opsiyonel)';
$lTCAttr      = 'TC No LDAP ' . chr(0xC3).chr(0x96) . 'zelli' . chr(0xC4).chr(0x9F) . 'i';
$lPhoneAttr   = 'Telefon LDAP ' . chr(0xC3).chr(0x96) . 'zelli' . chr(0xC4).chr(0x9F) . 'i';
$lRenkHint    = 'Men' . chr(0xC3).chr(0xBC) . ' ve buton rengi';
$lEnAz        = 'En az 8 karakter';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <div>
        <h1 class="page-title"><?= $LANG['settings_title'] ?></h1>
        <p class="page-sub"><?= $LANG['settings_sub'] ?></p>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="settings-layout">
      <div class="settings-tabs">
        <a href="?tab=hospital" class="tab-item <?= $tab==='hospital'?'active':'' ?>">
          <i class="fas fa-hospital"></i> <?= $lKurumBilgi ?>
        </a>
        <a href="?tab=ldap" class="tab-item <?= $tab==='ldap'?'active':'' ?>">
          <i class="fas fa-lock"></i> <?= $LANG['tab_ldap'] ?>
        </a>
        <a href="?tab=password" class="tab-item <?= $tab==='password'?'active':'' ?>">
          <i class="fas fa-key"></i> <?= $lSifreDeg ?>
        </a>
        <a href="?tab=users" class="tab-item <?= $tab==='users'?'active':'' ?>">
          <i class="fas fa-users-cog"></i> <?= $lAdminKul ?>
        </a>
      </div>

      <div class="settings-content">

        <?php if ($tab === 'hospital'): ?>
        <div class="card">
          <div class="card-header"><h3><i class="fas fa-hospital"></i> <?= $lKurumBilgi ?></h3></div>
          <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_hospital">
              <div class="form-row">
                <div class="form-group">
                  <label><?= $lKurumAdi ?></label>
                  <input type="text" name="institution_name" class="form-control"
                         value="<?= htmlspecialchars(getSetting('institution_name'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                  <label><?= $lHastaneAdi ?></label>
                  <input type="text" name="hospital_name" class="form-control"
                         value="<?= htmlspecialchars(getSetting('hospital_name'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
              </div>
              <div class="form-group">
                <label><?= $lFooter ?></label>
                <input type="text" name="footer_text" class="form-control"
                       value="<?= htmlspecialchars(getSetting('footer_text'), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="form-group">
                <label><?= $lAnaRenk ?></label>
                <div class="color-picker-row">
                  <input type="color" name="primary_color" class="form-control-color"
                         value="<?= htmlspecialchars(getSetting('primary_color','#1a5276'), ENT_QUOTES, 'UTF-8') ?>">
                  <span class="color-hint"><?= $lRenkHint ?></span>
                </div>
              </div>
              <div class="form-group">
                <label><?= $lLogo ?></label>
                <?php $logo = getSetting('logo_path',''); if ($logo): ?>
                  <div class="logo-preview">
                    <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="height:60px">
                    <button type="submit" name="remove_logo" value="1" class="btn btn-sm btn-danger ml-2">
                      <i class="fas fa-trash"></i> <?= $lLogoKaldir ?>
                    </button>
                  </div>
                <?php endif; ?>
                <div class="file-upload-area">
                  <input type="file" name="logo" id="logoFile" accept=".png,.jpg,.svg" hidden>
                  <label for="logoFile" class="file-upload-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>PNG, JPG veya SVG se&#231;in (max 2MB)</span>
                  </label>
                </div>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $lKaydet ?></button>
            </form>
          </div>
        </div>

        <?php elseif ($tab === 'ldap'): ?>
        <div class="card">
          <div class="card-header"><h3><i class="fas fa-lock"></i> <?= $LANG['tab_ldap'] ?></h3></div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="save_ldap">
              <div class="form-group">
                <label><?= $lLDAPAktif ?></label>
                <div class="toggle-row">
                  <label class="toggle-switch">
                    <input type="checkbox" name="ldap_enabled"
                           <?= getSetting('ldap_enabled','0')==='1'?'checked':'' ?>>
                    <span class="toggle-slider"></span>
                  </label>
                  <span><?= $lLDAPDesc ?></span>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label><?= $lLDAPHost ?></label>
                  <input type="text" name="ldap_host" class="form-control"
                         placeholder="Sunucu IP adresi"
                         value="<?= htmlspecialchars(getSetting('ldap_host'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                  <label>Port</label>
                  <input type="number" name="ldap_port" class="form-control"
                         value="<?= htmlspecialchars(getSetting('ldap_port','389'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Base DN</label>
                  <input type="text" name="ldap_base_dn" class="form-control"
                         placeholder="dc=domain,dc=local"
                         value="<?= htmlspecialchars(getSetting('ldap_base_dn'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                  <label>Domain</label>
                  <input type="text" name="ldap_domain" class="form-control"
                         placeholder="domain.local"
                         value="<?= htmlspecialchars(getSetting('ldap_domain'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label><?= $lServisHesap ?></label>
                  <input type="text" name="ldap_bind_user" class="form-control"
                         placeholder="servis_hesabi"
                         value="<?= htmlspecialchars(getSetting('ldap_bind_user'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                  <label><?= $lServisSifre ?></label>
                  <input type="password" name="ldap_bind_password" class="form-control"
                         placeholder="<?= $lDegistir ?>">
                  <?php if (getSetting('ldap_bind_password')): ?>
                    <span class="field-hint success">
                      <i class="fas fa-check-circle"></i> <?= $lSifreKayitli ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label><?= $lGrupAdi ?></label>
                  <input type="text" name="ldap_group" class="form-control"
                         placeholder="grup_adi"
                         value="<?= htmlspecialchars(getSetting('ldap_group'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                  <label><?= $lTCAttr ?></label>
                  <input type="text" name="ldap_tc_attribute" class="form-control"
                         placeholder="employeeID"
                         value="<?= htmlspecialchars(getSetting('ldap_tc_attribute','employeeID'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
              </div>
              <div class="form-group">
                <label><?= $lPhoneAttr ?></label>
                <input type="text" name="ldap_phone_attribute" class="form-control"
                       placeholder="mobile"
                       value="<?= htmlspecialchars(getSetting('ldap_phone_attribute','mobile'), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save"></i> <?= $lKaydet ?>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="testLdap()">
                  <i class="fas fa-plug"></i> <?= $lTestEt ?>
                </button>
              </div>
              <div id="ldapTestResult" class="mt-2" style="display:none"></div>
            </form>
          </div>
        </div>

        <?php elseif ($tab === 'password'): ?>
        <div class="card">
          <div class="card-header"><h3><i class="fas fa-key"></i> <?= $lSifreDeg ?></h3></div>
          <div class="card-body" style="max-width:480px">
            <form method="POST">
              <input type="hidden" name="action" value="change_password">
              <div class="form-group">
                <label><?= $lMevSifre ?></label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <div class="form-group">
                <label><?= $lYeniSifre ?></label>
                <input type="password" name="new_password" class="form-control" minlength="8" required>
                <span class="field-hint"><?= $lEnAz ?></span>
              </div>
              <div class="form-group">
                <label><?= $lYeniSifre2 ?></label>
                <input type="password" name="new_password2" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= $lSifreGun ?>
              </button>
            </form>
          </div>
        </div>

        <?php elseif ($tab === 'users'): ?>
        <?php
        $db    = getDB();
        $users = $db->query("SELECT * FROM admin_users ORDER BY created_at")->fetchAll();
        ?>
        <div class="card">
          <div class="card-header d-flex-between">
            <h3><i class="fas fa-users-cog"></i> <?= $lAdminKul ?></h3>
          </div>
          <div class="card-body p-0">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Ad Soyad</th>
                  <th>Kullan&#305;c&#305; Ad&#305;</th>
                  <th>Rol</th>
                  <th>Son Giri&#351;</th>
                  <th>Durum</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                  <td><?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><code><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></code></td>
                  <td>
                    <span class="badge <?= $u['role']==='superadmin'?'badge-primary':'badge-secondary' ?>">
                      <?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td><?= $u['last_login'] ? date('d.m.Y H:i', strtotime($u['last_login'])) : '&#8212;' ?></td>
                  <td>
                    <span class="badge <?= $u['is_active']?'badge-success':'badge-danger' ?>">
                      <?= $u['is_active'] ? 'Aktif' : 'Pasif' ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<script>
function testLdap() {
  var btn = event.target.closest('button');
  var res = document.getElementById('ldapTestResult');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test ediliyor...';
  res.style.display = 'none';

  fetch('/admin/settings.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=test_ldap'
  })
  .then(function(r) { return r.text(); })
  .then(function(text) {
    try {
      var d = JSON.parse(text);
      res.style.display = 'block';
      res.className = 'alert ' + (d.success ? 'alert-success' : 'alert-danger');
      res.innerHTML = '<i class="fas fa-' + (d.success ? 'check' : 'times') + '-circle"></i> ' + d.message;
    } catch(e) {
      res.style.display = 'block';
      res.className = 'alert alert-danger';
      res.innerHTML = 'Yanit okunamadi: ' + text.substring(0, 300);
    }
  })
  .catch(function(err) {
    res.style.display = 'block';
    res.className = 'alert alert-danger';
    res.innerHTML = 'Istek hatasi: ' + err.message;
  })
  .finally(function() {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-plug"></i> <?= $lTestEt ?>';
  });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>