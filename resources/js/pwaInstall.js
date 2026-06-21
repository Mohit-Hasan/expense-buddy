let deferredPrompt = null;

export function isPwaInstalled() {
    return (
        window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true
    );
}

export function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

function isIosSafari() {
    const ua = window.navigator.userAgent;
    const isIos = /iPad|iPhone|iPod/.test(ua);
    const isSafari = /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS/.test(ua);

    return isIos && isSafari;
}

export function initPwaInstall() {
    if (isPwaInstalled()) {
        return;
    }

    const banner = document.getElementById('pwa-install-banner');
    const iosHint = document.getElementById('pwa-ios-hint');
    const installBtn = document.getElementById('pwa-install-btn');
    const dismissBtn = document.getElementById('pwa-install-dismiss');

    if (!banner && !iosHint) {
        return;
    }

    if (localStorage.getItem('pwa-install-dismissed') === '1') {
        return;
    }

    if (isIosSafari() && iosHint) {
        iosHint.classList.remove('hidden');
        return;
    }

    if (!banner || !installBtn) {
        return;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        banner.classList.remove('hidden');
    });

    installBtn.addEventListener('click', async () => {
        if (!deferredPrompt) {
            return;
        }

        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        deferredPrompt = null;
        banner.classList.add('hidden');

        if (outcome === 'dismissed') {
            localStorage.setItem('pwa-install-dismissed', '1');
        }
    });

    dismissBtn?.addEventListener('click', () => {
        banner.classList.add('hidden');
        localStorage.setItem('pwa-install-dismissed', '1');
    });
}
