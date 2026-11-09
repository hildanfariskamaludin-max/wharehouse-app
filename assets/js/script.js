/* ═══════════════════════════════════════════════════════════════
   WAREHOUSE SYSTEM — Landing Page Script
   Navbar · Scroll Spy · Reveal · Counter · Dark Mode · Modal · Form
   ═══════════════════════════════════════════════════════════════ */
'use strict';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ───────────────────────── Dark / Light Mode ───────────────────────── */
const themeToggle = document.getElementById('themeToggle');
const THEME_KEY   = 'ws-theme';

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  if (themeToggle) {
    themeToggle.setAttribute('aria-pressed', String(theme === 'dark'));
    themeToggle.setAttribute('aria-label', theme === 'dark' ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
  }
}

(function initTheme() {
  let saved = null;
  try { saved = localStorage.getItem(THEME_KEY); } catch (_) { /* private mode */ }
  const param   = new URLSearchParams(window.location.search).get('theme');
  const initial = ['light', 'dark'].includes(param)
    ? param
    : (saved ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
  applyTheme(initial);
})();

themeToggle?.addEventListener('click', () => {
  const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  applyTheme(next);
  try { localStorage.setItem(THEME_KEY, next); } catch (_) { /* abaikan */ }
  showToast(next === 'dark' ? 'Mode gelap diaktifkan.' : 'Mode terang diaktifkan.');
});

/* ───────────────────────── Navbar: scroll state ───────────────────────── */
const navbar     = document.getElementById('navbar');
const backToTop  = document.getElementById('backToTop');

function onScroll() {
  const y = window.scrollY || document.documentElement.scrollTop;
  navbar?.classList.toggle('is-scrolled', y > 40);
  backToTop?.classList.toggle('is-visible', y > 420);
}
window.addEventListener('scroll', onScroll, { passive: true });
onScroll();

backToTop?.addEventListener('click', () => {
  window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
});

/* ───────────────────────── Navbar: mobile menu ───────────────────────── */
const hamburger  = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');

function setMobileMenu(open) {
  if (!hamburger || !mobileMenu) return;
  hamburger.classList.toggle('is-active', open);
  hamburger.setAttribute('aria-expanded', String(open));
  hamburger.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
  mobileMenu.hidden = !open;
  document.body.style.overflow = open ? 'hidden' : '';
}

hamburger?.addEventListener('click', () => {
  setMobileMenu(mobileMenu?.hidden ?? true);
});

mobileMenu?.querySelectorAll('a').forEach((link) =>
  link.addEventListener('click', () => setMobileMenu(false))
);

document.addEventListener('click', (e) => {
  if (!mobileMenu || mobileMenu.hidden) return;
  if (!mobileMenu.contains(e.target) && !hamburger?.contains(e.target)) setMobileMenu(false);
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    setMobileMenu(false);
    closeArticleModal();
  }
});

/* ───────────────────────── Scroll spy (nav aktif) ───────────────────────── */
const sectionIds   = ['beranda', 'tentang', 'layanan', 'informasi', 'kontak'];
const sectionEls   = sectionIds.map((id) => document.getElementById(id)).filter(Boolean);
const navLinkMap   = new Map();
document.querySelectorAll('.nav-link').forEach((a) => {
  const id = (a.getAttribute('href') || '').replace('#', '');
  if (id) navLinkMap.set(id, a);
});

function updateScrollSpy() {
  if (!sectionEls.length) return;
  const offset = (navbar?.offsetHeight ?? 76) + 24;
  let currentId = sectionEls[0].id;
  for (const section of sectionEls) {
    if (section.getBoundingClientRect().top - offset <= 0) currentId = section.id;
  }
  navLinkMap.forEach((link, id) => link.classList.toggle('is-active', id === currentId));
}
window.addEventListener('scroll', updateScrollSpy, { passive: true });
updateScrollSpy();

/* ───────────────────────── Reveal on scroll ───────────────────────── */
const revealEls = document.querySelectorAll('.reveal');
if ('IntersectionObserver' in window && !prefersReducedMotion) {
  const revealObserver = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        }
      }
    },
    { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
  );
  revealEls.forEach((el) => revealObserver.observe(el));

  // Fallback keamanan: pastikan semua konten tampil walau observer tidak terpicu.
  window.addEventListener('load', () => {
    setTimeout(() => revealEls.forEach((el) => el.classList.add('is-visible')), 1500);
  });
} else {
  revealEls.forEach((el) => el.classList.add('is-visible'));
}

/* ───────────────────────── Animated counters ───────────────────────── */
const counterEls = document.querySelectorAll('.js-counter');
const idFormat   = new Intl.NumberFormat('id-ID');

