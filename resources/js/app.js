import './bootstrap';

const translations = {
    en: {
        offline: 'You are offline. Recent metrics may be out of date.',
        online: 'Back online. Syncing the latest data…',
        installTitle: 'Install WPS Payroll Compliance',
        installBody: 'Add the app to your home screen for offline-ready dashboards and queue monitoring.',
        installAccept: 'Install now',
        installDismiss: 'Maybe later',
        installSuccess: 'Installed. Launch WPS Payroll Compliance from your home screen.',
        installSnoozed: 'We will remind you to install the app again soon.',
    },
    ar: {
        offline: 'لا يوجد اتصال بالإنترنت. قد تكون البيانات الأخيرة غير محدثة.',
        online: 'تم استعادة الاتصال. يتم تحديث البيانات الآن…',
        installTitle: 'تثبيت منصة الامتثال لنظام حماية الأجور',
        installBody: 'أضف التطبيق إلى شاشتك الرئيسية للحصول على لوحات معلومات تعمل دون اتصال ومراقبة للطوابير.',
        installAccept: 'تثبيت الآن',
        installDismiss: 'لاحقًا',
        installSuccess: 'تم التثبيت. يمكنك فتح التطبيق من شاشتك الرئيسية.',
        installSnoozed: 'سنذكّرك بتثبيت التطبيق مرة أخرى قريبًا.',
    },
};

const locale = document.documentElement.lang?.split('-')[0] ?? 'en';
const messages = translations[locale] ?? translations.en;
const isRtl = (document.documentElement.dir ?? 'ltr') === 'rtl';

const ensurePwaStyles = () => {
    if (document.getElementById('pwa-enhancements')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'pwa-enhancements';
    style.textContent = `
        .pwa-toast {
            position: fixed;
            inset-inline-end: ${isRtl ? '1.25rem' : '1.25rem'};
            inset-inline-start: auto;
            bottom: 1.25rem;
            max-width: 22rem;
            background: rgba(15, 23, 42, 0.92);
            color: #f8fafc;
            padding: 1rem 1.25rem;
            border-radius: 1rem;
            border: 1px solid rgba(56, 189, 248, 0.35);
            box-shadow: 0 18px 35px rgba(8, 47, 73, 0.45);
            backdrop-filter: blur(18px);
            font-size: 0.95rem;
            line-height: 1.5;
            z-index: 9999;
            display: none;
        }

        .pwa-toast.is-visible {
            display: block;
        }

        .pwa-toast[data-variant='success'] {
            border-color: rgba(34, 197, 94, 0.55);
        }

        .pwa-install-banner {
            position: fixed;
            inset-inline-start: 50%;
            transform: translateX(-50%);
            bottom: 1.5rem;
            width: min(420px, calc(100vw - 2.5rem));
            background: rgba(15, 23, 42, 0.94);
            color: #f8fafc;
            padding: 1.4rem;
            border-radius: 1.25rem;
            border: 1px solid rgba(56, 189, 248, 0.35);
            box-shadow: 0 25px 40px rgba(8, 47, 73, 0.35);
            backdrop-filter: blur(18px);
            z-index: 9998;
        }

        .pwa-install-banner__actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.15rem;
            flex-wrap: wrap;
            justify-content: ${isRtl ? 'flex-start' : 'flex-end'};
        }

        .pwa-install-banner button {
            border-radius: 999px;
            border: 1px solid rgba(56, 189, 248, 0.35);
            padding: 0.65rem 1.4rem;
            font-weight: 600;
            cursor: pointer;
            background: rgba(56, 189, 248, 0.15);
            color: #f8fafc;
        }

        .pwa-install-banner button.install-primary {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.95), rgba(56, 189, 248, 0.85));
        }

        .pwa-install-banner button:focus-visible,
        .pwa-toast:focus-visible {
            outline: 3px solid rgba(56, 189, 248, 0.7);
            outline-offset: 2px;
        }

        @media (max-width: 640px) {
            .pwa-toast {
                inset-inline-start: 1rem;
                inset-inline-end: 1rem;
                max-width: unset;
            }

            .pwa-install-banner {
                inset-inline-start: 1rem;
                inset-inline-end: 1rem;
                transform: none;
                width: auto;
            }
        }
    `;

    document.head.append(style);
};

const INSTALL_DISMISSED_AT_KEY = 'wps-pwa-install-dismissed-at';

const showToast = (message, variant = 'warning') => {
    ensurePwaStyles();

    let toast = document.querySelector('.pwa-toast');

    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'pwa-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.tabIndex = -1;
        document.body.append(toast);
    }

    toast.dataset.variant = variant;
    toast.textContent = message;
    toast.classList.add('is-visible');

    setTimeout(() => {
        toast.classList.remove('is-visible');
    }, 5000);
};

