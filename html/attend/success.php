<?php
header('Content-Type: text/html; charset=UTF-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/lang.php';

$name    = htmlspecialchars($_SESSION['attend_success_name'] ?? 'Katilimci', ENT_QUOTES, 'UTF-8');
$type    = $_GET['type']   ?? 'guest';
$already = isset($_GET['already']);
$logo    = getSetting('logo_path', '');
$footer  = getSetting('footer_text', '');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= $LANG['success_title'] ?></title>
  <link rel="stylesheet" href="/assets/css/attend.css">
  <style>
    .success-container{text-align:center}
    .success-icon{font-size:4rem;color:#2ecc71;margin:16px 0}
    .success-container h2{font-size:1.3rem;color:#1a2e4a;margin-bottom:8px}
    .success-container p{color:#6c757d;font-size:.9rem}
    .success-time{margin-top:16px;font-size:.8rem;color:#6c757d}
    .success-type{margin:12px 0}
    .badge{padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:600;display:inline-block}
    .badge-primary{background:#cce5ff;color:#004085}
    .badge-secondary{background:#e2e3e5;color:#383d41}
  </style>
</head>
<body class="attend-body">
<div class="attend-container success-container">
  <?php if ($logo && file_exists('/var/www/html'.$logo)): ?>
    <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo"
         style="height:60px;margin-bottom:16px">
  <?php endif; ?>

  <div class="success-icon">&#10003;</div>

  <?php if ($already): ?>
    <h2><?= $LANG['already_registered'] ?></h2>
    <p>Say&#305;n <strong><?= $name ?></strong>, bu toplant&#305;ya daha &#246;nce kat&#305;ld&#305;n&#305;z.</p>
  <?php else: ?>
    <h2><?= $LANG['success_title'] ?></h2>
    <p>Say&#305;n <strong><?= $name ?></strong>, <?= $LANG['success_msg'] ?></p>
    <div class="success-type">
      <span class="badge <?= $type==='staff' ? 'badge-primary' : 'badge-secondary' ?>">
        <?= $type==='staff' ? $LANG['success_staff'] : $LANG['success_guest'] ?>
      </span>
    </div>
  <?php endif; ?>

  <p class="success-time">&#128336; <?= date('d.m.Y H:i:s') ?></p>
  <div class="attend-footer"><?= htmlspecialchars($footer, ENT_QUOTES, 'UTF-8') ?></div>
</div>
</body>
</html>