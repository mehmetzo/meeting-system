<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/lang.php';
requireAdmin();

$id   = (int)($_GET['id'] ?? 0);
$db   = getDB();
$stmt = $db->prepare("SELECT * FROM meetings WHERE id = ?");
$stmt->execute([$id]);
$meeting = $stmt->fetch();

if (!$meeting) {
    header('Location: /admin/meetings.php');
    exit;
}

$attendUrl   = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
             . '://' . $_SERVER['HTTP_HOST']
             . '/attend/?token=' . $meeting['qr_token'];

$pageTitle   = $LANG['qr_title'];
$logo        = getSetting('logo_path', '');
$institution = getSetting('institution_name', '');
$hospital    = getSetting('hospital_name', '');
$footer      = getSetting('footer_text', '');

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-title"><?= $LANG['qr_title'] ?></h1>
        <p class="page-sub"><?= htmlspecialchars($meeting['title'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div class="header-actions">
        <button onclick="window.print()" class="btn btn-primary">
          <i class="fas fa-print"></i> <?= $LANG['qr_print'] ?>
        </button>
        <a href="/meeting/report.php?id=<?= $id ?>" class="btn btn-outline-secondary">
          <i class="fas fa-chart-bar"></i> <?= $LANG['qr_report'] ?>
        </a>
      </div>
    </div>

    <!-- EKRAN GÖRÜNÜMÜ -->
    <div class="qr-page-layout" id="screenView">
      <div class="qr-card">
        <div class="qr-card-header">
          <?php if ($logo && file_exists('/var/www/html'.$logo)): ?>
            <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="qr-logo">
          <?php endif; ?>
          <div class="qr-institution">
            <strong><?= htmlspecialchars($institution, ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars($hospital, ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        </div>
        <div class="qr-title"><?= $LANG['qr_form_title'] ?></div>
        <div class="qr-meeting-info">
          <h2><?= htmlspecialchars($meeting['title'], ENT_QUOTES, 'UTF-8') ?></h2>
          <div class="qr-meta">
            <span>&#128197; <?= date('d.m.Y', strtotime($meeting['meeting_date'])) ?></span>
            <span>&#128336; <?= substr($meeting['meeting_time'],0,5) ?></span>
            <?php if ($meeting['location']): ?>
              <span>&#128205; <?= htmlspecialchars($meeting['location'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="qr-code-container">
          <div id="qrcode"></div>
          <p class="qr-instruction">
            <i class="fas fa-qrcode"></i> <?= $LANG['qr_instruction'] ?>
          </p>
        </div>
        <div class="qr-code-text"><?= htmlspecialchars($meeting['meeting_code'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>

      <!-- Sað panel -->
      <div class="qr-side-panel">
        <div class="card">
          <div class="card-header"><h3><?= $LANG['qr_details'] ?></h3></div>
          <div class="card-body">
            <table class="detail-table">
              <tr><th>Kod</th><td><code><?= htmlspecialchars($meeting['meeting_code'], ENT_QUOTES, 'UTF-8') ?></code></td></tr>
              <tr><th>Tarih</th><td><?= date('d.m.Y', strtotime($meeting['meeting_date'])) ?></td></tr>
              <tr><th>Saat</th><td><?= substr($meeting['meeting_time'],0,5) ?></td></tr>
              <tr><th>Konum</th><td><?= htmlspecialchars($meeting['location'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
              <tr><th>D&#252;zenleyen</th><td><?= htmlspecialchars($meeting['organizer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
            </table>
          </div>
        </div>
        <div class="card mt-3">
          <div class="card-header"><h3><?= $LANG['qr_link'] ?></h3></div>
          <div class="card-body">
            <input type="text" value="<?= htmlspecialchars($attendUrl, ENT_QUOTES, 'UTF-8') ?>"
                   id="attendUrl" class="form-control" readonly>
            <button onclick="copyUrl()" class="btn btn-sm btn-outline-primary mt-1">
              <i class="fas fa-copy"></i> <?= $LANG['qr_copy'] ?>
            </button>
          </div>
        </div>
        <div class="card mt-3">
          <div class="card-header"><h3><?= $LANG['qr_live'] ?></h3></div>
          <div class="card-body" id="liveAttendees">
            <div class="text-center text-muted">Y&#252;kleniyor...</div>
          </div>
        </div>
      </div>
    </div>

    <!-- YAZDIR ALANI — sadece baskýda görünür -->
    <div id="printArea">
      <div class="print-qr-page">
        <div class="print-left">
          <div class="print-header">
            <?php if ($logo && file_exists('/var/www/html'.$logo)): ?>
              <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="print-logo">
            <?php endif; ?>
            <div>
              <div class="print-institution"><?= htmlspecialchars($institution, ENT_QUOTES, 'UTF-8') ?></div>
              <div class="print-hospital"><?= htmlspecialchars($hospital, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          </div>
          <div class="print-form-title">TOPLANTI KATILIM FORMU</div>
          <div class="print-meeting-title"><?= htmlspecialchars($meeting['title'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="print-meta">
            <span>&#128197; <?= date('d.m.Y', strtotime($meeting['meeting_date'])) ?></span>
            <span>&#128336; <?= substr($meeting['meeting_time'],0,5) ?></span>
            <?php if ($meeting['location']): ?>
              <span>&#128205; <?= htmlspecialchars($meeting['location'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="print-right">
          <div id="qrcode-print"></div>
          <div class="print-instruction"><?= $LANG['qr_instruction'] ?></div>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// Ekran QR
new QRCode(document.getElementById("qrcode"), {
  text: "<?= addslashes($attendUrl) ?>",
  width: 260, height: 260,
  colorDark: "#1a2e4a", colorLight: "#ffffff",
  correctLevel: QRCode.CorrectLevel.H
});
// Baský QR
new QRCode(document.getElementById("qrcode-print"), {
  text: "<?= addslashes($attendUrl) ?>",
  width: 300, height: 300,
  colorDark: "#000000", colorLight: "#ffffff",
  correctLevel: QRCode.CorrectLevel.H
});

function copyUrl() {
  document.getElementById('attendUrl').select();
  document.execCommand('copy');
}

function loadAttendees() {
  fetch('/admin/api.php?action=live_attendees&id=<?= $id ?>')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var el = document.getElementById('liveAttendees');
      if (!data.length) {
        el.innerHTML = '<p class="text-muted text-center"><?= $LANG['qr_no_attendee'] ?></p>';
        return;
      }
      el.innerHTML = data.map(function(a) {
        return '<div class="attendee-item">' +
          '<span class="attendee-type ' + a.type + '">' + (a.type==='staff'?'Personel':'Misafir') + '</span>' +
          '<span>' + a.name + '</span>' +
          '<small>' + a.time + '</small></div>';
      }).join('');
    });
}
loadAttendees();
setInterval(loadAttendees, 10000);
</script>

<style>
/* Ekran stilleri */
.qr-instruction {
  font-size: 1.1rem !important;
  font-weight: 700 !important;
  margin-top: 14px;
  color: #1a2e4a;
  text-align: center;
}
#qrcode img, #qrcode canvas { width: 260px !important; height: 260px !important; }
.qr-meeting-info h2 { font-size: 1.4rem !important; font-weight: 800 !important; }

/* Yazdýr alaný ekranda gizli */
#printArea { display: none; }

/* YAZDIR MEDYA */
@media print {
  /* Tarayýcý üst/alt bilgisini gizle */
  @page {
    size: A4 landscape;
    margin: 0;
  }

  /* Her þeyi gizle, sadece printArea göster */
  body > *                          { display: none !important; }
  .sidebar, .page-header,
  .header-actions, #screenView,
  .qr-side-panel                    { display: none !important; }

  /* app-layout ve main-content sýfýrla */
  .app-layout   { display: block !important; margin: 0 !important; padding: 0 !important; }
  .main-content { display: block !important; margin: 0 !important; padding: 0 !important; }

  /* printArea göster */
  #printArea {
    display: block !important;
    position: fixed;
    top: 0; left: 0;
    width: 297mm;
    height: 210mm;
  }

  .print-qr-page {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    width: 297mm;
    height: 210mm;
    padding: 14mm 16mm;
    box-sizing: border-box;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .print-left  { flex: 1; padding-right: 16mm; }
  .print-right {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .print-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
  }
  .print-logo        { height: 56px; object-fit: contain; }
  .print-institution { font-size: 12pt; font-weight: 700; color: #1a2e4a; }
  .print-hospital    { font-size: 10pt; color: #555; margin-top: 2px; }
  .print-form-title  {
    font-size: 8pt; font-weight: 800;
    letter-spacing: 2.5px; color: #888;
    text-transform: uppercase; margin-bottom: 14px;
  }
  .print-meeting-title {
    font-size: 24pt; font-weight: 900;
    color: #1a2e4a; margin-bottom: 16px;
    line-height: 1.2;
  }
  .print-meta         { display: flex; flex-direction: column; gap: 6px; }
  .print-meta span    { font-size: 11pt; color: #444; display: block; }
  .print-instruction  {
    font-size: 14pt; font-weight: 800;
    color: #1a2e4a; text-align: center;
    margin-top: 14px; max-width: 280px;
    line-height: 1.4;
  }
  #qrcode-print img,
  #qrcode-print canvas {
    width: 290px !important;
    height: 290px !important;
  }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>