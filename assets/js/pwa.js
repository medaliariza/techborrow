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
    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        installPrompt = event;
        installButtons.forEach(function (button) {
            button.classList.remove('d-none');
        });
    });

    installButtons.forEach(function (button) {
        button.addEventListener('click', async function () {
            if (!installPrompt) {
                return;
            }
            installPrompt.prompt();
            await installPrompt.userChoice;
            installPrompt = null;
            installButtons.forEach(function (installButton) {
                installButton.classList.add('d-none');
            });
        });
    });

    window.addEventListener('appinstalled', function () {
        installPrompt = null;
        installButtons.forEach(function (button) {
            button.classList.add('d-none');
        });
    });
});
