/* ═══════════════════════════════════════════════════════════════
   WAREHOUSE SYSTEM — Login Admin Script
   Toggle password, loading state, fokus otomatis
   ═══════════════════════════════════════════════════════════════ */
'use strict';

const passwordInput = document.getElementById('password');
const pwToggle      = document.getElementById('pwToggle');
const loginForm     = document.getElementById('loginForm');
const loginSubmit   = document.getElementById('loginSubmit');
const usernameInput = document.getElementById('username');

/* ── Tampilkan / sembunyikan password ── */
pwToggle?.addEventListener('click', () => {
  if (!passwordInput) return;

  const show = passwordInput.type === 'password';
  passwordInput.type = show ? 'text' : 'password';
  pwToggle.setAttribute('aria-pressed', String(show));
  pwToggle.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');

  // Kembalikan fokus ke input & caret di akhir teks.
  passwordInput.focus({ preventScroll: true });
  const end = passwordInput.value.length;
  try {
    passwordInput.setSelectionRange(end, end);
  } catch (_) {
    /* tipe input tidak mendukung selection range */
  }
});

/* ── Fokus ke password bila username sudah terisi (mis. setelah error) ── */
if (usernameInput?.value.trim() && passwordInput) {
  passwordInput.focus({ preventScroll: true });
}

/* ── Loading state saat submit ── */
function resetSubmitState() {
  if (!loginSubmit) return;
  loginSubmit.classList.remove('is-loading');
  loginSubmit.disabled = false;
  const label = loginSubmit.querySelector('.login-submit__text');
  if (label) label.textContent = 'Masuk ke Dashboard';
}

loginForm?.addEventListener('submit', () => {
  if (!loginSubmit || !loginForm.checkValidity()) return;
  loginSubmit.classList.add('is-loading');
  loginSubmit.disabled = true;
  const label = loginSubmit.querySelector('.login-submit__text');
  if (label) label.textContent = 'Memproses...';
});

// Kembalikan tombol bila halaman diambil dari bfcache (tombol back browser).
window.addEventListener('pageshow', resetSubmitState);
