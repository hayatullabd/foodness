@php
    /** @var array $tracking */
    $facebook = $tracking['facebook'] ?? [];
    $google = $tracking['google'] ?? [];
    $tiktok = $tracking['tiktok'] ?? [];
    $gtmDomain = ! empty($google['custom_server_url']) ? rtrim($google['custom_server_url'], '/') : 'https://www.googletagmanager.com';
@endphp

<script>
    window.dataLayer = window.dataLayer || [];
</script>

@if (! empty($google['enabled']) && filled($google['gtm_container_id'] ?? null))
    <!-- Google Tag Manager -->
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        '{{ $gtmDomain }}/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',@js($google['gtm_container_id']));
    </script>
    <!-- End Google Tag Manager -->
@endif

@if (! empty($google['gtag_enabled']))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $google['ga4_measurement_id'] ?: $google['google_ads_id'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        @if (filled($google['ga4_measurement_id'] ?? null))
            gtag('config', @js($google['ga4_measurement_id']));
        @endif
        @if (filled($google['google_ads_id'] ?? null))
            gtag('config', @js($google['google_ads_id']));
        @endif
    </script>
@endif

@if (! empty($facebook['enabled']) && filled($facebook['pixel_id'] ?? null))
    <!-- Facebook Pixel -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('set', 'autoConfig', false, @js($facebook['pixel_id']));
        fbq('init', @js($facebook['pixel_id']));
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id={{ $facebook['pixel_id'] }}&ev=PageView&noscript=1"
             alt="">
    </noscript>
    <!-- End Facebook Pixel -->
@endif

@if (! empty($tiktok['enabled']) && filled($tiktok['pixel_id'] ?? null))
    <!-- TikTok Pixel -->
    <script>
        !function (w, d, t) {
            w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
            ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"];
            ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
            for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
            ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};
            ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.partner;
            ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=r;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};
            n=document.createElement("script");n.type="text/javascript";n.async=!0;n.src=r+"?sdkid="+e+"&lib="+t;
            e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};
            ttq.load(@js($tiktok['pixel_id']));
            ttq.page();
        }(window, document, 'ttq');
    </script>
    <!-- End TikTok Pixel -->
@endif

{{-- Unified Session/Page Event Tracker Component (Auto-Deduplicated) --}}
@include('store.partials.tracking-event')
