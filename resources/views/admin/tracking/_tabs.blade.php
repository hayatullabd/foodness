@php
    $tabs = [
        [
            'label' => 'Facebook Pixel',
            'route' => 'admin.tracking.facebook.edit',
            'active' => request()->routeIs('admin.tracking.facebook.*'),
            'description' => 'Browser tracking (PageView, ViewContent, AddToCart, Purchase)',
        ],
        [
            'label' => 'Facebook CAPI',
            'route' => 'admin.tracking.facebook-capi.edit',
            'active' => request()->routeIs('admin.tracking.facebook-capi.*'),
            'description' => 'Server-Side Conversions API for 100% conversion tracking',
        ],
        [
            'label' => 'Google Tag Manager & GA4',
            'route' => 'admin.tracking.google.edit',
            'active' => request()->routeIs('admin.tracking.google.*'),
            'description' => 'GTM Container, GA4 & Google Ads dataLayer integration',
        ],
        [
            'label' => 'TikTok Pixel',
            'route' => 'admin.tracking.tiktok.edit',
            'active' => request()->routeIs('admin.tracking.tiktok.*'),
            'description' => 'TikTok browser events & ads tracking',
        ],
    ];
@endphp

<div class="mb-6 border-b border-slate-200">
    <nav class="-mb-px flex flex-wrap gap-2 sm:gap-3" aria-label="Tracking integrations">
        @foreach ($tabs as $tab)
            <a
                href="{{ route($tab['route']) }}"
                @class([
                    'inline-flex items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium transition',
                    'border-slate-900 text-slate-900' => $tab['active'],
                    'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => ! $tab['active'],
                ])
                @if ($tab['active']) aria-current="page" @endif
            >
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </nav>
</div>

@once
    <script>
        (function () {
            if (window.__foodnessTrackingRevokeBound) return;
            window.__foodnessTrackingRevokeBound = true;

            document.addEventListener('click', function (event) {
                const btn = event.target.closest('[data-tracking-revoke]');
                if (!btn) return;

                const action = btn.getAttribute('data-action');
                const confirmMsg = btn.getAttribute('data-confirm') || 'Revoke this credential?';
                if (!action || !window.confirm(confirmMsg)) return;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = action;
                form.style.display = 'none';

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name="_token"]')?.value
                    || '';
                form.appendChild(csrf);

                const scope = document.createElement('input');
                scope.type = 'hidden';
                scope.name = 'scope';
                scope.value = btn.getAttribute('data-scope') || 'fields';
                form.appendChild(scope);

                let fields = [];
                try {
                    fields = JSON.parse(btn.getAttribute('data-fields') || '[]');
                } catch (e) {
                    fields = [];
                }
                fields.forEach(function (field) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'fields[]';
                    input.value = field;
                    form.appendChild(input);
                });

                if (btn.getAttribute('data-deactivate') === '1') {
                    const deactivate = document.createElement('input');
                    deactivate.type = 'hidden';
                    deactivate.name = 'deactivate';
                    deactivate.value = '1';
                    form.appendChild(deactivate);
                }

                document.body.appendChild(form);
                form.submit();
            });
        })();
    </script>
@endonce
