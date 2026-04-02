/* ── Nav item click pop animation handler ── */
document.querySelectorAll('.nav-item:not(.logout)').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        var href = this.href;
        document.querySelectorAll('.nav-item').forEach(function(i) {
            i.classList.remove('active', 'nav-clicked');
        });
        this.classList.add('active');
        var el = this;
        requestAnimationFrame(function() { el.classList.add('nav-clicked'); });
        setTimeout(function() { window.location.href = href; }, 340);
    });
});
