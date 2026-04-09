<?php
header('Content-Type: text/html; charset=UTF-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/lang.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    die('<p style="font-family:sans-serif;text-align:center;padding:2rem;color:#e74c3c">Ge&#231;ersiz QR kodu</p>');
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM meetings WHERE qr_token = ?");
$stmt->execute([$token]);
$meeting = $stmt->fetch();

if (!$meeting) {
    die('<p style="font-family:sans-serif;text-align:center;padding:2rem;color:#e74c3c">Toplant&#305; bulunamad&#305;</p>');
}

if ($meeting['status'] === 'completed') {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Toplant&#305; Tamamland&#305;</title>
    <link rel="stylesheet" href="/assets/css/attend.css">
    </head><body class="attend-body">
    <div class="attend-container" style="text-align:center;padding:40px 24px">
      <div style="font-size:4rem;margin-bottom:16px">&#128274;</div>
      <h2 style="color:#1a2e4a;margin-bottom:8px">Toplant&#305; Tamamland&#305;</h2>
      <p style="color:#6c757d">Kat&#305;l&#305;m kayd&#305; kapat&#305;lm&#305;&#351;t&#305;r.</p>
      <p style="color:#6c757d;margin-top:8px;font-size:.85rem;font-weight:600">' .
      htmlspecialchars($meeting['title'], ENT_QUOTES, 'UTF-8') .
    '</p></div></body></html>');
}

if ($meeting['status'] === 'cancelled') {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>&#304;ptal</title>
    <link rel="stylesheet" href="/assets/css/attend.css">
    </head><body class="attend-body">
    <div class="attend-container" style="text-align:center;padding:40px 24px">
      <div style="font-size:4rem;margin-bottom:16px">&#10060;</div>
      <h2 style="color:#e74c3c;margin-bottom:8px">Toplant&#305; &#304;ptal Edildi</h2>
      <p style="color:#6c757d">' .
      htmlspecialchars($meeting['title'], ENT_QUOTES, 'UTF-8') .
    '</p></div></body></html>');
}

$_SESSION['attend_meeting_id']    = $meeting['id'];
$_SESSION['attend_meeting_token'] = $token;

$institutionName = getSetting('institution_name', '');
$hospitalName    = getSetting('hospital_name',    '');
$logoPath        = getSetting('logo_path', '');
$footerText      = getSetting('footer_text', '');
$ldapEnabled     = getSetting('ldap_enabled', '0') === '1';

$errorMsg = '';
if (isset($_GET['error']) && $_GET['error'] === 'ldap') {
    $errorMsg = $_SESSION['attend_error'] ?? '';
    unset($_SESSION['attend_error']);
}

