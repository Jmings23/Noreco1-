// ==========================================================
//  NORECO 1 WMS — Reset Password Page JS (reset_password.php)
// ==========================================================

function setupToggle(inputId, toggleId) {
    const el = document.getElementById(toggleId);
    if (!el) return;
    el.addEventListener('click', function () {
        const inp  = document.getElementById(inputId);
        const show = inp.type === 'password';
        inp.type       = show ? 'text' : 'password';
        this.className = show ? 'fas fa-eye toggle-pw' : 'fas fa-eye-slash toggle-pw';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    setupToggle('pw1', 't1');
    setupToggle('pw2', 't2');
});
