<script>
    (function () {
        const eye = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" width="18" height="18" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
        const eyeOff = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" width="18" height="18" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>';

        function labels() {
            const bangla = (document.documentElement.lang || '').toLowerCase().startsWith('bn');
            return bangla
                ? { show: 'পাসওয়ার্ড দেখুন', hide: 'পাসওয়ার্ড লুকান' }
                : { show: 'Show password', hide: 'Hide password' };
        }

        function enhance(input) {
            if (!input || input.dataset.passwordToggleReady === '1') return;
            if (input.closest('[data-password-wrap]')) return;
            input.dataset.passwordToggleReady = '1';

            const wrap = document.createElement('span');
            wrap.setAttribute('data-password-wrap', '');
            wrap.style.cssText = 'position:relative;display:block;width:100%;';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
            input.style.paddingRight = '2.75rem';

            const button = document.createElement('button');
            button.type = 'button';
            button.style.cssText = 'position:absolute;top:50%;right:0.45rem;transform:translateY(-50%);display:inline-flex;align-items:center;justify-content:center;width:1.75rem;height:1.75rem;padding:0;border:0;background:transparent;color:#64748b;cursor:pointer;z-index:2;';
            button.innerHTML = eye;
            button.setAttribute('aria-label', labels().show);
            button.setAttribute('aria-pressed', 'false');
            button.addEventListener('mouseenter', function () { button.style.color = '#0f172a'; });
            button.addEventListener('mouseleave', function () { button.style.color = '#64748b'; });
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                const text = labels();
                button.setAttribute('aria-label', showing ? text.show : text.hide);
                button.setAttribute('aria-pressed', showing ? 'false' : 'true');
                button.innerHTML = showing ? eye : eyeOff;
                input.focus();
            });
            wrap.appendChild(button);
        }

        function scan(root) {
            if (!root) return;
            if (root.matches && root.matches('input[type="password"]')) {
                enhance(root);
            }
            if (root.querySelectorAll) {
                root.querySelectorAll('input[type="password"]').forEach(enhance);
            }
        }

        function boot() {
            scan(document);
            if (window.MutationObserver && document.body) {
                new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        mutation.addedNodes.forEach(function (node) {
                            if (node.nodeType === 1) scan(node);
                        });
                    });
                }).observe(document.body, { childList: true, subtree: true });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    })();
</script>
