document.addEventListener('DOMContentLoaded', () => {
    // Mobile slide drawer
    const drawer = document.querySelector('[data-store-drawer]');
    const drawerBackdrop = document.querySelector('[data-drawer-backdrop]');

    const openDrawer = () => {
        const d = drawer || document.querySelector('[data-store-drawer]');
        if (! d) return;
        d.classList.remove('pointer-events-none');
        d.setAttribute('aria-hidden', 'false');
        const backdrop = drawerBackdrop || d.querySelector('[data-drawer-backdrop]');
        if (backdrop) {
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            backdrop.classList.add('opacity-100');
        }
        const panel = d.querySelector('[data-drawer-content]');
        if (panel) {
            panel.classList.remove('-translate-x-full');
            panel.classList.add('translate-x-0');
        }
        document.body.style.overflow = 'hidden';
    };

    const closeDrawer = () => {
        const d = drawer || document.querySelector('[data-store-drawer]');
        if (! d) return;
        d.classList.add('pointer-events-none');
        d.setAttribute('aria-hidden', 'true');
        const backdrop = drawerBackdrop || d.querySelector('[data-drawer-backdrop]');
        if (backdrop) {
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
        }
        const panel = d.querySelector('[data-drawer-content]');
        if (panel) {
            panel.classList.remove('translate-x-0');
            panel.classList.add('-translate-x-full');
        }
        document.body.style.overflow = '';
    };

    window.openStoreDrawer = openDrawer;
    window.closeStoreDrawer = closeDrawer;

    // Delegate click events for all current and dynamically rendered drawer triggers
    document.addEventListener('click', (e) => {
        const openTrigger = e.target.closest('[data-drawer-open]');
        if (openTrigger) {
            e.preventDefault();
            openDrawer();
            return;
        }

        const closeTrigger = e.target.closest('[data-drawer-close]');
        if (closeTrigger) {
            // If it's a link (<a>) or submit button with action, let the navigation/action proceed!
            const href = closeTrigger.getAttribute('href');
            if (closeTrigger.tagName === 'A' && href && href !== '#') {
                closeDrawer();
                return;
            }
            e.preventDefault();
            closeDrawer();
            return;
        }

        if (e.target.matches('[data-drawer-backdrop]')) {
            e.preventDefault();
            closeDrawer();
        }
    });

    // Pre-rendered close triggers (buttons only, do not prevent default on anchor links)
    document.querySelectorAll('button[data-drawer-close]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            closeDrawer();
        });
    });

    if (drawerBackdrop) {
        drawerBackdrop.addEventListener('click', closeDrawer);
    }

    // Legacy nav toggle fallback
    const legacyToggle = document.querySelector('[data-nav-toggle]');
    const legacyPanel = document.querySelector('[data-nav-panel]');
    if (legacyToggle && legacyPanel) {
        legacyToggle.addEventListener('click', () => {
            const open = legacyPanel.classList.toggle('hidden') === false;
            legacyToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Dismissible alerts
    document.querySelectorAll('[data-dismiss-alert]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const alert = btn.closest('[role="alert"]');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-6px)';
                alert.style.transition = 'all 0.25s ease';
                setTimeout(() => alert.remove(), 260);
            }
        });
    });

    // Keyboard support: Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawer && drawer.getAttribute('aria-hidden') === 'false') {
            closeDrawer();
        }
    });

    // Global Image Loading & Broken URL Safeguard
    const transparentPixel = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>';
    const safeguardImage = (img) => {
        if (!img || img.dataset.safeguarded) return;
        img.dataset.safeguarded = 'true';
        if (!img.hasAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }
        img.addEventListener('error', function () {
            const src = this.getAttribute('src') || '';
            if (!src || src.startsWith('data:image/svg+xml')) return;
            this.onerror = null;
            this.src = transparentPixel;
            this.classList.add('opacity-0', 'pointer-events-none');
        });
        img.addEventListener('load', function () {
            const src = this.currentSrc || this.src || '';
            if (src && !src.startsWith('data:')) {
                this.classList.remove('opacity-0');
            }
        });
    };

    document.querySelectorAll('img').forEach(safeguardImage);

    if (window.MutationObserver) {
        new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (node.nodeType === 1) {
                        if (node.tagName === 'IMG') safeguardImage(node);
                        else node.querySelectorAll?.('img').forEach(safeguardImage);
                    }
                }
            }
        }).observe(document.body, { childList: true, subtree: true });
    }
});
