/**
 * Mobile Menu Toggle Handler
 * Handles the hamburger menu for mobile devices
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get sidebar and toggle button elements
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    // Only initialize if elements exist
    if (!sidebar || !toggleBtn) {
        return;
    }
    
    // Toggle sidebar visibility
    toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        sidebar.classList.toggle('active');
        toggleBtn.classList.toggle('active');
    });
    
    // Close sidebar when clicking on a nav item
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            // Close menu after navigation (if not same page)
            setTimeout(() => {
                sidebar.classList.remove('active');
                toggleBtn.classList.remove('active');
            }, 300);
        });
    });
    
    // Close sidebar when clicking outside of it
    document.addEventListener('click', function(e) {
        const isClickInsideSidebar = sidebar.contains(e.target);
        const isClickOnToggle = toggleBtn.contains(e.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            toggleBtn.classList.remove('active');
        }
    });
    
    // Handle window resize to reset menu state on larger screens
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            toggleBtn.classList.remove('active');
            toggleBtn.style.display = 'none';
        }
    });
});
