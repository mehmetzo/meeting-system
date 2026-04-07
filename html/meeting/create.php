<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/lang.php';
requireAdmin();

$pageTitle = $LANG['create_meeting'];
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']        ?? '');
    $desc     = trim($_POST['description']  ?? '');
    $date     = $_POST['meeting_date']      ?? '';
    $time     = $_POST['meeting_time']      ?? '';
    $location = trim($_POST['location']     ?? '');

    if (!$title || !$date || !$time) {
        $lZorunlu = 'Toplant' . chr(0xC4).chr(0xB1) . ' ad' . chr(0xC4).chr(0xB1)
                  . ', tarih ve saat zorunludur.';
        $error = $lZorunlu;
    } else {
        $admin = getCurrentAdmin();
        $db    = getDB();
        $code  = 'MTG-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $token = bin2hex(random_bytes(32));

        $stmt = $db->prepare(
            "INSERT INTO meetings
             (meeting_code, title, description, meeting_date, meeting_time,
              location, organizer_id, organizer_name, qr_token)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $code, $title, $desc, $date, $time,
            $location, $admin['id'], $admin['name'], $token
        ]);
        $meetingId = $db->lastInsertId();
        logAccess('meeting_created', "Toplanti olusturuldu: {$title}", 'success');
        header("Location: /meeting/qr.php?id={$meetingId}");
        exit;
    }
}

// Türkçe etiketler
$lBaslik      = 'Toplant' . chr(0xC4).chr(0xB1) . ' Olu' . chr(0xC5).chr(0x9F) . 'tur';
$lAltBaslik   = 'Yeni bir toplant' . chr(0xC4).chr(0xB1) . ' kayd' . chr(0xC4).chr(0xB1) . ' olu' . chr(0xC5).chr(0x9F) . 'turun';
$lToplBilgi   = 'Toplant' . chr(0xC4).chr(0xB1) . ' Bilgileri';
$lAdi         = 'Toplant' . chr(0xC4).chr(0xB1) . ' Ad' . chr(0xC4).chr(0xB1) . ' / Konusu';
$lAdiPH       = 'Ayl' . chr(0xC4).chr(0xB1) . 'k Koordinasyon Toplant' . chr(0xC4).chr(0xB1) . 's' . chr(0xC4).chr(0xB1);
$lAciklama    = 'A' . chr(0xC3).chr(0xA7) . 'klama';
$lAciklamaPH  = 'Toplant' . chr(0xC4).chr(0xB1) . ' hakk' . chr(0xC4).chr(0xB1) . 'nda k' . chr(0xC4).chr(0xB1) . 'sa a' . chr(0xC3).chr(0xA7) . 'klama...';
$lTarih       = 'Tarih';
$lSaat        = 'Saat';
$lKonum       = 'Toplant' . chr(0xC4).chr(0xB1) . ' Yeri / Salonu';
$lKonumPH     = 'Konferans Salonu A, 3. Kat';
$lBtn         = 'Toplant' . chr(0xC4).chr(0xB1) . ' Olu' . chr(0xC5).chr(0x9F) . 'tur &amp; QR ' . chr(0xC3).chr(0x9C) . 'ret';
$lIptal       = chr(0xC4).chr(0xB0) . 'ptal';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-title"><?= $lBaslik ?></h1>
        <p class="page-sub"><?= $lAltBaslik ?></p>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <div class="card" style="max-width:720px">
      <div class="card-header">
        <h3><i class="fas fa-calendar-plus"></i> <?= $lToplBilgi ?></h3>
      </div>
      <div class="card-body">
        <form method="POST">

          <div class="form-group">
            <label><?= $lAdi ?> <span class="required">*</span></label>
            <input type="text" name="title" class="form-control"
                   placeholder="&#214;rn: <?= $lAdiPH ?>"
                   value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   required>
          </div>

          <div class="form-group">
            <label><?= $lAciklama ?></label>
            <textarea name="description" class="form-control" rows="3"
                      placeholder="<?= $lAciklamaPH ?>"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label><?= $lTarih ?> <span class="required">*</span></label>
              <input type="text" name="meeting_date" id="meetingDate" class="form-control"
                     placeholder="gg.aa.yyyy"
                     value="<?= isset($_POST['meeting_date']) ? date('d.m.Y', strtotime($_POST['meeting_date'])) : '' ?>"
                     required>
            </div>
            <div class="form-group">
              <label><?= $lSaat ?> <span class="required">*</span></label>
              <input type="text" name="meeting_time" id="meetingTime" class="form-control"
                     placeholder="SS:DD"
                     value="<?= htmlspecialchars($_POST['meeting_time'] ?? date('H:i'), ENT_QUOTES, 'UTF-8') ?>"
                     required>
            </div>
          </div>

          <div class="form-group">
            <label><?= $lKonum ?></label>
            <input type="text" name="location" class="form-control"
                   placeholder="&#214;rn: <?= $lKonumPH ?>"
                   value="<?= htmlspecialchars($_POST['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-qrcode"></i> <?= $lBtn ?>
            </button>
            <a href="/admin/index.php" class="btn btn-outline-secondary"><?= $lIptal ?></a>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
<script>
// Tarih — Türkçe takvim
flatpickr("#meetingDate", {
  locale: "tr",
  dateFormat: "Y-m-d",
  altInput: true,
  altFormat: "d.m.Y",
  allowInput: true,
  minDate: "today"
});

// Saat — 24 saat formatý
flatpickr("#meetingTime", {
  enableTime: true,
  noCalendar: true,
  dateFormat: "H:i",
  time_24hr: true,
  allowInput: true,
  defaultDate: "<?= htmlspecialchars($_POST['meeting_time'] ?? date('H:i'), ENT_QUOTES, 'UTF-8') ?>"
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>