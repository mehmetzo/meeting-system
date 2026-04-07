<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/lang.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$meeting = $db->prepare("SELECT * FROM meetings WHERE id = ?");
$meeting->execute([$id]);
$meeting = $meeting->fetch();

if (!$meeting) {
    header('Location: /admin/meetings.php');
    exit;
}

$attendees = $db->prepare(
    "SELECT * FROM attendees WHERE meeting_id = ? ORDER BY attended_at ASC"
);
$attendees->execute([$id]);
$attendees = $attendees->fetchAll();

$pageTitle = $meeting['title'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <div>
        <h1 class="page-title"><?= htmlspecialchars($meeting['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="page-sub">Toplantı Detayı &mdash; <code><?= htmlspecialchars($meeting['meeting_code'], ENT_QUOTES, 'UTF-8') ?></code></p>
      </div>
      <div class="header-actions">
        <a href="/meeting/qr.php?id=<?= $id ?>" class="btn btn-primary">
          <i class="fas fa-qrcode"></i> QR Kod
        </a>
        <a href="/admin/export.php?type=csv&meeting_id=<?= $id ?>" class="btn btn-outline-primary">
          <i class="fas fa-download"></i> CSV İndir
        </a>
        <a href="/admin/meetings.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left"></i> Geri
        </a>
      </div>
    </div>

    <!-- Toplantı Bilgileri -->
    <div class="card mb-3">
      <div class="card-header"><h3><i class="fas fa-info-circle"></i> Toplantı Bilgileri</h3></div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label>Toplantı Adı</label>
            <p><?= htmlspecialchars($meeting['title'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="form-group">
            <label>Tarih / Saat</label>
            <p><?= date('d.m.Y', strtotime($meeting['meeting_date'])) ?> &mdash; <?= substr($meeting['meeting_time'],0,5) ?></p>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Konum</label>
            <p><?= htmlspecialchars($meeting['location'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="form-group">
            <label>Düzenleyen</label>
            <p><?= htmlspecialchars($meeting['organizer_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        </div>
        <?php if ($meeting['description']): ?>
        <div class="form-group">
          <label>Açıklama</label>
          <p><?= htmlspecialchars($meeting['description'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php endif; ?>
        <div class="form-group">
          <label>Durum</label>
          <?php
          $sm = [
            'active'    => ['Aktif',      'badge-success'],
            'completed' => ['Tamamlandı', 'badge-secondary'],
            'cancelled' => ['İptal',      'badge-danger'],
          ];
          $s = $sm[$meeting['status']] ?? ['?','badge-info'];
          ?>
          <p><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></p>
        </div>
      </div>
    </div>

    <!-- Katılımcılar -->
    <div class="card">
      <div class="card-header d-flex-between">
        <h3><i class="fas fa-users"></i> Katılımcılar (<?= count($attendees) ?>)</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>TÜR</th>
                <th>AD SOYAD</th>
                <th>TC NO</th>
                <th>TELEFON</th>
                <th>KURUM / BİRİM</th>
                <th>ÜNVAN</th>
                <th>KATILIM SAATİ</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($attendees as $i => $a): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td>
                  <span class="badge <?= $a['attendee_type']==='staff'?'badge-primary':'badge-secondary' ?>">
                    <?= $a['attendee_type']==='staff' ? 'Personel' : 'Misafir' ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($a['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($a['tc_no'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($a['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <?= htmlspecialchars($a['institution'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  <?php if ($a['unit']): ?>
                    <small class="text-muted">/ <?= htmlspecialchars($a['unit'], ENT_QUOTES, 'UTF-8') ?></small>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($a['title'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= date('d.m.Y H:i', strtotime($a['attended_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($attendees)): ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  <i class="fas fa-users fa-2x mb-2"></i><br>
                  Henüz katılımcı yok
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
