@php
    /** @var array $tracking */
    $google = $tracking['google'] ?? [];
    $gtmDomain = ! empty($google['custom_server_url']) ? rtrim($google['custom_server_url'], '/') : 'https://www.googletagmanager.com';
@endphp

@if (! empty($google['enabled']) && filled($google['gtm_container_id'] ?? null))
    <!-- Google Tag Manager (noscript) -->
    <noscript>
        <iframe
            src="{{ $gtmDomain }}/ns.html?id={{ $google['gtm_container_id'] }}"
            height="0"
            width="0"
            style="display:none;visibility:hidden"
            title="Google Tag Manager"
        ></iframe>
    </noscript>
    <!-- End Google Tag Manager (noscript) -->
@endif
