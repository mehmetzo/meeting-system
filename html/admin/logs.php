<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/lang.php';
requireSuperAdmin();

$pageTitle = $LANG['logs_title'];
$db = getDB();

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 50;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$where  = [];
$params = [];

if ($search) {
    $where[]  = "(username LIKE ? OR action LIKE ? OR ip_address LIKE ? OR details LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($status) {
    $where[]  = "status = ?";
    $params[] = $status;
}

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = $db->prepare("SELECT COUNT(*) FROM access_logs {$whereStr}");
$total->execute($params);
$total = $total->fetchColumn();

$stmt = $db->prepare(
    "SELECT * FROM access_logs {$whereStr} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <div>
        <h1 class="page-title"><?= $LANG['logs_title'] ?></h1>
        <p class="page-sub"><?= $LANG['logs_sub'] ?></p>
      </div>
      <a href="/admin/export.php?type=logs" class="btn btn-outline-primary">
        <i class="fas fa-download"></i> Dışa Aktar
      </a>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <form class="filter-row" method="GET">
          <div class="form-group">
            <input type="text" name="search" class="form-control"
                   placeholder="Kullanıcı, işlem veya IP ara..."
                   value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="form-group">
            <select name="status" class="form-control">
              <option value="">Tüm Durumlar</option>
              <option value="success" <?= $status==='success'?'selected':'' ?>>Başarılı</option>
              <option value="warning" <?= $status==='warning'?'selected':'' ?>>Uyarı</option>
              <option value="error"   <?= $status==='error'  ?'selected':'' ?>>Hata</option>
              <option value="info"    <?= $status==='info'   ?'selected':'' ?>>Bilgi</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrele</button>
          <a href="/admin/logs.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Temizle</a>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>KULLANICI</th>
                <th>İŞLEM</th>
                <th>DETAY</th>
                <th>IP ADRESİ</th>
                <th>DURUM</th>
                <th>TARİH</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
              <tr>
                <td><?= $log['id'] ?></td>
                <td><code><?= htmlspecialchars($log['username'] ?? '—', ENT_QUOTES, 'UTF-8') ?></code></td>
                <td><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-muted small"><?= htmlspecialchars(mb_substr($log['details'] ?? '', 0, 80), ENT_QUOTES, 'UTF-8') ?></td>
                <td><code><?= htmlspecialchars($log['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></code></td>
                <td>
                  <?php $sc = ['success'=>'badge-success','warning'=>'badge-warning','error'=>'badge-danger','info'=>'badge-info']; ?>
                  <span class="badge <?= $sc[$log['status']] ?? 'badge-secondary' ?>"><?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td class="small"><?= date('d.m.Y H:i', strtotime($log['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($logs)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">Kayıt bulunamadı</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ($total > $limit): ?>
    <div class="pagination mt-3">
      <?php $pages = ceil($total / $limit); ?>
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
           class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
