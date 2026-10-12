<style>
    [data-pwa-install][hidden] {
        display: none !important;
    }
</style>

<script>
    (function () {
        let deferredInstallPrompt = null;
        const installButtons = Array.from(document.querySelectorAll('[data-pwa-install]'));
        const standaloneMedia = window.matchMedia('(display-mode: standalone)');
        const isStandalone = function () {
            return standaloneMedia.matches || window.navigator.standalone === true;
        };

        const userAgent = window.navigator.userAgent || '';
        const isIos = /iPad|iPhone|iPod/.test(userAgent) ||
            (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
        const isFirefox = /Firefox|FxiOS/.test(userAgent);

        const setInstallButtonVisibility = function (visible) {
            installButtons.forEach(function (button) {
                button.hidden = !visible;
                button.setAttribute('aria-hidden', visible ? 'false' : 'true');
            });
        };

        const showMessage = function (title, html, icon) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: title,
                    html: html,
                    icon: icon || 'info',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#c1121f'
                });
                return;
            }

            const plainText = html.replace(/<br\s*\/?\s*>/gi, '\n').replace(/<[^>]*>/g, '');
            window.alert(title + '\n\n' + plainText);
        };

        const manualInstallGuide = function () {
            if (isIos) {
                showMessage(
                    'Pasang di iPhone/iPad',
                    'Buka menu <strong>Bagikan</strong> di Safari, lalu pilih <strong>Tambahkan ke Layar Utama</strong>.',
                    'info'
                );
                return;
            }

            if (isFirefox) {
                showMessage(
                    'Pasang Aplikasi',
                    'Buka menu browser, lalu pilih <strong>Install</strong> atau <strong>Tambahkan ke Layar Utama</strong>.',
                    'info'
                );
                return;
            }

            showMessage(
                'Pasang Aplikasi',
                'Buka menu browser dan pilih <strong>Install app</strong>, <strong>Apps → Install</strong>, atau <strong>Tambahkan ke Layar Utama</strong>.',
                'info'
            );
        };

        installButtons.forEach(function (button) {
            button.addEventListener('click', async function (event) {
                event.preventDefault();

                if (isStandalone()) {
                    setInstallButtonVisibility(false);
                    return;
                }

                if (!deferredInstallPrompt) {
                    manualInstallGuide();
                    return;
                }

                deferredInstallPrompt.prompt();
                await deferredInstallPrompt.userChoice;
                deferredInstallPrompt = null;
            });
        });

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredInstallPrompt = event;
            setInstallButtonVisibility(true);
        });

        window.addEventListener('appinstalled', function () {
            deferredInstallPrompt = null;
            setInstallButtonVisibility(false);
            showMessage('Aplikasi Terpasang', 'Aplikasi berhasil ditambahkan ke perangkat ini.', 'success');
        });

        const displayModeChanged = function () {
            setInstallButtonVisibility(!isStandalone());
        };

        if (typeof standaloneMedia.addEventListener === 'function') {
            standaloneMedia.addEventListener('change', displayModeChanged);
        } else if (typeof standaloneMedia.addListener === 'function') {
            standaloneMedia.addListener(displayModeChanged);
        }

        if (!isStandalone()) {
            setInstallButtonVisibility(true);
        }

        if ('serviceWorker' in navigator && (window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' })
                    .then(function (registration) {
                        registration.update().catch(function () {});
                    })
                    .catch(function (error) {
                        console.warn('PWA service worker gagal didaftarkan:', error);
                    });
            });
        }
    })();
</script>
