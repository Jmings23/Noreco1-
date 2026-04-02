/* ── Delete confirmation dialog (shared across material list pages) ── */
document.querySelectorAll('.delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var url = this.href;
        Swal.fire({
            html: `<div style="text-align:center;padding:10px 0 0;">
                <div style="width:80px;height:80px;border-radius:50%;border:3px solid #e55353;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <span style="font-size:2.2rem;font-weight:700;color:#e55353;">!</span>
                </div>
                <h2 style="font-size:1.4rem;color:#2d3142;margin-bottom:8px;">Remove Material?</h2>
                <p style="color:#768192;font-size:0.9rem;margin:0;">This action cannot be undone.</p>
            </div>`,
            showCancelButton: true,
            confirmButtonColor: '#e55353',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) window.location = url;
        });
    });
});
