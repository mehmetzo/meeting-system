<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/lang.php';

$hospitalName    = getSetting('hospital_name',    'Dijital Toplanti Sistemi');
$institutionName = getSetting('institution_name', 'Kurum Adi');
$logoPath        = getSetting('logo_path', '');
$footerText      = getSetting('footer_text', '');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($hospitalName, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#1a2e4a 0%,#1a5276 60%,#2980b9 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;color:#fff}
    .box{text-align:center;max-width:560px;padding:32px}
    .logo-icon{width:96px;height:96px;background:rgba(255,255,255,.15);border-radius:24px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:24px}
    .logo-img{height:96px;object-fit:contain;border-radius:16px;margin-bottom:24px;display:block;margin-left:auto;margin-right:auto}
    h1{font-size:1rem;font-weight:400;opacity:.7;margin-bottom:4px}
    h2{font-size:2rem;font-weight:800;margin-bottom:8px}
    p{opacity:.7;font-size:.95rem;margin-bottom:32px}
    .badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:100px;padding:6px 18px;font-size:.78rem;font-weight:600;margin-bottom:24px}
    .dot{width:8px;height:8px;background:#2ecc71;border-radius:50%;animation:pulse 1.5s infinite}
    .version{opacity:.4;font-size:.72rem;margin-top:24px}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
  </style>
</head>
<body>
<div class="box">
  <?php if ($logoPath && file_exists('/var/www/html'.$logoPath)): ?>
    <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="logo-img">
  <?php else: ?>
    <div class="logo-icon">
      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#fff" stroke-width="1.5">
        <rect x="3" y="4" width="18" height="18" rx="2"/>
        <line x1="16" y1="2" x2="16" y2="6"/>
        <line x1="8" y1="2" x2="8" y2="6"/>
        <line x1="3" y1="10" x2="21" y2="10"/>
      </svg>
    </div>
  <?php endif; ?>
  <div class="badge"><span class="dot"></span> <?= $LANG['system_active'] ?></div>
  <h1><?= htmlspecialchars($institutionName, ENT_QUOTES, 'UTF-8') ?></h1>
  <h2><?= htmlspecialchars($hospitalName, ENT_QUOTES, 'UTF-8') ?></h2>
  <p><?= $LANG['welcome_title'] ?></p>
  <div class="version"><?= htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8') ?></div>
</div>
</body>
</html>
