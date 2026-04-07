/* Toplantý Sistemi — Genel JS */

document.addEventListener('DOMContentLoaded', function () {

  // Auto-dismiss alerts
  document.querySelectorAll('.alert').forEach(function (alert) {
    setTimeout(function () {
      alert.style.opacity  = '0';
      alert.style.transition = 'opacity 0.5s';
      setTimeout(function () { alert.remove(); }, 500);
    }, 4000);
  });

  // Mobil sidebar toggle
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar   = document.getElementById('sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  // Confirm dialogs
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });

  // File input label güncelleme
  document.querySelectorAll('input[type="file"]').forEach(function (input) {
    input.addEventListener('change', function () {
      const label = document.querySelector('label[for="' + this.id + '"] span');
      if (label && this.files.length) {
        label.textContent = this.files[0].name;
      }
    });
  });

  // Tooltip basit implementasyonu
  document.querySelectorAll('[title]').forEach(function (el) {
    el.setAttribute('data-title', el.getAttribute('title'));
  });

});

// Bildirim fonksiyonu
function showNotification(message, type) {
  type = type || 'info';
  const colors = {
    success : '#27ae60',
    error   : '#e74c3c',
    warning : '#f39c12',
    info    : '#3498db'
  };
  const div = document.createElement('div');
  div.style.cssText = [
    'position:fixed', 'top:20px', 'right:20px', 'z-index:9999',
    'padding:12px 20px', 'border-radius:8px', 'color:#fff',
    'font-size:0.88rem', 'font-weight:600',
    'background:' + (colors[type] || colors.info),
    'box-shadow:0 4px 20px rgba(0,0,0,0.15)',
    'animation:slideIn 0.3s ease'
  ].join(';');
  div.textContent = message;
  document.body.appendChild(div);
  setTimeout(function () { div.remove(); }, 3000);
}

// CSS animasyonu enjekte
const style = document.createElement('style');
style.textContent = '@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:none;opacity:1}}';
document.head.appendChild(style);