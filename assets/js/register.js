// ==========================================================
//  NORECO 1 WMS — Register Page JS (public/register.php)
// ==========================================================

// ── Password show/hide toggles ──
function pwToggle(inputId, toggleId) {
    const input  = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    if (!input || !toggle) return;
    toggle.addEventListener('click', function () {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.className = show ? 'fas fa-eye toggle-pw' : 'fas fa-eye-slash toggle-pw';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    pwToggle('pwInput', 'pwToggle');
    pwToggle('cpInput', 'cpToggle');
});
