/* ═══════════════════════════════════════════════════════════════
   WAREHOUSE SYSTEM — Panel Admin Script
   Sidebar mobile (off-canvas) & konfirmasi hapus data
   ═══════════════════════════════════════════════════════════════ */
'use strict';

const body       = document.body;
const sidebar    = document.getElementById('adminSidebar');
const overlay    = document.getElementById('sidebarOverlay');
const menuToggle = document.getElementById('sidebarToggle');

function isSidebarOpen() {
  return body.classList.contains('sidebar-open');
}

function openSidebar() {
  body.classList.add('sidebar-open');
  if (menuToggle) {
    menuToggle.setAttribute('aria-expanded', 'true');
    menuToggle.setAttribute('aria-label', 'Tutup menu navigasi');
  }
}

function closeSidebar() {
  body.classList.remove('sidebar-open');
  if (menuToggle) {
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.setAttribute('aria-label', 'Buka menu navigasi');
  }
}

menuToggle?.addEventListener('click', () => {
  if (isSidebarOpen()) {
    closeSidebar();
  } else {
    openSidebar();
  }
});

overlay?.addEventListener('click', closeSidebar);

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && isSidebarOpen()) {
    closeSidebar();
    menuToggle?.focus();
  }
});

// Tutup drawer setelah memilih menu (khusus tampilan mobile).
sidebar?.querySelectorAll('a.admin-nav__link').forEach((link) => {
  link.addEventListener('click', () => {
    if (window.matchMedia('(max-width: 1024px)').matches) {
      closeSidebar();
    }
  });
});

// Kembali ke desktop: pastikan state drawer tidak tertinggal.
const desktopQuery = window.matchMedia('(min-width: 1025px)');
desktopQuery.addEventListener('change', (event) => {
  if (event.matches) {
    closeSidebar();
  }
});

// Cadangan resize/orientasi: sebagian browser tidak memicu matchMedia change.
let resizeFrame = null;
function handleViewportResize() {
  if (window.innerWidth > 1024) {
    closeSidebar();
  }
}
window.addEventListener('resize', () => {
  if (resizeFrame !== null) {
    cancelAnimationFrame(resizeFrame);
  }
  resizeFrame = requestAnimationFrame(() => {
    handleViewportResize();
    resizeFrame = null;
  });
});
window.addEventListener('orientationchange', handleViewportResize);

// Konfirmasi sebelum menghapus data.
document.querySelectorAll('form[data-confirm]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const message = form.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
      event.preventDefault();
    }
  });
});
