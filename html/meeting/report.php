<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/lang.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM meetings WHERE id = ?");
$stmt->execute([$id]);
$meeting = $stmt->fetch();

if (!$meeting) {
    header('Location: /admin/meetings.php');
    exit;
}

$attendees = $db->prepare("SELECT * FROM attendees WHERE meeting_id = ? ORDER BY attended_at ASC");
$attendees->execute([$id]);
$attendees = $attendees->fetchAll();

$staffCount = count(array_filter($attendees, fn($a) => $a['attendee_type'] === 'staff'));
$guestCount = count(array_filter($attendees, fn($a) => $a['attendee_type'] === 'guest'));

$statusMap = [
    'active'    => ['Aktif',       'badge-success'],
    'completed' => ['Tamamlandi',  'badge-secondary'],
    'cancelled' => ['Iptal',       'badge-danger'],
];
$statusInfo = $statusMap[$meeting['status']] ?? ['Bilinmiyor', 'badge-info'];

$meetingDate     = $meeting['meeting_date']  ? date('d.m.Y', strtotime($meeting['meeting_date']))  : '-';
$meetingTime     = $meeting['meeting_time']  ? substr($meeting['meeting_time'], 0, 5)               : '-';
$meetingLocation = $meeting['location']      ? htmlspecialchars($meeting['location'], ENT_QUOTES, 'UTF-8') : '-';
$organizerName   = $meeting['organizer_name']? htmlspecialchars($meeting['organizer_name'], ENT_QUOTES, 'UTF-8') : '-';
$meetingTitle    = htmlspecialchars($meeting['title'], ENT_QUOTES, 'UTF-8');
$meetingDesc     = $meeting['description']   ? htmlspecialchars($meeting['description'], ENT_QUOTES, 'UTF-8') : '';

