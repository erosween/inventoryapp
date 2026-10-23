<script>
    (() => {
        const root = document.documentElement;
        root.classList.add('surya-sidebar-initializing');
        try {
            if (window.innerWidth > 1100 && localStorage.getItem('surya-sidebar-collapsed') === 'true') {
                root.classList.add('surya-sidebar-precollapsed');
            }
        } catch (error) {}

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.enterprise-sidebar a').forEach((link) => {
                link.addEventListener('click', () => root.classList.add('surya-sidebar-initializing'));
            });
            requestAnimationFrame(() => requestAnimationFrame(() => {
                root.classList.remove('surya-sidebar-initializing');
                root.classList.add('surya-sidebar-ready');
            }));
        }, { once: true });
    })();
</script>
