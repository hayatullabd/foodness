{{-- Storefront Toast Notification System (100% Self-Contained Styles) --}}
<style>
#store-toast-container {
    position: fixed !important;
    top: 24px !important;
    right: 24px !important;
    z-index: 2147483647 !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
    max-width: 420px !important;
    width: calc(100% - 36px) !important;
    pointer-events: none !important;
    font-family: 'Noto Sans Bengali', system-ui, -apple-system, sans-serif !important;
}

@media (max-width: 640px) {
    #store-toast-container {
        top: 16px !important;
        right: 16px !important;
        left: 16px !important;
        width: calc(100% - 32px) !important;
        max-width: 100% !important;
    }
}

.store-toast-card {
    pointer-events: auto !important;
    display: flex !important;
    align-items: flex-start !important;
    justify-content: space-between !important;
    gap: 12px !important;
    padding: 14px 16px !important;
    border-radius: 16px !important;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.4), 0 10px 20px -5px rgba(0, 0, 0, 0.25) !important;
    border: 1.5px solid rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    animation: storeToastSlideDown 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
    transition: transform 0.25s ease, opacity 0.25s ease !important;
    cursor: default !important;
}

.store-toast-card.store-toast-hiding {
    transform: translateY(-20px) scale(0.95) !important;
    opacity: 0 !important;
}

@keyframes storeToastSlideDown {
    0% {
        transform: translateY(-30px) scale(0.92);
        opacity: 0;
    }
    100% {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.store-toast-success {
    background: linear-gradient(135deg, #013F1E 0%, #012813 100%) !important;
    border-color: rgba(52, 211, 153, 0.5) !important;
}

.store-toast-error {
    background: linear-gradient(135deg, #A5000B 0%, #750007 100%) !important;
    border-color: rgba(248, 113, 113, 0.5) !important;
}

.store-toast-warning {
    background: linear-gradient(135deg, #B45309 0%, #78350F 100%) !important;
    border-color: rgba(251, 191, 36, 0.5) !important;
}

.store-toast-info {
    background: linear-gradient(135deg, #0F172A 0%, #020617 100%) !important;
    border-color: rgba(56, 189, 248, 0.5) !important;
}

.store-toast-icon-wrap {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 26px !important;
    height: 26px !important;
    border-radius: 50% !important;
    flex-shrink: 0 !important;
    margin-top: 1px !important;
}

.store-toast-success .store-toast-icon-wrap {
    background: rgba(52, 211, 153, 0.2) !important;
    color: #34d399 !important;
}

.store-toast-error .store-toast-icon-wrap {
    background: rgba(248, 113, 113, 0.2) !important;
    color: #f87171 !important;
}

.store-toast-warning .store-toast-icon-wrap {
    background: rgba(251, 191, 36, 0.2) !important;
    color: #fbbf24 !important;
}

.store-toast-info .store-toast-icon-wrap {
    background: rgba(56, 189, 248, 0.2) !important;
    color: #38bdf8 !important;
}

.store-toast-body {
    flex: 1 1 0% !important;
    min-width: 0 !important;
}

.store-toast-text {
    font-size: 13.5px !important;
    font-weight: 600 !important;
    line-height: 1.45 !important;
    color: #ffffff !important;
    word-break: break-word !important;
    margin: 0 !important;
}

.store-toast-close {
    background: transparent !important;
    border: none !important;
    color: rgba(255, 255, 255, 0.7) !important;
    cursor: pointer !important;
    padding: 3px !important;
    margin: -3px -3px 0 0 !important;
    border-radius: 8px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: color 0.15s, background-color 0.15s !important;
}

.store-toast-close:hover {
    color: #ffffff !important;
    background-color: rgba(255, 255, 255, 0.15) !important;
}
</style>

<div id="store-toast-container" aria-live="polite"></div>

<script>
(() => {
    function getContainer() {
        let container = document.getElementById('store-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'store-toast-container';
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }
        return container;
    }

    window.showToast = function(message, type = 'success', duration = 3800) {
        if (!message) return;
        const container = getContainer();

        const normalizedType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';

        const toast = document.createElement('div');
        toast.className = `store-toast-card store-toast-${normalizedType}`;
        toast.setAttribute('role', normalizedType === 'error' ? 'alert' : 'status');

        let iconSvg = '';
        if (normalizedType === 'success') {
            iconSvg = `<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>`;
        } else if (normalizedType === 'error') {
            iconSvg = `<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`;
        } else if (normalizedType === 'warning') {
            iconSvg = `<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>`;
        } else {
            iconSvg = `<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>`;
        }

        toast.innerHTML = `
            <div class="store-toast-icon-wrap">
                ${iconSvg}
            </div>
            <div class="store-toast-body">
                <p class="store-toast-text">${message}</p>
            </div>
            <button type="button" class="store-toast-close" aria-label="Close notification">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;

        let timer = null;
        const dismiss = () => {
            if (timer) clearTimeout(timer);
            toast.classList.add('store-toast-hiding');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 260);
        };

        const closeBtn = toast.querySelector('.store-toast-close');
        closeBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            dismiss();
        });

        toast.addEventListener('click', dismiss);

        container.appendChild(toast);

        if (duration > 0) {
            timer = setTimeout(dismiss, duration);
        }
    };

    window.showStoreToast = window.showToast;

    // OVERRIDE default browser alert globally
    window.alert = function(msg) {
        if (!msg) return;
        const text = String(msg).toLowerCase();
        let type = 'info';
        if (text.includes('সফল') || text.includes('যোগ হয়েছে') || text.includes('success') || text.includes('added')) {
            type = 'success';
        } else if (text.includes('ভুল') || text.includes('ব্যর্থ') || text.includes('সমস্যা') || text.includes('হয়নি') || text.includes('error') || text.includes('fail') || text.includes('wrong')) {
            type = 'error';
        } else if (text.includes('সঠিক') || text.includes('অনুরোধ') || text.includes('দয়া করে') || text.includes('চেক') || text.includes('warning') || text.includes('required')) {
            type = 'warning';
        }
        window.showToast(msg, type, 4200);
    };

    function runWhenReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    @if (session('success'))
        runWhenReady(() => window.showToast(@json(session('success')), 'success'));
    @endif
    @if (session('error'))
        runWhenReady(() => window.showToast(@json(session('error')), 'error'));
    @endif
    @if (session('status'))
        runWhenReady(() => window.showToast(@json(session('status')), 'info'));
    @endif
})();
</script>