const shouldSnoozeInstallPrompt = () => {
    const stored = window.localStorage.getItem(INSTALL_DISMISSED_AT_KEY);

    if (!stored) {
        return false;
    }

    const timestamp = Number.parseInt(stored, 10);

    if (Number.isNaN(timestamp)) {
        return false;
    }

    const oneDay = 24 * 60 * 60 * 1000;

    return Date.now() - timestamp < oneDay;
};

const snoozeInstallPrompt = () => {
    window.localStorage.setItem(INSTALL_DISMISSED_AT_KEY, Date.now().toString());
};

let deferredInstallPrompt = null;

const renderInstallPrompt = () => {
    if (!deferredInstallPrompt || shouldSnoozeInstallPrompt()) {
        return;
    }

    ensurePwaStyles();

    if (document.querySelector('.pwa-install-banner')) {
        return;
    }

    const banner = document.createElement('section');
    banner.className = 'pwa-install-banner';
    banner.setAttribute('role', 'dialog');
    banner.setAttribute('aria-modal', 'false');
    banner.setAttribute('aria-label', messages.installTitle);

    banner.innerHTML = `
        <h3 style="margin: 0; font-size: 1.1rem;">${messages.installTitle}</h3>
        <p style="margin: 0.75rem 0 0; line-height: 1.6;">${messages.installBody}</p>
        <div class="pwa-install-banner__actions">
            <button type="button" class="install-secondary">${messages.installDismiss}</button>
            <button type="button" class="install-primary">${messages.installAccept}</button>
        </div>
    `;

    const [dismissButton, acceptButton] = banner.querySelectorAll('button');

    dismissButton.addEventListener('click', () => {
        snoozeInstallPrompt();
        banner.remove();
        showToast(messages.installSnoozed, 'success');
    });

    acceptButton.addEventListener('click', async () => {
        acceptButton.disabled = true;

        if (!deferredInstallPrompt) {
            return;
        }

        deferredInstallPrompt.prompt();

        try {
            const result = await deferredInstallPrompt.userChoice;

            if (result.outcome === 'accepted') {
                showToast(messages.installSuccess, 'success');
            } else {
                snoozeInstallPrompt();
            }
        } catch (error) {
            console.error('Install prompt error', error);
        }

        banner.remove();
        deferredInstallPrompt = null;
    });

    document.body.append(banner);

    setTimeout(() => {
        if (typeof banner.focus === 'function') {
            banner.focus();
        }
    }, 0);
};

const registerServiceWorker = () => {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);

    if (window.location.protocol !== 'https:' && !isLocalhost) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/service-worker.js')
            .catch((error) => console.error('Service worker registration failed', error));
    });
};

registerServiceWorker();

if (typeof window !== 'undefined') {
    ensurePwaStyles();

    if (!navigator.onLine) {
        showToast(messages.offline);
    }

    window.addEventListener('offline', () => showToast(messages.offline));
    window.addEventListener('online', () => showToast(messages.online, 'success'));

    window.addEventListener('beforeinstallprompt', (event) => {
        deferredInstallPrompt = event;
        event.preventDefault();
        renderInstallPrompt();
    });

    window.addEventListener('appinstalled', () => {
        showToast(messages.installSuccess, 'success');
        deferredInstallPrompt = null;
        window.localStorage.removeItem(INSTALL_DISMISSED_AT_KEY);

        const banner = document.querySelector('.pwa-install-banner');

        if (banner) {
            banner.remove();
        }
    });
}
