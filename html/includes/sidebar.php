<?php
$currentPath = $_SERVER['REQUEST_URI'];
$admin       = getCurrentAdmin();
if (!isset($LANG)) require_once __DIR__ . '/lang.php';

function navItem(string $href, string $icon, string $label, string $current, string $match): void {
    $active = (strpos($current, $match) !== false) ? 'active' : '';
    echo "<li class='nav-item {$active}'><a href='{$href}' class='nav-link'><i class='{$icon}'></i><span>{$label}</span></a></li>";
}
?>
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <?php $logo = getSetting('logo_path',''); if ($logo && file_exists('/var/www/html'.$logo)): ?>
      <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="brand-logo">
    <?php else: ?>
      <div class="brand-icon"><i class="fas fa-building"></i></div>
    <?php endif; ?>
    <div class="brand-text">
      <span class="brand-title"><?= htmlspecialchars(getSetting('hospital_name','Toplantı Sistemi'), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="brand-sub"><?= htmlspecialchars(getSetting('institution_name',''), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <ul class="nav-list">
      <li class="nav-section"><?= $LANG['nav_general'] ?></li>
      <?php navItem('/admin/index.php','fas fa-tachometer-alt',$LANG['nav_dashboard'],$currentPath,'admin/index'); ?>
      <?php navItem('/admin/meetings.php','fas fa-calendar-alt',$LANG['nav_meetings'],$currentPath,'meetings'); ?>
      <li class="nav-section"><?= $LANG['nav_reporting'] ?></li>
      <?php navItem('/admin/export.php','fas fa-file-export',$LANG['nav_export'],$currentPath,'export'); ?>
      <?php navItem('/admin/reports.php','fas fa-chart-bar',$LANG['nav_reports'],$currentPath,'reports'); ?>
      <?php if (($admin['role'] ?? '') === 'superadmin'): ?>
      <li class="nav-section"><?= $LANG['nav_management'] ?></li>
      <?php navItem('/admin/logs.php','fas fa-list-alt',$LANG['nav_logs'],$currentPath,'logs'); ?>
      <?php navItem('/admin/settings.php','fas fa-cog',$LANG['nav_settings'],$currentPath,'settings'); ?>
      <?php endif; ?>
    </ul>
  </nav>
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($admin['name'] ?? 'A', 0, 1)) ?></div>
      <div class="user-detail">
        <span class="user-name"><?= htmlspecialchars($admin['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <span class="user-role"><?= htmlspecialchars($admin['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    </div>
    <a href="/admin/logout.php" class="btn-logout" title="Çıkış"><i class="fas fa-sign-out-alt"></i></a>
  </div>
</div>
