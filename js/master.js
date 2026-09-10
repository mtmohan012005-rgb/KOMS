document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    const menuButton = document.getElementById('menuButton');

    function openSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (menuButton) {
        menuButton.addEventListener('click', function () {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
    }

    if (overlay) overlay.addEventListener('click', closeSidebar);

    const navLinks = document.querySelectorAll('.sidebar-nav a');
    navLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            const href = this.getAttribute('href') || '';
            if (href.startsWith('#')) {
                const target = document.querySelector(href);
                if (target) {
                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                navLinks.forEach(item => item.classList.remove('active'));
                this.classList.add('active');
                if (window.innerWidth <= 850) closeSidebar();
            }
        });
    });

    window.showToast = function (message) {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        clearTimeout(window.massDragonToastTimer);
        window.massDragonToastTimer = setTimeout(function () {
            toast.classList.remove('show');
        }, 2300);
    };

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeSidebar();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 850) closeSidebar();
    });

    const sections = document.querySelectorAll('[id]');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                const id = entry.target.id;
                navLinks.forEach(function (link) {
                    link.classList.toggle('active', link.getAttribute('href') === '#' + id);
                });
            });
        }, { rootMargin: '-20% 0px -65% 0px', threshold: 0.01 });
        sections.forEach(section => observer.observe(section));
    }

    console.log('Mass Dragon Dojo Master Portal loaded.');
});