import Alpine from 'alpinejs';
import 'preline';

window.Alpine = Alpine;

Alpine.start();

// Instant hover prefetching for seamless zero-delay page navigation
document.addEventListener('DOMContentLoaded', () => {
    const prefetchedUrls = new Set();
    const prefetch = (url) => {
        if (!url || prefetchedUrls.has(url) || url.startsWith('#') || url.startsWith('javascript:')) return;
        try {
            const parsed = new URL(url, window.location.origin);
            if (parsed.origin !== window.location.origin) return;
            prefetchedUrls.add(url);
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = url;
            document.head.appendChild(link);
        } catch (e) {}
    };

    document.addEventListener('mouseover', (e) => {
        const anchor = e.target.closest('a');
        if (anchor && anchor.href) {
            prefetch(anchor.href);
        }
    }, { passive: true });

    document.addEventListener('touchstart', (e) => {
        const anchor = e.target.closest('a');
        if (anchor && anchor.href) {
            prefetch(anchor.href);
        }
    }, { passive: true });

    // Global Auto-Dismiss for Flash Notification Alerts anywhere in the application
    const autoDismiss = (el, delay = 4000) => {
        if (!el || el.dataset.dismissScheduled) return;
        el.dataset.dismissScheduled = 'true';
        setTimeout(() => {
            el.style.transition = 'opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1), transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), margin 0.35s ease, padding 0.35s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(() => {
                el.style.maxHeight = '0';
                el.style.paddingTop = '0';
                el.style.paddingBottom = '0';
                el.style.marginTop = '0';
                el.style.marginBottom = '0';
                el.style.overflow = 'hidden';
                setTimeout(() => {
                    if (el.parentNode) el.remove();
                }, 350);
            }, 250);
        }, delay);
    };

    const alertSelectors = [
        '[role="alert"]',
        '.alert-auto-dismiss',
        '[data-auto-dismiss]'
    ];

    document.querySelectorAll(alertSelectors.join(', ')).forEach((alert) => {
        if (!alert.closest('.modal') && !alert.classList.contains('no-auto-dismiss')) {
            autoDismiss(alert, 4000);
        }
    });
});


