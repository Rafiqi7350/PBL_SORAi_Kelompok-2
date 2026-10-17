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
});

