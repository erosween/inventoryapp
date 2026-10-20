<script>
    (() => {
        try {
            if (window.innerWidth > 960 && localStorage.getItem('surya-sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('surya-sidebar-precollapsed');
            }
        } catch (error) {}
    })();
</script>
