<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/lang.php';
requireAdmin();

$pageTitle = $LANG['nav_meetings'];
$db = getDB();
$db->exec("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

$status   = $_GET['status']    ?? '';
$search   = trim($_GET['search']   ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to']   ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 15;
$offset   = ($page - 1) * $limit;

$where  = [];
$params = [];

if ($status) {
    $where[]  = "m.status = ?";
    $params[] = $status;
}
if ($search) {
    $where[]  = "(m.title LIKE ? OR m.location LIKE ? OR m.organizer_name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($dateFrom) {
    $where[]  = "m.meeting_date >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where[]  = "m.meeting_date <= ?";
    $params[] = $dateTo;
}

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalStmt = $db->prepare("SELECT COUNT(*) FROM meetings m {$whereStr}");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();

$stmt = $db->prepare(
    "SELECT m.id, m.meeting_code, m.title, m.meeting_date, m.meeting_time,
            m.location, m.organizer_name, m.status, COUNT(a.id) as attendee_count
     FROM meetings m
     LEFT JOIN attendees a ON m.id = a.meeting_id
     {$whereStr}
     GROUP BY m.id, m.meeting_code, m.title, m.meeting_date, m.meeting_time,
              m.location, m.organizer_name, m.status
     ORDER BY m.meeting_date DESC, m.meeting_time DESC
     LIMIT {$limit} OFFSET {$offset}"
);
$stmt->execute($params);
$meetings = $stmt->fetchAll();

$admin = getCurrentAdmin();

// Durum deðiþtirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $mid   = (int)$_POST['meeting_id'];
    $newSt = $_POST['new_status'];
    if (in_array($newSt, ['active','completed','cancelled'])) {
        $db->prepare("UPDATE meetings SET status=? WHERE id=?")->execute([$newSt, $mid]);
        logAccess('meeting_status', "Toplanti durumu guncellendi: ID={$mid} -> {$newSt}", 'success');
    }
    header('Location: /admin/meetings.php');
    exit;
}

// Toplantý silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_meeting'])) {
    if (($admin['role'] ?? '') === 'superadmin') {
        $mid = (int)$_POST['meeting_id'];
        $db->prepare("DELETE FROM meetings WHERE id = ?")->execute([$mid]);
        logAccess('meeting_deleted', "Toplanti silindi: ID={$mid}", 'warning');
    }
    header('Location: /admin/meetings.php');
    exit;
}

