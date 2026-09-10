/**
 * MASS DRAGON DOJO - KOMS Unified Responsive Portal Engine
 * Smooth mobile drawer, bottom nav tab sync, login drawer, touch gestures
 */

// Global Mobile Drawer Controllers
window.openSidebar = function() {
    const sidebar = document.getElementById('komsSidebar');
    const overlay = document.getElementById('komsOverlay');
    if (sidebar) {
        sidebar.classList.add('open');
        sidebar.style.transform = 'translateX(0)';
    }
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = window.innerWidth <= 1024 ? 'hidden' : '';
};

window.closeSidebar = function() {
    const sidebar = document.getElementById('komsSidebar');
    const overlay = document.getElementById('komsOverlay');
    if (sidebar) {
        sidebar.classList.remove('open');
        if (window.innerWidth <= 1024) {
            sidebar.style.transform = 'translateX(-100%)';
        } else {
            sidebar.style.transform = '';
        }
    }
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
};

window.toggleSidebar = function() {
    const sidebar = document.getElementById('komsSidebar');
    if (sidebar && sidebar.classList.contains('open')) {
        closeSidebar();
    } else {
        openSidebar();
    }
};

// Global Slide-In Login Drawer Controllers
window.openLoginDrawer = function() {
    const loginDrawer = document.getElementById('komsLoginDrawer');
    const overlay = document.getElementById('komsOverlay');
    if (loginDrawer) {
        loginDrawer.classList.add('open');
        loginDrawer.style.transform = 'translateX(0)';
    }
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    const firstInput = document.getElementById('loginIdentity');
    if (firstInput) setTimeout(() => firstInput.focus(), 300);
};

window.closeLoginDrawer = function() {
    const loginDrawer = document.getElementById('komsLoginDrawer');
    const overlay = document.getElementById('komsOverlay');
    const sidebar = document.getElementById('komsSidebar');
    if (loginDrawer) {
        loginDrawer.classList.remove('open');
        loginDrawer.style.transform = 'translateX(100%)';
    }
    if (overlay && (!sidebar || !sidebar.classList.contains('open'))) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
};

// 1-Click Demo Fill
window.quickLogin = function(identity, password) {
    openLoginDrawer();
    setTimeout(() => {
        const emailField = document.getElementById('loginIdentity');
        const passField = document.getElementById('loginPassword');
        if (emailField) emailField.value = identity;
        if (passField) passField.value = password;
    }, 150);
};

document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('komsMenuToggle');
    const sidebarClose = document.getElementById('komsSidebarClose');
    const overlay = document.getElementById('komsOverlay');
    const loginDrawerClose = document.getElementById('komsLoginDrawerClose');

    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSidebar();
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', (e) => {
            e.preventDefault();
            closeSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            closeSidebar();
            closeLoginDrawer();
        });
    }

    if (loginDrawerClose) {
        loginDrawerClose.addEventListener('click', (e) => {
            e.preventDefault();
            closeLoginDrawer();
        });
    }

    // Touch swipe left to close sidebar on mobile
    const sidebar = document.getElementById('komsSidebar');
    let touchStartX = 0;
    let touchEndX = 0;
    if (sidebar) {
        sidebar.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        sidebar.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            if (touchStartX - touchEndX > 50) {
                closeSidebar();
            }
        }, { passive: true });
    }

    // Keyboard Shortcuts
    const searchInput = document.querySelector('.koms-search-input');
    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && document.activeElement !== searchInput && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        } else if (e.key === 'Escape') {
            closeSidebar();
            closeLoginDrawer();
            if (searchInput) searchInput.blur();
        }
    });

    // Sync Bottom Nav Active States
    const currentPath = window.location.pathname.toLowerCase();
    const bottomNavItems = document.querySelectorAll('.koms-bottom-nav-item');
    bottomNavItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && !href.startsWith('#') && (currentPath.endsWith(href.toLowerCase()) || currentPath.includes(href.toLowerCase()))) {
            bottomNavItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
        }
    });
});