function animateCounter(el) {
  const target   = Number(el.dataset.target || 0);
  const duration = 1800;
  const start    = performance.now();
  const useIdFmt = el.dataset.format === 'id';

  function tick(now) {
    const p      = Math.min((now - start) / duration, 1);
    const eased  = 1 - Math.pow(2, -6 * p); // easeOutExpo-ish
    const value  = Math.round(target * (p === 1 ? 1 : eased));
    el.textContent = useIdFmt ? idFormat.format(value) : String(value);
    if (p < 1) requestAnimationFrame(tick);
  }

  if (prefersReducedMotion) {
    el.textContent = useIdFmt ? idFormat.format(target) : String(target);
    return;
  }
  requestAnimationFrame(tick);
}

if ('IntersectionObserver' in window) {
  const counterObserver = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          counterObserver.unobserve(entry.target);
        }
      }
    },
    { threshold: 0.6 }
  );
  counterEls.forEach((el) => counterObserver.observe(el));
} else {
  counterEls.forEach(animateCounter);
}

/* ───────────────────────── Modal artikel ───────────────────────── */
const modal         = document.getElementById('articleModal');
const modalContent  = document.getElementById('articleModalContent');
let lastFocused     = null;

function openArticleModal(templateId) {
  const tpl = document.getElementById(`tpl-${templateId}`);
  if (!modal || !modalContent || !tpl) return;
  lastFocused = document.activeElement;
  modalContent.innerHTML = '';
  modalContent.appendChild(tpl.content.cloneNode(true));
  modal.hidden = false;
  document.body.style.overflow = 'hidden';
  modal.querySelector('.modal__close')?.focus();
}

function closeArticleModal() {
  if (!modal || modal.hidden) return;
  modal.hidden = true;
  document.body.style.overflow = '';
  if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
}

document.querySelectorAll('.js-open-article').forEach((btn) => {
  btn.addEventListener('click', () => openArticleModal(btn.dataset.article));
});
modal?.querySelectorAll('[data-close-modal]').forEach((el) =>
  el.addEventListener('click', closeArticleModal)
);

/* ───────────────────────── Toast ───────────────────────── */
const toastEl = document.getElementById('toast');
let toastTimer = null;

function showToast(message) {
  if (!toastEl) return;
  toastEl.textContent = message;
  toastEl.hidden = false;
  // reflow agar transisi berjalan saat toast dipicu berturut-turut
  void toastEl.offsetWidth;
  toastEl.classList.add('is-visible');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toastEl.classList.remove('is-visible');
    toastTimer = setTimeout(() => { toastEl.hidden = true; }, 320);
  }, 4200);
}

/* ───────────────────────── Form kontak (validasi client-side) ───────────────────────── */
const form        = document.getElementById('contactForm');
const successBox  = document.getElementById('formSuccess');
const resetBtn    = document.getElementById('formResetBtn');

const fields = {
  nama:  { input: document.getElementById('fNama'),  error: document.getElementById('errNama'),
           validate: (v) => v.trim().length >= 3 || 'Nama minimal 3 karakter.' },
  email: { input: document.getElementById('fEmail'), error: document.getElementById('errEmail'),
           validate: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) || 'Masukkan alamat email yang valid.' },
  pesan: { input: document.getElementById('fPesan'), error: document.getElementById('errPesan'),
           validate: (v) => v.trim().length >= 10 || 'Pesan minimal 10 karakter.' },
};

function validateField({ input, error, validate }) {
  if (!input || !error) return true;
  const result  = validate(input.value);
  const isValid = result === true;
  input.classList.toggle('is-invalid', !isValid);
  input.setAttribute('aria-invalid', String(!isValid));
  error.textContent = isValid ? '' : result;
  return isValid;
}

Object.values(fields).forEach((cfg) => {
  cfg.input?.addEventListener('input', () => {
    if (cfg.input.classList.contains('is-invalid')) validateField(cfg);
  });
});

form?.addEventListener('submit', (e) => {
  e.preventDefault();
  const results = Object.values(fields).map(validateField);
  if (results.includes(false)) {
    const firstInvalid = Object.values(fields).find((f) => f.input?.classList.contains('is-invalid'));
    firstInvalid?.input?.focus();
    showToast('Periksa kembali isian formulir yang ditandai merah.');
    return;
  }
  // Mode demo: tidak ada pengiriman ke server.
  [...form.elements].forEach((el) => { if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') el.disabled = true; });
  form.querySelector('button[type="submit"]').hidden = true;
  if (successBox) {
    successBox.hidden = false;
    successBox.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'nearest' });
  }
  showToast('Pesan Anda berhasil dikirim (mode demo).');
});

resetBtn?.addEventListener('click', () => {
  if (!form) return;
  form.reset();
  Object.values(fields).forEach(({ input, error }) => {
    input?.classList.remove('is-invalid');
    input?.removeAttribute('aria-invalid');
    if (input) input.disabled = false;
    if (error) error.textContent = '';
  });
  const submitBtn = form.querySelector('button[type="submit"]');
  if (submitBtn) submitBtn.hidden = false;
  if (successBox) successBox.hidden = true;
  fields.nama.input?.focus();
});

/* ───────────────────────── Tahun footer ───────────────────────── */
const yearEl = document.getElementById('footerYear');
if (yearEl) yearEl.textContent = String(new Date().getFullYear());
