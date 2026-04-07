<?php
header('Content-Type: text/html; charset=UTF-8');
$pageTitle = 'Gosterge Paneli';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/lang.php';
requireAdmin();

$db = getDB();
$db->exec("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

$stats['total_meetings']  = $db->query("SELECT COUNT(*) FROM meetings")->fetchColumn();
$stats['active_meetings'] = $db->query("SELECT COUNT(*) FROM meetings WHERE status='active'")->fetchColumn();
$stats['total_attendees'] = $db->query("SELECT COUNT(*) FROM attendees")->fetchColumn();
$stats['today_attendees'] = $db->query("SELECT COUNT(*) FROM attendees WHERE DATE(attended_at)=CURDATE()")->fetchColumn();
$stats['staff_attendees'] = $db->query("SELECT COUNT(*) FROM attendees WHERE attendee_type='staff'")->fetchColumn();
$stats['guest_attendees'] = $db->query("SELECT COUNT(*) FROM attendees WHERE attendee_type='guest'")->fetchColumn();

$trend = $db->query(
    "SELECT DATE(meeting_date) as d, COUNT(*) as cnt
     FROM meetings WHERE meeting_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(meeting_date) ORDER BY d ASC"
)->fetchAll();

$recentMeetings = $db->query(
    "SELECT m.id, m.title, m.meeting_date, m.meeting_time,
            m.location, m.organizer_name, m.status, COUNT(a.id) as attendee_count
     FROM meetings m LEFT JOIN attendees a ON m.id = a.meeting_id
     GROUP BY m.id, m.title, m.meeting_date, m.meeting_time, m.location, m.organizer_name, m.status
     ORDER BY m.created_at DESC LIMIT 8"
)->fetchAll();

$monthly = $db->query(
    "SELECT DATE_FORMAT(meeting_date,'%Y-%m') as ym, COUNT(*) as cnt
     FROM meetings WHERE meeting_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(meeting_date,'%Y-%m')
     ORDER BY ym ASC"
)->fetchAll();

$lTamamlandi = 'Tamamland' . chr(0xC4).chr(0xB1);
$lIptal      = chr(0xC4).chr(0xB0) . 'ptal';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <div>
        <h1 class="page-title"><?= $LANG['dashboard_title'] ?></h1>
        <p class="page-sub"><?= $LANG['dashboard_sub'] ?></p>
      </div>
      <div class="header-actions">
        <a href="/meeting/create.php" class="btn btn-primary">
          <i class="fas fa-plus"></i> <?= $LANG['create_meeting'] ?>
        </a>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-body"><div class="stat-value"><?= $stats['total_meetings'] ?></div><div class="stat-label"><?= $LANG['total_meeting'] ?></div></div>
      </div>
      <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-body"><div class="stat-value"><?= $stats['active_meetings'] ?></div><div class="stat-label"><?= $LANG['active_meeting'] ?></div></div>
      </div>
      <div class="stat-card stat-info">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-body"><div class="stat-value"><?= $stats['total_attendees'] ?></div><div class="stat-label"><?= $LANG['total_attendee'] ?></div></div>
      </div>
      <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="fas fa-bell"></i></div>
        <div class="stat-body"><div class="stat-value"><?= $stats['today_attendees'] ?></div><div class="stat-label"><?= $LANG['today_attendee'] ?></div></div>
      </div>
      <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
        <div class="stat-body"><div class="stat-value"><?= $stats['staff_attendees'] ?></div><div class="stat-label"><?= $LANG['staff_attendee'] ?></div></div>
      </div>
      <div class="stat-card stat-teal">
        <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
        <div class="stat-body"><div class="stat-value"><?= $stats['guest_attendees'] ?></div><div class="stat-label"><?= $LANG['guest_attendee'] ?></div></div>
      </div>
    </div>

    <div class="dashboard-grid">
      <div class="card chart-card">
        <div class="card-header"><h3><i class="fas fa-chart-line"></i> <?= $LANG['trend_title'] ?></h3></div>
        <div class="card-body"><canvas id="trendChart" height="100"></canvas></div>
      </div>
      <div class="card chart-card">
        <div class="card-header"><h3><i class="fas fa-chart-bar"></i> <?= $LANG['monthly_title'] ?></h3></div>
        <div class="card-body"><canvas id="monthlyChart" height="100"></canvas></div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header d-flex-between">
        <h3><i class="fas fa-list"></i> <?= $LANG['last_meetings'] ?></h3>
        <a href="/admin/meetings.php" class="btn btn-sm btn-outline-primary"><?= $LANG['see_all'] ?> &rarr;</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th><?= $LANG['col_meeting'] ?></th>
                <th><?= $LANG['col_datetime'] ?></th>
                <th><?= $LANG['col_location'] ?></th>
                <th><?= $LANG['col_attendee'] ?></th>
                <th><?= $LANG['col_status'] ?></th>
                <th><?= $LANG['col_action'] ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentMeetings as $m):
                $sm = [
                  'active'    => ['Aktif',       'badge-success'],
                  'completed' => [$lTamamlandi,  'badge-secondary'],
                  'cancelled' => [$lIptal,       'badge-danger'],
                ];
                $s = $sm[$m['status']] ?? ['?', 'badge-info'];
              ?>
              <tr>
                <td><span class="badge-num"><?= $m['id'] ?></span></td>
                <td>
                  <div class="meeting-name"><?= htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8') ?></div>
                  <small class="text-muted"><?= htmlspecialchars($m['organizer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                </td>
                <td style="white-space:nowrap">
                  <?= date('d.m.Y', strtotime($m['meeting_date'])) ?>
                  <span class="time-badge"><?= substr($m['meeting_time'],0,5) ?></span>
                </td>
                <td><?= htmlspecialchars($m['location'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="attendee-count"><i class="fas fa-users"></i> <?= $m['attendee_count'] ?></span></td>
                <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                <td>
                  <div style="display:flex;gap:4px">
                    <a href="/meeting/qr.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-primary" title="QR Kod">
                      <i class="fas fa-qrcode"></i>
                    </a>
                    <a href="/meeting/report.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Rapor">
                      <i class="fas fa-chart-bar"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($recentMeetings)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">
                <i class="fas fa-calendar-times fa-2x mb-2"></i><br><?= $LANG['no_meeting'] ?>
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const AYLAR = ['Oca','\u015eub','Mar','Nis','May','Haz','Tem','\u0102u','Eyl','Eki','Kas','Ara'];

// Trend verileri
const trendRaw  = <?= json_encode(array_map(fn($r) => $r['d'], $trend)) ?>;
const trendData = <?= json_encode(array_column($trend, 'cnt')) ?>;
const trendLabels = trendRaw.length ? trendRaw.map(function(d) {
  var dt = new Date(d);
  return dt.getDate() + ' ' + AYLAR[dt.getMonth()];
}) : ['Veri yok'];

new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: {
    labels: trendLabels,
    datasets: [{
      label: 'Toplant\u0131',
      data: trendData.length ? trendData : [0],
      borderColor: '#1a5276',
      backgroundColor: 'rgba(26,82,118,0.1)',
      borderWidth: 2,
      pointBackgroundColor: '#1a5276',
      fill: true,
      tension: 0.4
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});

// Aylık veriler
const monthlyRaw  = <?= json_encode(array_column($monthly, 'ym')) ?>;
const monthlyData = <?= json_encode(array_column($monthly, 'cnt')) ?>;
const monthlyLabels = monthlyRaw.length ? monthlyRaw.map(function(ym) {
  var parts = ym.split('-');
  return AYLAR[parseInt(parts[1]) - 1] + ' ' + parts[0];
}) : ['Veri yok'];

new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: monthlyLabels,
    datasets: [{
      label: 'Toplant\u0131',
      data: monthlyData.length ? monthlyData : [0],
      backgroundColor: [
        'rgba(26,82,118,0.8)','rgba(46,204,113,0.8)','rgba(243,156,18,0.8)',
        'rgba(231,76,60,0.8)','rgba(142,68,173,0.8)','rgba(26,188,156,0.8)'
      ],
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>