// Türkçe etiketler
$lArama        = 'Arama';
$lBaslangic    = 'Ba' . chr(0xC5).chr(0x9F) . 'lang' . chr(0xC4).chr(0xB1) . chr(0xC3).chr(0xA7) . ' Tarihi';
$lBitis        = 'Biti' . chr(0xC5).chr(0x9F) . ' Tarihi';
$lDurum        = 'Durum';
$lTumDurum     = 'T' . chr(0xC3).chr(0xBC) . 'm' . chr(0xC3).chr(0xBC) . ' Durumlar';
$lTamamlandi   = 'Tamamland' . chr(0xC4).chr(0xB1);
$lIptal        = chr(0xC4).chr(0xB0) . 'ptal';
$lPlaceholder  = 'Toplant' . chr(0xC4).chr(0xB1) . ' ad' . chr(0xC4).chr(0xB1) . ', konum veya d' . chr(0xC3).chr(0xBC) . 'zenleyen...';
$lSonuc        = 'sonu' . chr(0xC3).chr(0xA7) . ' bulundu';
$lArasinda     = 'tarihleri aras' . chr(0xC4).chr(0xB1);
$lFiltreSonuc  = 'Filtre kriterlerine uygun toplant' . chr(0xC4).chr(0xB1) . ' bulunamad' . chr(0xC4).chr(0xB1);
$lHenuz        = 'Hen' . chr(0xC3).chr(0xBC) . 'z toplant' . chr(0xC4).chr(0xB1) . ' olu' . chr(0xC5).chr(0x9F) . 'turulmam' . chr(0xC4).chr(0xB1) . chr(0xC5).chr(0x9F);
$lTamamConfirm = 'Toplant' . chr(0xC4).chr(0xB1) . ' tamamland' . chr(0xC4).chr(0xB1) . ' olarak i' . chr(0xC5).chr(0x9F) . 'aretlensin mi?';
$lSilConfirm   = 'Bu toplant' . chr(0xC4).chr(0xB1) . ' ve t' . chr(0xC3).chr(0xBC) . 'm kat' . chr(0xC4).chr(0xB1) . 'l' . chr(0xC4).chr(0xB1) . 'mc' . chr(0xC4).chr(0xB1) . ' verileri silinecek. Emin misiniz?';
$lYonetiniz    = 'T' . chr(0xC3).chr(0xBC) . 'm toplant' . chr(0xC4).chr(0xB1) . 'lar' . chr(0xC4).chr(0xB1) . ' g' . chr(0xC3).chr(0xB6) . 'r' . chr(0xC3).chr(0xBC) . 'nt' . chr(0xC3).chr(0xBC) . 'leyin ve y' . chr(0xC3).chr(0xB6) . 'netin';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-title"><?= $LANG['nav_meetings'] ?></h1>
        <p class="page-sub"><?= $lYonetiniz ?></p>
      </div>
      <a href="/meeting/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?= $LANG['create_meeting'] ?>
      </a>
    </div>

    <!-- Filtreler -->
    <div class="card mb-3">
      <div class="card-body">
        <form method="GET">
          <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">

            <div class="form-group" style="flex:2;min-width:180px;margin:0">
              <label style="font-size:.78rem;font-weight:600;color:#374151;margin-bottom:4px;display:block">
                <?= $lArama ?>
              </label>
              <input type="text" name="search" class="form-control"
                     placeholder="<?= $lPlaceholder ?>"
                     value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group" style="flex:1;min-width:150px;margin:0">
              <label style="font-size:.78rem;font-weight:600;color:#374151;margin-bottom:4px;display:block">
                <?= $lBaslangic ?>
              </label>
              <input type="text" name="date_from" id="date_from" class="form-control"
                     placeholder="gg.aa.yyyy"
                     value="<?= $dateFrom ? date('d.m.Y', strtotime($dateFrom)) : '' ?>">
            </div>

            <div class="form-group" style="flex:1;min-width:150px;margin:0">
              <label style="font-size:.78rem;font-weight:600;color:#374151;margin-bottom:4px;display:block">
                <?= $lBitis ?>
              </label>
              <input type="text" name="date_to" id="date_to" class="form-control"
                     placeholder="gg.aa.yyyy"
                     value="<?= $dateTo ? date('d.m.Y', strtotime($dateTo)) : '' ?>">
            </div>

            <div class="form-group" style="flex:1;min-width:130px;margin:0">
              <label style="font-size:.78rem;font-weight:600;color:#374151;margin-bottom:4px;display:block">
                <?= $lDurum ?>
              </label>
              <select name="status" class="form-control">
                <option value=""><?= $lTumDurum ?></option>
                <option value="active"    <?= $status==='active'   ?'selected':'' ?>>Aktif</option>
                <option value="completed" <?= $status==='completed'?'selected':'' ?>><?= $lTamamlandi ?></option>
                <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>><?= $lIptal ?></option>
              </select>
            </div>

            <div style="display:flex;gap:6px;margin:0;padding-bottom:1px">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filtrele
              </button>
              <a href="/admin/meetings.php" class="btn btn-outline-secondary">
                <i class="fas fa-times"></i> Temizle
              </a>
            </div>

          </div>
        </form>
      </div>
    </div>

    <?php if ($search || $dateFrom || $dateTo || $status): ?>
    <p style="font-size:.82rem;color:#6c757d;margin-bottom:12px">
      <i class="fas fa-info-circle"></i>
      <?= $total ?> <?= $lSonuc ?>
      <?php if ($dateFrom || $dateTo): ?>
        &mdash;
        <?= $dateFrom ? date('d.m.Y', strtotime($dateFrom)) : '...' ?>
        &mdash;
        <?= $dateTo ? date('d.m.Y', strtotime($dateTo)) : '...' ?>
        <?= $lArasinda ?>
      <?php endif; ?>
    </p>
    <?php endif; ?>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>TOPLANTI ADI</th>
                <th>TAR&#304;H</th>
                <th>SAAT</th>
                <th>KONUM</th>
                <th>D&#220;ZENLEYEN</th>
                <th>KATILIMCI</th>
                <th>DURUM</th>
                <th>&#304;&#350;LEM</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($meetings as $m):
                $sm = [
                  'active'    => ['Aktif',       'badge-success'],
                  'completed' => [$lTamamlandi,  'badge-secondary'],
                  'cancelled' => [$lIptal,       'badge-danger'],
                ];
                $s = $sm[$m['status']] ?? ['?', 'badge-info'];
              ?>
              <tr>
                <td><span class="badge-num"><?= $m['id'] ?></span></td>
                <td><div class="meeting-name"><?= htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8') ?></div></td>
                <td style="white-space:nowrap"><?= date('d.m.Y', strtotime($m['meeting_date'])) ?></td>
                <td><span class="time-badge"><?= substr($m['meeting_time'],0,5) ?></span></td>
                <td><?= htmlspecialchars($m['location'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($m['organizer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="attendee-count"><i class="fas fa-users"></i> <?= $m['attendee_count'] ?></span></td>
                <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                <td>
                  <div style="display:flex;gap:4px">
                    <a href="/meeting/qr.php?id=<?= $m['id'] ?>"
                       class="btn btn-sm btn-primary" title="QR Kod">
                      <i class="fas fa-qrcode"></i>
                    </a>
                    <a href="/meeting/report.php?id=<?= $m['id'] ?>"
                       class="btn btn-sm btn-outline-secondary" title="Rapor">
                      <i class="fas fa-chart-bar"></i>
                    </a>
                    <?php if ($m['status'] === 'active'): ?>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
                      <input type="hidden" name="new_status" value="completed">
                      <button type="submit" name="toggle_status"
                              class="btn btn-sm btn-outline-secondary"
                              title="<?= $lTamamlandi ?>"
                              onclick="return confirm('<?= $lTamamConfirm ?>')">
                        <i class="fas fa-check"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                    <?php if (($admin['role'] ?? '') === 'superadmin'): ?>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
                      <input type="hidden" name="delete_meeting" value="1">
                      <button type="submit"
                              class="btn btn-sm btn-danger"
                              title="Sil"
                              onclick="return confirm('<?= $lSilConfirm ?>')">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($meetings)): ?>
              <tr>
                <td colspan="9" class="text-center text-muted py-4">
                  <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                  <?= ($search || $dateFrom || $dateTo || $status) ? $lFiltreSonuc : $lHenuz ?>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ($total > $limit): ?>
    <div class="pagination mt-3">
      <?php $totalPages = ceil($total / $limit); ?>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>"
           class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
<script>
flatpickr("#date_from", {
  locale: "tr", dateFormat: "Y-m-d", altInput: true, altFormat: "d.m.Y",
  allowInput: true, defaultDate: "<?= $dateFrom ?>"
});
flatpickr("#date_to", {
  locale: "tr", dateFormat: "Y-m-d", altInput: true, altFormat: "d.m.Y",
  allowInput: true, defaultDate: "<?= $dateTo ?>"
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>