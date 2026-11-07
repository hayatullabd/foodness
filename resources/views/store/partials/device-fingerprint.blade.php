{{-- Advanced Persistent Hardware & Browser Fingerprinting --}}
{{-- Combines Canvas 2D + WebGL + AudioContext + CPU Cores + Screen Resolution to build a stable hardware hash --}}
<script>
    (() => {
        'use strict';

        function getCanvasSignature() {
            try {
                const canvas = document.createElement('canvas');
                canvas.width = 240;
                canvas.height = 60;
                const ctx = canvas.getContext('2d');
                if (!ctx) return 'no-canvas';

                ctx.textBaseline = 'top';
                ctx.font = '14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
                ctx.fillStyle = '#f60';
                ctx.fillRect(10, 5, 80, 45);

                ctx.fillStyle = '#069';
                ctx.font = '15px "Times New Roman", Georgia, serif';
                ctx.fillText('Foodness 🛍️ ৳99 🇧🇩', 15, 12);

                ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
                ctx.font = '13px monospace';
                ctx.fillText('fp:canvas:v1', 20, 32);

                ctx.strokeStyle = '#000';
                ctx.beginPath();
                ctx.arc(140, 30, 20, 0, Math.PI * 2, true);
                ctx.stroke();

                ctx.globalCompositeOperation = 'multiply';
                ctx.fillStyle = 'rgb(255,0,255)';
                ctx.fillRect(130, 20, 30, 30);

                return canvas.toDataURL();
            } catch (e) {
                return 'canvas-err';
            }
        }

        function getWebGLDetails() {
            try {
                const canvas = document.createElement('canvas');
                const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                if (!gl) return { vendor: 'no-webgl', renderer: 'no-webgl', signature: 'no-webgl' };

                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                const vendor = debugInfo ? gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) : 'unknown-vendor';
                const renderer = debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : 'unknown-renderer';
                const maxTexture = gl.getParameter(gl.MAX_TEXTURE_SIZE) || 0;
                const maxRenderbuffer = gl.getParameter(gl.MAX_RENDERBUFFER_SIZE) || 0;

                return {
                    vendor,
                    renderer,
                    signature: `${vendor}~${renderer}~tex:${maxTexture}~rb:${maxRenderbuffer}`
                };
            } catch (e) {
                return { vendor: 'webgl-err', renderer: 'webgl-err', signature: 'webgl-err' };
            }
        }

        async function getAudioSignature() {
            try {
                const AudioCtx = window.OfflineAudioContext || window.webkitOfflineAudioContext;
                if (!AudioCtx) return 'no-audio';

                const context = new AudioCtx(1, 5000, 44100);
                const oscillator = context.createOscillator();
                oscillator.type = 'triangle';
                oscillator.frequency.value = 10000;

                const compressor = context.createDynamicsCompressor();
                compressor.threshold.value = -50;
                compressor.knee.value = 40;
                compressor.ratio.value = 12;
                compressor.reduction.value = -20;
                compressor.attack.value = 0;
                compressor.release.value = 0.25;

                oscillator.connect(compressor);
                compressor.connect(context.destination);
                oscillator.start(0);

                const renderedBuffer = await context.startRendering();
                const channelData = renderedBuffer.getChannelData(0);
                let sum = 0;
                for (let i = 4500; i < 5000; i++) {
                    sum += Math.abs(channelData[i]);
                }
                return 'audio:' + sum.toFixed(6);
            } catch (e) {
                return 'audio-err';
            }
        }

        function getHardwareProfile() {
            const nav = navigator;
            const scr = window.screen;
            return [
                scr?.width || 0,
                scr?.height || 0,
                scr?.colorDepth || 0,
                scr?.pixelDepth || 0,
                window.devicePixelRatio || 1,
                nav?.hardwareConcurrency || 0,
                nav?.deviceMemory || 0,
                nav?.maxTouchPoints || 0,
                nav?.platform || '',
                Intl?.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone : '',
                new Date().getTimezoneOffset(),
            ].join('~');
        }

        async function computeHash(str) {
            if (window.crypto && window.crypto.subtle && window.TextEncoder) {
                try {
                    const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(str));
                    const arr = Array.from(new Uint8Array(buf));
                    return arr.map(b => b.toString(16).padStart(2, '0')).join('').substring(0, 32);
                } catch (e) {}
            }
            // Fast 32-character polynomial fallback hash
            let h1 = 0xdeadbeef, h2 = 0x41c64e6d;
            for (let i = 0; i < str.length; i++) {
                const ch = str.charCodeAt(i);
                h1 = Math.imul(h1 ^ ch, 2654435761);
                h2 = Math.imul(h2 ^ ch, 1597334677);
            }
            h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507) ^ Math.imul(h2 ^ (h2 >>> 13), 3266489909);
            h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507) ^ Math.imul(h1 ^ (h1 >>> 13), 3266489909);
            const hex1 = (h1 >>> 0).toString(16).padStart(8, '0');
            const hex2 = (h2 >>> 0).toString(16).padStart(8, '0');
            return (hex1 + hex2 + hex1 + hex2).substring(0, 32);
        }

        function injectIntoForms(did, telemetry) {
            document.querySelectorAll('form').forEach(form => {
                const action = (form.getAttribute('action') || '').toLowerCase();
                if (action.includes('checkout') || action.includes('order')) {
                    if (did) {
                        let hidden = form.querySelector('input[name="device_id"]');
                        if (!hidden) {
                            hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'device_id';
                            form.appendChild(hidden);
                        }
                        hidden.value = did;
                    }
                    if (telemetry) {
                        let telInput = form.querySelector('input[name="device_telemetry"]');
                        if (!telInput) {
                            telInput = document.createElement('input');
                            telInput.type = 'hidden';
                            telInput.name = 'device_telemetry';
                            form.appendChild(telInput);
                        }
                        telInput.value = typeof telemetry === 'string' ? telemetry : JSON.stringify(telemetry);
                    }
                }
            });
        }

        function persistDeviceId(did) {
            if (!did) return;
            window.foodness_did = did;

            // 1. Persistent 1-year Cookie (sent with all HTTP requests)
            const exp = new Date(Date.now() + 365 * 864e5).toUTCString();
            document.cookie = `foodness_did=${did}; path=/; expires=${exp}; SameSite=Lax`;

            // 2. LocalStorage & SessionStorage
            try {
                localStorage.setItem('foodness_hw_did', did);
                sessionStorage.setItem('foodness_hw_did', did);
            } catch (e) {}

            // 3. Inject into any checkout form present on page
            injectIntoForms(did, window.foodness_telemetry);
        }

        function persistTelemetry(telemetry) {
            if (!telemetry) return;
            window.foodness_telemetry = telemetry;
            const str = JSON.stringify(telemetry);

            try {
                localStorage.setItem('foodness_dt', str);
                sessionStorage.setItem('foodness_dt', str);
            } catch (e) {}

            const exp = new Date(Date.now() + 365 * 864e5).toUTCString();
            document.cookie = `foodness_dt=${encodeURIComponent(str)}; path=/; expires=${exp}; SameSite=Lax`;

            injectIntoForms(window.foodness_did, telemetry);
        }

        // Fast synchronous pre-seed if cached in localStorage or cookie
        let cached = null;
        try {
            cached = localStorage.getItem('foodness_hw_did');
        } catch (e) {}
        if (!cached) {
            const m = document.cookie.match(/(?:^|;\s*)foodness_did=([^;]+)/);
            if (m) cached = decodeURIComponent(m[1]);
        }
        if (cached && cached.startsWith('did_hw_')) {
            persistDeviceId(cached);
        }

        let cachedTel = null;
        try {
            cachedTel = localStorage.getItem('foodness_dt');
        } catch (e) {}
        if (cachedTel) {
            try {
                window.foodness_telemetry = JSON.parse(cachedTel);
                injectIntoForms(cached, window.foodness_telemetry);
            } catch (e) {}
        }

        // Compute full hardware fingerprint
        const computeAndStore = async () => {
            try {
                const webglInfo = getWebGLDetails();
                const [canvas, audio, hardware] = await Promise.all([
                    Promise.resolve(getCanvasSignature()),
                    getAudioSignature(),
                    Promise.resolve(getHardwareProfile()),
                ]);

                const compositeSignature = `hw:v1|c:${canvas}|w:${webglInfo.signature}|a:${audio}|h:${hardware}`;
                const hash = await computeHash(compositeSignature);
                const finalDid = 'did_hw_' + hash;

                persistDeviceId(finalDid);

                const telemetry = {
                    screen: `${window.screen?.width || 0}x${window.screen?.height || 0}`,
                    color_depth: `${window.screen?.colorDepth || 0}-bit`,
                    pixel_ratio: window.devicePixelRatio || 1,
                    gpu_vendor: webglInfo.vendor,
                    gpu_renderer: webglInfo.renderer,
                    cores: navigator.hardwareConcurrency || 0,
                    memory_gb: navigator.deviceMemory || 0,
                    touch_points: navigator.maxTouchPoints || 0,
                    platform: navigator.platform || '',
                    timezone: Intl?.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone : '',
                    language: navigator.language || '',
                    audio_sig: audio,
                    canvas_sig: canvas ? 'active' : 'none',
                };
                persistTelemetry(telemetry);
            } catch (err) {
                if (!window.foodness_did) {
                    const fallback = 'did_' + Date.now().toString(36) + '_' + Math.random().toString(36).substring(2, 10);
                    persistDeviceId(fallback);
                }
            }
        };

        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(() => { computeAndStore(); });
        } else {
            setTimeout(computeAndStore, 50);
        }

        // Ensure forms created or submitted later always have the device_id & telemetry
        document.addEventListener('submit', (e) => {
            if (e.target && e.target.tagName === 'FORM') {
                injectIntoForms(window.foodness_did, window.foodness_telemetry);
            }
        }, true);
    })();
</script>
