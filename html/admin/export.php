<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/lang.php';
requireAdmin();

$type      = $_GET['type'] ?? 'page';
$meetingId = (int)($_GET['meeting_id'] ?? 0);
$db        = getDB();

if ($type === 'csv') {
    $where  = $meetingId ? "WHERE a.meeting_id = {$meetingId}" : '';
    $result = $db->query(
        "SELECT m.title, m.meeting_date, m.meeting_time, m.location,
                a.attendee_type, a.full_name, a.tc_no, a.phone,
                a.email, a.institution, a.title, a.unit,
                a.ldap_username, a.ip_address, a.attended_at
         FROM attendees a
         JOIN meetings m ON a.meeting_id = m.id
         {$where}
         ORDER BY a.attended_at DESC"
    )->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="katilimcilar_' . date('Ymd_His') . '.csv"');
    header('Cache-Control: no-cache');
    $f = fopen('php://output', 'w');
    fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($f, ['Toplanti','Tarih','Saat','Yer','Tur','Ad Soyad',
                 'TC No','Telefon','E-Posta','Kurum','Unvan','Birim',
                 'LDAP Kullanici','IP Adresi','Katilim Tarihi']);
    foreach ($result as $row) { fputcsv($f, $row); }
    fclose($f);
    logAccess('export_csv', 'CSV disa aktarma', 'success');
    exit;
}

if ($type === 'pdf') {
    $where = $meetingId ? "WHERE a.meeting_id = {$meetingId}" : '';
    $rows  = $db->query(
        "SELECT m.title, a.attendee_type, a.full_name, a.tc_no,
                a.phone, a.institution, a.title as atitle, a.attended_at
         FROM attendees a JOIN meetings m ON a.meeting_id = m.id
         {$where} ORDER BY a.attended_at DESC LIMIT 500"
    )->fetchAll();

    $institution = getSetting('institution_name', '');
    $hospital    = getSetting('hospital_name', '');

    $meetingTitle = '';
    $meetingDate  = '';
    if ($meetingId) {
        $mRow = $db->prepare("SELECT title, meeting_date FROM meetings WHERE id=?");
        $mRow->execute([$meetingId]);
        $mRow = $mRow->fetch();
        if ($mRow) {
            $meetingTitle = $mRow['title'];
            $meetingDate  = date('d.m.Y', strtotime($mRow['meeting_date']));
        }
    }

    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rapor_' . date('Ymd') . '.html"');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>
      body{font-family:Arial,sans-serif;font-size:11px;margin:20px}
      h2,h3{color:#1a5276;margin:0 0 4px}
      p{margin:0 0 12px;font-size:10px;color:#555}
      table{width:100%;border-collapse:collapse;margin-top:8px}
      th,td{border:1px solid #ccc;padding:5px 7px;text-align:left}
      th{background:#1a5276;color:#fff;font-size:10px}
      tr:nth-child(even){background:#f8f9fa}
      .badge{padding:2px 6px;border-radius:3px;font-size:9px;font-weight:700}
      .staff{background:#cce5ff;color:#004085}
      .guest{background:#e2e3e5;color:#383d41}
    </style></head><body>';
    echo '<h2>' . htmlspecialchars($institution, ENT_QUOTES, 'UTF-8') . '</h2>';
    echo '<h3>' . htmlspecialchars($hospital,    ENT_QUOTES, 'UTF-8') . '</h3>';
    if ($meetingTitle) {
        echo '<p><strong>Toplant&#305;:</strong> ' . htmlspecialchars($meetingTitle, ENT_QUOTES, 'UTF-8') . ' &mdash; ' . $meetingDate . '</p>';
    }
    echo '<p>Rapor tarihi: ' . date('d.m.Y H:i') . ' &nbsp;|&nbsp; Toplam: ' . count($rows) . ' kat&#305;l&#305;mc&#305;</p>';
    echo '<table><tr>
            <th>#</th><th>Ad Soyad</th><th>T&#252;r</th>
            <th>TC No</th><th>Telefon</th><th>Kurum</th>
            <th>&#220;nvan</th><th>Kat&#305;l&#305;m</th>
          </tr>';
    foreach ($rows as $i => $r) {
        $typeLabel = $r['attendee_type']==='staff'
            ? '<span class="badge staff">Personel</span>'
            : '<span class="badge guest">Misafir</span>';
        echo '<tr>';
        echo '<td>' . ($i+1) . '</td>';
        echo '<td>' . htmlspecialchars($r['full_name'],   ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . $typeLabel . '</td>';
        echo '<td>' . ($r['tc_no']       ? htmlspecialchars($r['tc_no'],       ENT_QUOTES, 'UTF-8') : '-') . '</td>';
        echo '<td>' . ($r['phone']       ? htmlspecialchars($r['phone'],       ENT_QUOTES, 'UTF-8') : '-') . '</td>';
        echo '<td>' . ($r['institution'] ? htmlspecialchars($r['institution'], ENT_QUOTES, 'UTF-8') : '-') . '</td>';
        echo '<td>' . ($r['atitle']      ? htmlspecialchars($r['atitle'],      ENT_QUOTES, 'UTF-8') : '-') . '</td>';
        echo '<td>' . date('d.m.Y H:i', strtotime($r['attended_at'])) . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    logAccess('export_pdf', 'PDF rapor disa aktarma', 'success');
    exit;
}

// Sayfa görünümü
$lBaslik   = 'D' . chr(0xC4).chr(0xB1) . chr(0xC5).chr(0x9F) . 'a Aktar';
$lIndirme  = 'Toplant' . chr(0xC4).chr(0xB1) . 'ya G' . chr(0xC3).chr(0xB6) . 're ' . chr(0xC4).chr(0xB0) . 'ndirme';
$lSec      = 'Toplant' . chr(0xC4).chr(0xB1) . ' Se' . chr(0xC3).chr(0xA7) . 'in';
$lCSVIndir = 'CSV Dosyas' . chr(0xC4).chr(0xB1) . ' ' . chr(0xC4).chr(0xB0) . 'ndir';
$lPDFIndir = 'PDF Raporu ' . chr(0xC4).chr(0xB0) . 'ndir';
$lUyari    = 'L' . chr(0xC3).chr(0xBC) . 'tfen bir toplant' . chr(0xC4).chr(0xB1) . ' se' . chr(0xC3).chr(0xA7) . 'in';

$pageTitle = $lBaslik;
$meetings  = $db->query(
    "SELECT id, title, meeting_date FROM meetings ORDER BY meeting_date DESC"
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-title"><?= $lBaslik ?></h1>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-filter"></i> <?= $lIndirme ?></h3>
      </div>
      <div class="card-body">
        <div class="form-group">
          <label><?= $lSec ?></label>
          <select id="meetingSelect" class="form-control" style="max-width:500px">
            <option value="">-- <?= $lSec ?> --</option>
            <?php foreach ($meetings as $m): ?>
              <option value="<?= $m['id'] ?>">
                <?= htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8') ?>
                &mdash; <?= date('d.m.Y', strtotime($m['meeting_date'])) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;max-width:320px">
          <a href="#" onclick="exportMeeting('csv')" class="btn btn-success">
            <i class="fas fa-file-csv"></i> <?= $lCSVIndir ?>
          </a>
          <a href="#" onclick="exportMeeting('pdf')" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> <?= $lPDFIndir ?>
          </a>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
var lUyari = '<?= $lUyari ?>';
function exportMeeting(type) {
  var id = document.getElementById('meetingSelect').value;
  if (!id) { alert(lUyari); return; }
  window.location.href = '/admin/export.php?type=' + type + '&meeting_id=' + id;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>