$green = '#1e7e34';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
  <title><?= htmlspecialchars($hospitalName, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body.attend-body {
      font-family: 'Inter', system-ui, sans-serif;
      background: #f0f4f8;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .attend-container {
      background: #fff;
      border-radius: 20px;
      padding: 32px 28px;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.12);
    }
    .attend-header {
      display: flex; align-items: center; justify-content: center;
      gap: 12px; margin-bottom: 20px;
    }
    .attend-logo { height: 52px; object-fit: contain; }
    .attend-logo-icon {
      width: 52px; height: 52px; background: #1a2e4a;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center; color: #fff;
    }
    .attend-institution strong { display: block; font-size: .82rem; color: #1a2e4a; font-weight: 700; }
    .attend-institution span   { font-size: .72rem; color: #6c757d; }
    .attend-meeting-info {
      text-align: center; padding: 16px;
      background: #f8f9fa; border-radius: 12px; margin-bottom: 20px;
    }
    .attend-meeting-info h1 { font-size: 1.05rem; font-weight: 700; color: #1a2e4a; }
    .attend-meta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 8px; }
    .attend-meta span { font-size: .75rem; color: #6c757d; }
    .attend-title { text-align: center; font-size: 1.1rem; font-weight: 700; color: #1a2e4a; margin-bottom: 4px; }
    .attend-subtitle { text-align: center; color: #6c757d; font-size: .82rem; margin-bottom: 20px; }
    .attend-tabs {
      display: grid; grid-template-columns: 1fr 1fr;
      border-radius: 10px; overflow: hidden;
      border: 1.5px solid #e9ecef; margin-bottom: 20px;
    }
    .attend-tab {
      padding: 11px; text-align: center;
      font-size: .85rem; font-weight: 700;
      cursor: pointer; background: #fff;
      border: none; color: #6c757d; transition: .2s;
      display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .attend-tab.active { background: <?= $green ?>; color: #fff; }
    .attend-form .form-group { margin-bottom: 14px; }
    .attend-form label {
      display: block; font-size: .72rem;
      font-weight: 700; letter-spacing: .8px;
      color: #555; margin-bottom: 5px;
    }
    .attend-form .required { color: #e74c3c; margin-left: 2px; }
    .attend-form .form-control {
      width: 100%; padding: 10px 14px;
      border: 1.5px solid #e9ecef; border-radius: 10px;
      font-size: .9rem; transition: .2s;
      font-family: 'Inter', sans-serif; color: #1a2e4a; background: #fff;
    }
    .attend-form .form-control:focus {
      outline: none; border-color: <?= $green ?>;
      box-shadow: 0 0 0 3px rgba(30,126,52,0.1);
    }
    .attend-form .form-control::placeholder { color: #9ca3af; }
    .input-icon { position: relative; display: flex; align-items: center; }
    .input-icon .form-control { padding-right: 36px; }
    .toggle-pass {
      position: absolute; right: 10px;
      background: none; border: none; cursor: pointer; color: #9ca3af;
    }
    .btn-attend {
      width: 100%; padding: 14px;
      background: <?= $green ?>; color: #fff;
      border: none; border-radius: 10px;
      font-size: .9rem; font-weight: 700;
      letter-spacing: .8px; cursor: pointer;
      margin-top: 8px; transition: .2s;
      font-family: 'Inter', sans-serif;
    }
    .btn-attend:hover { background: #155724; }
    .attend-footer { text-align: center; margin-top: 20px; font-size: .72rem; color: #9ca3af; }
    .alert-error {
      background: #fef2f2; border: 1px solid #fecaca;
      color: #dc2626; border-radius: 8px;
      padding: 10px 14px; font-size: .84rem;
      margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
    }
  </style>
</head>
<body class="attend-body">
<div class="attend-container">

  <!-- Baþlýk -->
  <div class="attend-header">
    <?php if ($logoPath && file_exists('/var/www/html'.$logoPath)): ?>
      <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="attend-logo">
    <?php else: ?>
      <div class="attend-logo-icon">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#fff" stroke-width="1.8">
          <rect x="3" y="4" width="18" height="18" rx="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8"  y1="2" x2="8"  y2="6"/>
          <line x1="3"  y1="10" x2="21" y2="10"/>
        </svg>
      </div>
    <?php endif; ?>
    <div class="attend-institution">
      <strong><?= htmlspecialchars($institutionName, ENT_QUOTES, 'UTF-8') ?></strong>
      <span><?= htmlspecialchars($hospitalName, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>

  <!-- Toplantý Bilgisi -->
  <div class="attend-meeting-info">
    <h1><?= htmlspecialchars($meeting['title'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="attend-meta">
      <span>&#128197; <?= date('d.m.Y', strtotime($meeting['meeting_date'])) ?></span>
      <span>&#128336; <?= substr($meeting['meeting_time'],0,5) ?></span>
      <?php if ($meeting['location']): ?>
        <span>&#128205; <?= htmlspecialchars($meeting['location'], ENT_QUOTES, 'UTF-8') ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="attend-title"><?= $LANG['attend_welcome'] ?></div>
  <p class="attend-subtitle"><?= $LANG['attend_sub'] ?></p>

  <?php if ($ldapEnabled): ?>
  <!-- LDAP aktif: Personel + Misafir sekmeleri -->
  <div class="attend-tabs">
    <button class="attend-tab active" id="tabStaff" onclick="switchTab('staff')">
      <?= $LANG['tab_staff'] ?>
    </button>
    <button class="attend-tab" id="tabGuest" onclick="switchTab('guest')">
      <?= $LANG['tab_guest'] ?>
    </button>
  </div>

  <!-- PERSONEL FORMU -->
  <div id="formStaff" class="attend-form">
    <?php if ($errorMsg): ?>
    <div class="alert-error">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>
    <form method="POST" action="/attend/staff.php">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
      <div class="form-group">
        <label><?= $LANG['field_username'] ?> <span class="required">*</span></label>
        <input type="text" name="username" class="form-control"
               placeholder="&#214;rn: kaan.oz" autocomplete="username" required>
      </div>
      <div class="form-group">
        <label><?= $LANG['field_password'] ?> <span class="required">*</span></label>
        <div class="input-icon">
          <input type="password" name="password" class="form-control"
                 placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" required>
          <button type="button" class="toggle-pass" onclick="togglePass(this)">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="form-group">
        <label><?= $LANG['field_unit'] ?> <span class="required">*</span></label>
        <input type="text" name="unit" class="form-control"
               placeholder="&#214;rn: Onkoloji Servisi" required>
      </div>
      <div class="form-group">
        <label><?= $LANG['field_title'] ?> <span class="required">*</span></label>
        <input type="text" name="title_field" class="form-control"
               placeholder="&#214;rn: Uzman Doktor" required>
      </div>
      <button type="submit" class="btn-attend"><?= $LANG['btn_save'] ?></button>
    </form>
  </div>

  <!-- MÝSAFÝR FORMU (LDAP aktif) -->
  <div id="formGuest" class="attend-form" style="display:none">

  <?php else: ?>
  <!-- LDAP kapalý: sadece misafir formu, sekme yok -->
  <div id="formGuest" class="attend-form">
  <?php endif; ?>

    <form method="POST" action="/attend/guest.php">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
      <div class="form-group">
        <label><?= $LANG['field_fullname'] ?> <span class="required">*</span></label>
        <input type="text" name="full_name" class="form-control"
               placeholder="&#214;rn: Kaan &#214;Z" required>
      </div>
      <div class="form-group">
        <label><?= $LANG['field_institution'] ?> <span class="required">*</span></label>
        <input type="text" name="institution" class="form-control"
               placeholder="&#214;rn: Onkoloji Hastanesi" required>
      </div>
      <div class="form-group">
        <label><?= $LANG['field_title'] ?> <span class="required">*</span></label>
        <input type="text" name="title_field" class="form-control"
               placeholder="&#214;rn: Prof&#246;s&#246;r" required>
      </div>
      <div class="form-group">
        <label><?= $LANG['field_email'] ?> <span class="required">*</span></label>
        <input type="email" name="email" class="form-control"
               placeholder="&#214;rn: isim@mail.com" required>
      </div>
      <div class="form-group">
        <label><?= $LANG['field_phone'] ?> <span class="required">*</span></label>
        <input type="tel" name="phone" id="phoneInput" class="form-control"
               placeholder="5XX XXX XX XX"
               maxlength="10" pattern="[0-9]{10}" inputmode="numeric" required>
      </div>
      <button type="submit" class="btn-attend"><?= $LANG['btn_save'] ?></button>
    </form>
  </div>

  <div class="attend-footer"><?= htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8') ?></div>
</div>

<script>
<?php if ($ldapEnabled): ?>
function switchTab(t) {
  document.getElementById('formStaff').style.display = t === 'staff' ? 'block' : 'none';
  document.getElementById('formGuest').style.display = t === 'guest' ? 'block' : 'none';
  document.getElementById('tabStaff').className = 'attend-tab' + (t === 'staff' ? ' active' : '');
  document.getElementById('tabGuest').className = 'attend-tab' + (t === 'guest' ? ' active' : '');
}
<?php if (isset($_GET['error']) && $_GET['error'] === 'ldap'): ?>
switchTab('staff');
<?php endif; ?>
<?php endif; ?>

function togglePass(btn) {
  var inp = btn.closest('.input-icon').querySelector('input');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

var phoneInput = document.getElementById('phoneInput');
if (phoneInput) {
  phoneInput.addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
  });
}
</script>
</body>
</html>
