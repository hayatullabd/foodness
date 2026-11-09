@php
    $voiceSettings = $store ?? \App\Support\StoreSettings::all();
    $checkoutVoiceOn = (string) ($voiceSettings['checkout_voice_enabled'] ?? '1') === '1';
    $checkoutVoiceClips = $checkoutVoiceOn
        ? \App\Support\CheckoutVoices::clips($voiceSettings['checkout_voice_id'] ?? 'nabanita')
        : [];
@endphp
@if ($checkoutVoiceOn)
<script>
(() => {
    if (window.__fnBnFieldVoice) return;
    window.__fnBnFieldVoice = true;

    const clips = @json($checkoutVoiceClips);
    const cache = new Map();
    let current = null;

    const stop = () => {
        if (!current) return;
        current.pause();
        current.currentTime = 0;
        current = null;
    };

    const play = (src) => {
        if (!src) return;
        stop();
        let audio = cache.get(src);
        if (!audio) {
            audio = new Audio(src);
            audio.preload = 'auto';
            cache.set(src, audio);
        }
        audio.currentTime = 0;
        current = audio;
        const attempt = audio.play();
        if (attempt && typeof attempt.catch === 'function') {
            attempt.catch(() => {});
        }
    };

    document.addEventListener('focusin', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;
        const field = target.closest('[data-bn-voice]');
        if (!field) return;
        play(clips[field.getAttribute('data-bn-voice')] || '');
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;
        if (target.closest('[data-checkout-close], [data-checkout-backdrop]')) {
            stop();
        }
    });
})();
</script>
@endif
