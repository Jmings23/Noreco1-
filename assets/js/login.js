// ==========================================================
//  NORECO 1 WMS — Login Page JS (homepage.php)
// ==========================================================

// ── Password show/hide toggle ──
document.getElementById('togglePw').addEventListener('click', function () {
    const input   = document.getElementById('pwInput');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    this.classList.toggle('fa-eye',       isHidden);
    this.classList.toggle('fa-eye-slash', !isHidden);
});

// ── Forgot password modal ──
const overlay  = document.getElementById('forgotOverlay');
const openBtn  = document.getElementById('openForgotModal');
const closeBtn = document.getElementById('closeForgotModal');

openBtn.addEventListener('click', function (e) {
    e.preventDefault();
    overlay.classList.add('active');
    document.getElementById('fpEmail').focus();
});

closeBtn.addEventListener('click', () => overlay.classList.remove('active'));

overlay.addEventListener('click', function (e) {
    if (e.target === overlay) overlay.classList.remove('active');
});