$pageTitle = 'Toplanti Raporu';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-title">Toplant&#305; Raporu</h1>
        <p class="page-sub"><?= $meetingTitle ?></p>
      </div>
      <div class="header-actions">
        <a href="/admin/export.php?type=csv&meeting_id=<?= $id ?>" class="btn btn-outline-primary">
          <i class="fas fa-file-csv"></i> CSV
        </a>
        <a href="/admin/export.php?type=pdf&meeting_id=<?= $id ?>" class="btn btn-outline-danger">
          <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="/meeting/qr.php?id=<?= $id ?>" class="btn btn-outline-secondary">
          <i class="fas fa-qrcode"></i> QR Kod
        </a>
        <a href="/admin/meetings.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left"></i> Geri
        </a>
      </div>
    </div>

    <!-- Özet Kartlar -->
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));margin-bottom:20px">
      <div class="stat-card stat-info">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= count($attendees) ?></div>
          <div class="stat-label">Toplam Kat&#305;l&#305;mc&#305;</div>
        </div>
      </div>
      <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $staffCount ?></div>
          <div class="stat-label">Personel</div>
        </div>
      </div>
      <div class="stat-card stat-teal">
        <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $guestCount ?></div>
          <div class="stat-label">Misafir</div>
        </div>
      </div>
      <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-calendar"></i></div>
        <div class="stat-body">
          <div class="stat-value" style="font-size:1.8rem"><?= $meetingDate ?></div>
          <div class="stat-label">Toplant&#305; Tarihi</div>
        </div>
      </div>
    </div>

    <!-- Toplantı Bilgileri -->
    <div class="card mb-3">
      <div class="card-header">
        <h3><i class="fas fa-info-circle"></i> Toplant&#305; Bilgileri</h3>
      </div>
      <div class="card-body">
        <table style="width:100%;border-collapse:collapse">
          <tr>
            <td style="padding:8px 0;width:160px;color:#6c757d;font-weight:600;font-size:.83rem;vertical-align:top">Toplant&#305; Ad&#305;</td>
            <td style="padding:8px 0;font-size:.88rem"><?= $meetingTitle ?></td>
          </tr>
          <tr>
            <td style="padding:8px 0;color:#6c757d;font-weight:600;font-size:.83rem;border-top:1px solid #f0f4f8;vertical-align:top">Tarih</td>
            <td style="padding:8px 0;font-size:.88rem;border-top:1px solid #f0f4f8"><?= $meetingDate ?></td>
          </tr>
          <tr>
            <td style="padding:8px 0;color:#6c757d;font-weight:600;font-size:.83rem;border-top:1px solid #f0f4f8;vertical-align:top">Saat</td>
            <td style="padding:8px 0;font-size:.88rem;border-top:1px solid #f0f4f8"><?= $meetingTime ?></td>
          </tr>
          <tr>
            <td style="padding:8px 0;color:#6c757d;font-weight:600;font-size:.83rem;border-top:1px solid #f0f4f8;vertical-align:top">Konum</td>
            <td style="padding:8px 0;font-size:.88rem;border-top:1px solid #f0f4f8"><?= $meetingLocation ?></td>
          </tr>
          <tr>
            <td style="padding:8px 0;color:#6c757d;font-weight:600;font-size:.83rem;border-top:1px solid #f0f4f8;vertical-align:top">D&#252;zenleyen</td>
            <td style="padding:8px 0;font-size:.88rem;border-top:1px solid #f0f4f8"><?= $organizerName ?></td>
          </tr>
          <tr>
            <td style="padding:8px 0;color:#6c757d;font-weight:600;font-size:.83rem;border-top:1px solid #f0f4f8;vertical-align:top">Durum</td>
            <td style="padding:8px 0;border-top:1px solid #f0f4f8">
              <span class="badge <?= $statusInfo[1] ?>"><?= $statusInfo[0] ?></span>
            </td>
          </tr>
          <?php if ($meetingDesc): ?>
          <tr>
            <td style="padding:8px 0;color:#6c757d;font-weight:600;font-size:.83rem;border-top:1px solid #f0f4f8;vertical-align:top">A&#231;&#305;klama</td>
            <td style="padding:8px 0;font-size:.88rem;border-top:1px solid #f0f4f8"><?= $meetingDesc ?></td>
          </tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Katılımcı Listesi -->
    <div class="card">
      <div class="card-header d-flex-between">
        <h3><i class="fas fa-users"></i> Kat&#305;l&#305;mc&#305;lar (<?= count($attendees) ?>)</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>TUR</th>
                <th>AD SOYAD</th>
                <th>TC NO</th>
                <th>TELEFON</th>
                <th>KURUM / BIRIM</th>
                <th>UNVAN</th>
                <th>KATILIM SAATI</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($attendees as $i => $a): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td>
                  <span class="badge <?= $a['attendee_type']==='staff' ? 'badge-primary' : 'badge-secondary' ?>">
                    <?= $a['attendee_type']==='staff' ? 'Personel' : 'Misafir' ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($a['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $a['tc_no']      ? htmlspecialchars($a['tc_no'],      ENT_QUOTES, 'UTF-8') : '-' ?></td>
                <td><?= $a['phone']      ? htmlspecialchars($a['phone'],      ENT_QUOTES, 'UTF-8') : '-' ?></td>
                <td>
                  <?= $a['institution'] ? htmlspecialchars($a['institution'], ENT_QUOTES, 'UTF-8') : '' ?>
                  <?php if ($a['unit']): ?>
                    <small class="text-muted">/ <?= htmlspecialchars($a['unit'], ENT_QUOTES, 'UTF-8') ?></small>
                  <?php endif; ?>
                  <?php if (!$a['institution'] && !$a['unit']): ?>-<?php endif; ?>
                </td>
                <td><?= $a['title']     ? htmlspecialchars($a['title'],      ENT_QUOTES, 'UTF-8') : '-' ?></td>
                <td style="white-space:nowrap"><?= date('d.m.Y H:i', strtotime($a['attended_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($attendees)): ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  <i class="fas fa-users fa-2x mb-2"></i><br>
                  Hen&#252;z kat&#305;l&#305;mc&#305; yok
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
