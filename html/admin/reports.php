<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/lang.php';
requireAdmin();

$pageTitle = $LANG['nav_reports'];
$db = getDB();
$db->exec("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

$stats = [
    'total_meetings'  => $db->query("SELECT COUNT(*) FROM meetings")->fetchColumn(),
    'active_meetings' => $db->query("SELECT COUNT(*) FROM meetings WHERE status='active'")->fetchColumn(),
    'completed'       => $db->query("SELECT COUNT(*) FROM meetings WHERE status='completed'")->fetchColumn(),
    'total_attendees' => $db->query("SELECT COUNT(*) FROM attendees")->fetchColumn(),
    'staff_attendees' => $db->query("SELECT COUNT(*) FROM attendees WHERE attendee_type='staff'")->fetchColumn(),
    'guest_attendees' => $db->query("SELECT COUNT(*) FROM attendees WHERE attendee_type='guest'")->fetchColumn(),
];

$topMeetings = $db->query(
    "SELECT m.title, m.meeting_date, COUNT(a.id) as cnt
     FROM meetings m
     LEFT JOIN attendees a ON m.id = a.meeting_id
     GROUP BY m.id, m.title, m.meeting_date
     ORDER BY cnt DESC LIMIT 5"
)->fetchAll();

$dailyAttendees = $db->query(
    "SELECT DATE(attended_at) as d, COUNT(*) as cnt
     FROM attendees
     WHERE attended_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(attended_at)
     ORDER BY d ASC"
)->fetchAll();

$lTamamlandi = 'Tamamland' . chr(0xC4).chr(0xB1);
$lEnCok      = 'En ' . chr(0xC3).chr(0x87) . 'ok Kat' . chr(0xC4).chr(0xB1) . 'l' . chr(0xC4).chr(0xB1) . 'ml' . chr(0xC4).chr(0xB1) . ' Toplant' . chr(0xC4).chr(0xB1) . 'lar';
$lSon30      = 'Son 30 G' . chr(0xC3).chr(0xBC) . 'n Kat' . chr(0xC4).chr(0xB1) . 'l' . chr(0xC4).chr(0xB1) . 'm';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-title"><?= $LANG['nav_reports'] ?></h1>
        <p class="page-sub">Toplant&#305; ve kat&#305;l&#305;m istatistikleri</p>
      </div>
    </div>

    <!-- İstatistik kartları -->
    <div class="stats-grid">
      <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $stats['total_meetings'] ?></div>
          <div class="stat-label">Toplam Toplant&#305;</div>
        </div>
      </div>
      <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $stats['completed'] ?></div>
          <div class="stat-label"><?= $lTamamlandi ?></div>
        </div>
      </div>
      <div class="stat-card stat-info">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $stats['total_attendees'] ?></div>
          <div class="stat-label">Toplam Kat&#305;l&#305;mc&#305;</div>
        </div>
      </div>
      <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $stats['staff_attendees'] ?></div>
          <div class="stat-label">Personel</div>
        </div>
      </div>
      <div class="stat-card stat-teal">
        <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $stats['guest_attendees'] ?></div>
          <div class="stat-label">Misafir</div>
        </div>
      </div>
    </div>

    <!-- Grafikler -->
    <div class="dashboard-grid">
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-chart-line"></i> <?= $lSon30 ?></h3>
        </div>
        <div class="card-body">
          <canvas id="dailyChart" height="120"></canvas>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-trophy"></i> <?= $lEnCok ?></h3>
        </div>
        <div class="card-body p-0">
          <table class="data-table">
            <thead>
              <tr>
                <th>TOPLANTI</th>
                <th>TAR&#304;H</th>
                <th>KATILIMCI</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topMeetings as $t): ?>
              <tr>
                <td><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="white-space:nowrap"><?= date('d.m.Y', strtotime($t['meeting_date'])) ?></td>
                <td>
                  <span class="attendee-count">
                    <i class="fas fa-users"></i> <?= $t['cnt'] ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($topMeetings)): ?>
              <tr>
                <td colspan="3" class="text-center text-muted py-4">
                  Veri bulunamad&#305;
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const AYLAR = ['Oca', '\u015eub', 'Mar', 'Nis', 'May', 'Haz',
               'Tem', 'A\u011fu', 'Eyl', 'Eki', 'Kas', 'Ara'];

const dailyRaw  = <?= json_encode(array_map(fn($r) => $r['d'], $dailyAttendees)) ?>;
const dailyData = <?= json_encode(array_column($dailyAttendees, 'cnt')) ?>;

const dailyLabels = dailyRaw.length ? dailyRaw.map(function(d) {
  var dt = new Date(d);
  return dt.getDate() + ' ' + AYLAR[dt.getMonth()];
}) : ['Veri yok'];

new Chart(document.getElementById('dailyChart'), {
  type: 'line',
  data: {
    labels: dailyLabels,
    datasets: [{
      label: 'Kat\u0131l\u0131mc\u0131',
      data: dailyData.length ? dailyData : [0],
      borderColor: '#2ecc71',
      backgroundColor: 'rgba(46,204,113,0.1)',
      borderWidth: 2,
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#2ecc71'
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