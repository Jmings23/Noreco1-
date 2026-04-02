/* ── Shared pagination + search ──
   Usage: initPagination('tableId', 'searchInputId', 'paginationContainerId')
   ──────────────────────────────────────────────────────────────────────── */
function initPagination(tableId, searchId, paginationId) {
    var PER_PAGE   = 15;
    var allRows    = Array.from(document.querySelectorAll('#' + tableId + ' tbody tr'));
    var pagination = document.getElementById(paginationId);
    var currentPage = 1;
    var filtered   = allRows.slice();

    function render() {
        var total = Math.ceil(filtered.length / PER_PAGE) || 1;
        if (currentPage > total) currentPage = total;

        allRows.forEach(function(r) { r.style.display = 'none'; });
        var start = (currentPage - 1) * PER_PAGE;
        filtered.slice(start, start + PER_PAGE).forEach(function(r) { r.style.display = ''; });

        pagination.innerHTML = '';

        var info = document.createElement('span');
        info.style.cssText = 'font-size:12px;color:#768192;margin-right:6px;';
        info.textContent = filtered.length
            ? 'Showing ' + Math.min(start + 1, filtered.length) + '\u2013' + Math.min(start + PER_PAGE, filtered.length) + ' of ' + filtered.length + ' entries'
            : 'No entries found';
        pagination.appendChild(info);

        if (filtered.length <= PER_PAGE) return;

        function btn(label, page, disabled, active) {
            var b = document.createElement('button');
            b.className = 'pg-btn' + (active ? ' active' : '');
            b.innerHTML = label;
            b.disabled  = disabled;
            b.addEventListener('click', function() { currentPage = page; render(); });
            pagination.appendChild(b);
        }

        btn('<i class="fas fa-chevron-left"></i>', currentPage - 1, currentPage === 1, false);
        for (var i = 1; i <= total; i++) {
            if (total > 7 && i > 2 && i < total - 1 && Math.abs(i - currentPage) > 1) {
                if (i === 3 || i === total - 2) {
                    var dots = document.createElement('span');
                    dots.textContent = '\u2026';
                    dots.style.cssText = 'color:#aab;font-size:13px;padding:0 4px;';
                    pagination.appendChild(dots);
                }
                continue;
            }
            btn(i, i, false, i === currentPage);
        }
        btn('<i class="fas fa-chevron-right"></i>', currentPage + 1, currentPage === total, false);
    }

    var searchEl = document.getElementById(searchId);
    if (searchEl) {
        searchEl.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            filtered = q ? allRows.filter(function(r) { return r.textContent.toLowerCase().includes(q); }) : allRows.slice();
            currentPage = 1;
            render();
        });
    }

    render();
}
