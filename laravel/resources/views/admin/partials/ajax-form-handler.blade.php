{{-- Admin Global AJAX Form Handler & Toast Notification System --}}
<div id="admin-toast-container" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none max-w-sm w-full px-3"></div>

<script>
(() => {
    // -------------------------------------------------------------
    // Toast Notification System
    // -------------------------------------------------------------
    const toastContainer = document.getElementById('admin-toast-container');

    window.showAdminToast = (message, type = 'success', duration = 3500) => {
        if (! toastContainer || ! message) return;

        const toast = document.createElement('div');
        toast.className = 'pointer-events-auto flex items-center justify-between gap-3 px-4 py-3 rounded-lg shadow-lg text-xs sm:text-sm font-medium border transition-all duration-300 transform -translate-y-2 opacity-0';

        let iconSvg = '';
        let colorClasses = '';

        if (type === 'success') {
            colorClasses = 'bg-emerald-900/90 text-white border-emerald-700/50 backdrop-blur-md';
            iconSvg = `<svg class="h-4 w-4 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>`;
        } else if (type === 'error') {
            colorClasses = 'bg-rose-900/90 text-white border-rose-700/50 backdrop-blur-md';
            iconSvg = `<svg class="h-4 w-4 shrink-0 text-rose-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>`;
        } else if (type === 'warning') {
            colorClasses = 'bg-amber-900/90 text-white border-amber-700/50 backdrop-blur-md';
            iconSvg = `<svg class="h-4 w-4 shrink-0 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>`;
        } else {
            colorClasses = 'bg-ink/90 text-white border-ink/40 backdrop-blur-md';
            iconSvg = `<svg class="h-4 w-4 shrink-0 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>`;
        }

        toast.className += ` ${colorClasses}`;
        toast.innerHTML = `
            <div class="flex items-center gap-2.5 flex-1">
                ${iconSvg}
                <span class="leading-tight">${message}</span>
            </div>
            <button type="button" class="text-white/60 hover:text-white transition p-0.5 ml-2" aria-label="Close">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        `;

        const closeBtn = toast.querySelector('button');
        const dismiss = () => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('-translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        };

        closeBtn?.addEventListener('click', dismiss);
        toastContainer.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.remove('-translate-y-2', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });

        if (duration > 0) {
            setTimeout(dismiss, duration);
        }
    };

    window.showToast = window.showAdminToast;

    // Override browser default alert across admin
    window.alert = function(msg) {
        if (!msg) return;
        const text = String(msg).toLowerCase();
        let type = 'info';
        if (text.includes('success') || text.includes('সফল') || text.includes('saved') || text.includes('updated')) {
            type = 'success';
        } else if (text.includes('error') || text.includes('ব্যর্থ') || text.includes('ভুল') || text.includes('failed')) {
            type = 'error';
        } else if (text.includes('warning') || text.includes('সতর্ক') || text.includes('required') || text.includes('select')) {
            type = 'warning';
        }
        window.showAdminToast(msg, type, 4000);
    };

    // Show initial session flash if present
    @if (session('success'))
        window.showAdminToast(@json(session('success')), 'success');
    @endif
    @if (session('error'))
        window.showAdminToast(@json(session('error')), 'error');
    @endif

    // -------------------------------------------------------------
    // Global AJAX Form Submitter
    // -------------------------------------------------------------
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (! (form instanceof HTMLFormElement)) return;

        // Skip conditions
        if (form.hasAttribute('data-no-ajax')) return;
        if (form.getAttribute('method')?.toUpperCase() === 'GET') return;
        if (form.action && form.action.includes('/logout')) return;
        if (form.hasAttribute('target') && form.getAttribute('target') === '_blank') return;

        // Prevent standard page reload
        e.preventDefault();

        // Find submit button
        const submitBtn = e.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        const originalHtml = submitBtn ? submitBtn.innerHTML : '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 inline-block text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Saving...</span>
            `;
        }

        // Clear existing errors
        form.querySelectorAll('.admin-ajax-error').forEach(el => el.remove());
        form.querySelectorAll('.has-ajax-error').forEach(el => {
            el.classList.remove('has-ajax-error', '!border-accent', '!ring-1', '!ring-accent');
        });

        // Collect form data
        const formData = new FormData(form);
        if (e.submitter && e.submitter.name) {
            formData.append(e.submitter.name, e.submitter.value);
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                }
            });

            const data = await response.json().catch(() => null);

            if (response.ok && data?.success !== false) {
                // Success
                const message = data?.message || 'Changes saved successfully!';
                window.showAdminToast(message, 'success');

                // Clear uploaded file inputs to prevent re-upload on next submit
                form.querySelectorAll('input[type="file"]').forEach((fi) => {
                    fi.value = '';
                });

                const methodInput = form.querySelector('input[name="_method"]');
                const isDelete = (methodInput && methodInput.value.toUpperCase() === 'DELETE')
                    || form.getAttribute('method')?.toUpperCase() === 'DELETE'
                    || (form.action && (form.action.includes('/destroy') || form.action.endsWith('/delete')));
                const isDuplicate = form.action && form.action.includes('/duplicate');
                const isToggle = form.action && form.action.includes('/toggle');

                // 1. If this was a DELETE action
                if (isDelete) {
                    const tableRow = form.closest('tr');
                    if (tableRow) {
                        tableRow.style.transition = 'all 0.25s ease';
                        tableRow.style.opacity = '0';
                        tableRow.style.transform = 'scale(0.96)';
                        setTimeout(() => {
                            tableRow.remove();
                            const tbody = tableRow.closest('tbody');
                            if (tbody && tbody.querySelectorAll('tr').length === 0) {
                                window.location.reload();
                            }
                        }, 250);
                        return;
                    }

                    // If deleted from an edit page, redirect back to index
                    if (data?.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 400);
                        return;
                    }
                }

                // 2. If this was a DUPLICATE action, reload or go to the edit page of the clone
                if (isDuplicate) {
                    setTimeout(() => {
                        if (data?.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.reload();
                        }
                    }, 400);
                    return;
                }

                // 3. If this was a TOGGLE action, reload so badges and switches reflect new state
                if (isToggle) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 350);
                    return;
                }

                // 4. If form specifies reload on success
                if (form.hasAttribute('data-reload-on-success')) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 400);
                    return;
                }

                // 5. If this is a create page that redirects to an edit page or index
                if (data?.redirect) {
                    const currentUrl = window.location.href.split('?')[0].replace(/\/+$/, '');
                    const targetUrl = data.redirect.split('?')[0].replace(/\/+$/, '');

                    // Only redirect if navigating to a different page (e.g. from create to edit or index)
                    if (currentUrl !== targetUrl && ! currentUrl.endsWith('/edit')) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 500);
                        return;
                    }
                }

                // 6. If form is a delete form for an image or item
                if (form.id && form.id.startsWith('delete-product-image-')) {
                    const imgId = form.id.replace('delete-product-image-', '');
                    const imageCard = form.closest('[data-image-card]') 
                        || document.querySelector(`[data-image-id="${imgId}"]`)
                        || document.querySelector(`[data-gallery-item][data-image-id="${imgId}"]`);
                    imageCard?.remove();
                    window.updateGalleryOrderNumbers?.();
                } else if (form.id === 'delete-product-meta-image') {
                    const metaSlot = document.querySelector('[data-meta-current]');
                    metaSlot?.remove();
                }

                // Dispatch global event for custom page handlers
                document.dispatchEvent(new CustomEvent('admin:form-saved', {
                    detail: { form, data, submitter: e.submitter }
                }));
            } else if (response.status === 422) {
                // Validation error
                const message = data?.message || 'Please check the form for errors.';
                window.showAdminToast(message, 'error');

                if (data?.errors) {
                    let firstErrorInput = null;

                    Object.entries(data.errors).forEach(([field, messages]) => {
                        let input = form.querySelector(`[name="${field}"]`)
                            || form.querySelector(`[name="${field}[]"]`);

                        if (! input && field.includes('.')) {
                            const parts = field.split('.');
                            const bracketName = parts[0] + parts.slice(1).map(p => `[${p}]`).join('');
                            input = form.querySelector(`[name="${bracketName}"]`);
                        }

                        if (input) {
                            if (! firstErrorInput) firstErrorInput = input;
                            input.classList.add('has-ajax-error', '!border-accent', '!ring-1', '!ring-accent');
                            const errEl = document.createElement('p');
                            errEl.className = 'admin-ajax-error text-[11px] text-accent font-medium mt-1';
                            errEl.textContent = Array.isArray(messages) ? messages[0] : messages;
                            const parent = input.closest('div') || input.parentElement;
                            parent?.appendChild(errEl);
                        }
                    });

                    if (firstErrorInput) {
                        firstErrorInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstErrorInput.focus?.();
                    }
                }
            } else {
                const message = data?.message || (response.status === 404 ? 'Item not found or already removed.' : 'An unexpected error occurred (Status: ' + response.status + '). Please try again.');
                window.showAdminToast(message, 'error');
            }
        } catch (err) {
            console.error('AJAX Form Error:', err);
            window.showAdminToast('Network error. Please check your connection and try again.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }
        }
    });
})();
</script>
