document.addEventListener('DOMContentLoaded', function () {
    const manifestLink = document.querySelector('link[rel="manifest"]');
    if ('serviceWorker' in navigator && manifestLink) {
        const appBase = new URL('.', manifestLink.href);
        navigator.serviceWorker.register(new URL('service-worker.js', appBase), { scope: appBase.pathname })
            .catch(function (error) {
                console.error('TechBorrow offline support could not be registered.', error);
            });
    }

    const installButtons = document.querySelectorAll('[data-install-app]');
    if (!installButtons.length) {
        return;
    }

    let installPrompt = null;
    const installHelp = document.querySelectorAll('[data-install-help]');

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        installPrompt = event;
    });

    installButtons.forEach(function (button) {
        button.addEventListener('click', async function () {
            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                installHelp.forEach(function (help) {
                    help.textContent = 'TechBorrow is already installed on this device.';
                });
                return;
            }

            if (installPrompt) {
                installPrompt.prompt();
                const choice = await installPrompt.userChoice;
                installPrompt = null;
                if (choice.outcome === 'accepted') {
                    installHelp.forEach(function (help) {
                        help.textContent = 'TechBorrow is installing.';
                    });
                }
                return;
            }

            const isAppleMobile = /iPad|iPhone|iPod/.test(window.navigator.userAgent);
            const message = isAppleMobile
                ? 'To install, tap Share in Safari, then choose Add to Home Screen.'
                : 'To install, open your browser menu and choose Install app or Add to Home screen.';
            installHelp.forEach(function (help) {
                help.textContent = message;
            });
        });
    });

    window.addEventListener('appinstalled', function () {
        installPrompt = null;
        installHelp.forEach(function (help) {
            help.textContent = 'TechBorrow is installed on this device.';
        });
    });
});
