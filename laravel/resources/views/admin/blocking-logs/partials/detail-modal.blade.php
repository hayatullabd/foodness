{{-- Blocked Attempt Comprehensive Details Slide-Over / Modal --}}
<div
    id="blocking-log-detail-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-3 sm:p-4 backdrop-blur-xs transition-opacity duration-200"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-log-title"
>
    <div class="relative w-full max-w-4xl max-h-[92vh] flex flex-col rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden font-sans">
        {{-- Modal Top Bar --}}
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/90 px-5 py-3.5">
            <div class="flex items-center gap-2.5 flex-wrap">
                <span id="modal-log-badge-id" class="rounded bg-slate-200 px-2 py-0.5 font-mono text-xs font-bold text-slate-800">#0</span>
                <span id="modal-log-badge-type" class="rounded border px-2 py-0.5 text-xs font-semibold bg-rose-50 text-rose-800 border-rose-300">Security Vector</span>
                <span id="modal-log-badge-attempts" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700 ring-1 ring-slate-300">1 Attempt</span>
                <span id="modal-log-badge-status" class="rounded bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800 border border-amber-200">Active Block</span>
            </div>
            <button
                type="button"
                id="modal-log-close-btn"
                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition cursor-pointer"
                title="Close (Esc)"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Navigation Tabs --}}
        <div class="flex border-b border-slate-200 bg-white px-5 text-xs font-semibold text-slate-600 gap-1 overflow-x-auto">
            <button
                type="button"
                data-tab-target="tab-device"
                class="modal-tab-btn flex items-center gap-1.5 border-b-2 border-emerald-600 px-3 py-2.5 text-emerald-700 cursor-pointer"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>Device &amp; Hardware</span>
            </button>
            <button
                type="button"
                data-tab-target="tab-network"
                class="modal-tab-btn flex items-center gap-1.5 border-b-2 border-transparent px-3 py-2.5 text-slate-500 hover:text-slate-900 cursor-pointer"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253" />
                </svg>
                <span>IP &amp; Geolocation</span>
            </button>
            <button
                type="button"
                data-tab-target="tab-order"
                class="modal-tab-btn flex items-center gap-1.5 border-b-2 border-transparent px-3 py-2.5 text-slate-500 hover:text-slate-900 cursor-pointer"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span>Order &amp; Customer Attempt</span>
            </button>
            <button
                type="button"
                data-tab-target="tab-raw"
                class="modal-tab-btn flex items-center gap-1.5 border-b-2 border-transparent px-3 py-2.5 text-slate-500 hover:text-slate-900 cursor-pointer"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
                <span>Raw JSON Payload</span>
            </button>
        </div>

        {{-- Scrollable Content Body --}}
        <div class="flex-1 overflow-y-auto p-5 text-slate-700 text-xs space-y-4">
            {{-- Tab 1: Device & Hardware --}}
            <div id="tab-device" class="modal-tab-pane space-y-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Persistent Device Token / Hardware Hash</span>
                        <button
                            type="button"
                            onclick="copyToClipboard(document.getElementById('modal-device-id').textContent, this)"
                            class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 hover:text-emerald-900 cursor-pointer"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Copy Token</span>
                        </button>
                    </div>
                    <code id="modal-device-id" class="block font-mono text-xs font-bold text-purple-900 break-all bg-white p-2 rounded border border-slate-200 select-all">—</code>
                    <p class="text-[11px] text-slate-400 mt-1">Multi-layer fingerprinting: Canvas 2D + WebGL GPU + AudioContext DSP + CPU Cores + Screen Resolution. Resistant to Incognito mode and cookie clears.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-2xs">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Device Form Factor</span>
                        <div id="modal-device-type" class="text-xs font-semibold text-slate-900 mt-0.5">—</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-2xs">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Operating System</span>
                        <div id="modal-device-os" class="text-xs font-semibold text-slate-900 mt-0.5">—</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-2xs">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Web Browser</span>
                        <div id="modal-device-browser" class="text-xs font-semibold text-slate-900 mt-0.5">—</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-2xs">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Screen Resolution</span>
                        <div id="modal-device-screen" class="text-xs font-mono font-semibold text-slate-900 mt-0.5">—</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-2xs">
                        <span class="text-[10px] font-bold uppercase text-slate-400">CPU Cores &amp; RAM</span>
                        <div id="modal-device-cpu-ram" class="text-xs font-mono font-semibold text-slate-900 mt-0.5">—</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-2xs">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Timezone &amp; Locale</span>
                        <div id="modal-device-timezone" class="text-xs font-semibold text-slate-900 mt-0.5">—</div>
                    </div>
                </div>

                {{-- GPU & AudioContext --}}
                <div class="rounded-lg border border-slate-200 bg-white p-3 space-y-2">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400">GPU Graphics Renderer (WebGL)</span>
                        <div id="modal-device-gpu" class="text-xs font-mono font-semibold text-slate-800 break-words mt-0.5">—</div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400">Touch Points Support</span>
                            <div id="modal-device-touch" class="text-xs font-mono text-slate-700 mt-0.5">—</div>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400">AudioContext DSP Fingerprint</span>
                            <div id="modal-device-audio" class="text-xs font-mono text-slate-700 mt-0.5">—</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 2: IP & Geolocation --}}
            <div id="tab-network" class="modal-tab-pane hidden space-y-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Client IP Address</span>
                        <button
                            type="button"
                            onclick="copyToClipboard(document.getElementById('modal-ip-address').textContent, this)"
                            class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 hover:text-emerald-900 cursor-pointer"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Copy IP</span>
                        </button>
                    </div>
                    <code id="modal-ip-address" class="block font-mono text-sm font-bold text-sky-900 break-all bg-white p-2 rounded border border-slate-200 select-all">—</code>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-2xs">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Country &amp; Location</span>
                        <div id="modal-network-location" class="text-xs font-semibold text-slate-900 mt-0.5 flex items-center gap-1.5">
                            <span id="modal-network-flag" class="text-base">🇧🇩</span>
                            <span id="modal-network-country">Bangladesh</span>
                        </div>
                        <div id="modal-network-city-region" class="text-[11px] text-slate-500 mt-0.5">—</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-2xs">
                        <span class="text-[10px] font-bold uppercase text-slate-400">ISP &amp; Network Provider</span>
                        <div id="modal-network-isp" class="text-xs font-semibold text-slate-900 mt-0.5">—</div>
                        <div id="modal-network-org" class="text-[11px] text-slate-500 mt-0.5">—</div>
                    </div>
                </div>

                {{-- Raw User Agent --}}
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Raw HTTP User-Agent</span>
                        <button
                            type="button"
                            onclick="copyToClipboard(document.getElementById('modal-user-agent').textContent, this)"
                            class="text-[10px] font-medium text-slate-500 hover:text-slate-800 cursor-pointer"
                        >Copy UA</button>
                    </div>
                    <pre id="modal-user-agent" class="font-mono text-[11px] text-slate-700 bg-slate-50 p-2 rounded border border-slate-200 break-all whitespace-pre-wrap select-all">—</pre>
                </div>
            </div>

            {{-- Tab 3: Order & Customer Attempt --}}
            <div id="tab-order" class="modal-tab-pane hidden space-y-4">
                <div class="rounded-lg border border-slate-200 bg-white p-4 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400">Customer Name Submitted</span>
                            <div id="modal-customer-name" class="text-xs font-semibold text-slate-900 mt-0.5">—</div>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400">Phone Number Submitted</span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <code id="modal-customer-phone" class="text-xs font-mono font-bold text-slate-900 select-all">—</code>
                                <button
                                    type="button"
                                    onclick="copyToClipboard(document.getElementById('modal-customer-phone').textContent, this)"
                                    class="text-[11px] text-emerald-700 hover:text-emerald-900 cursor-pointer"
                                    title="Copy Phone"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400">Delivery Address Submitted</span>
                        <div id="modal-customer-address" class="text-xs text-slate-800 bg-slate-50 p-2.5 rounded border border-slate-200 mt-1 select-all">—</div>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold uppercase text-rose-500">Triggered Security Policy / Reason</span>
                        <div id="modal-log-reason" class="text-xs font-semibold text-rose-900 bg-rose-50 p-2.5 rounded border border-rose-200 mt-1">—</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-100 text-[11px]">
                        <div>
                            <span class="text-slate-400">First Attempted At:</span>
                            <span id="modal-first-attempt" class="font-mono text-slate-800 ml-1 font-medium">—</span>
                        </div>
                        <div>
                            <span class="text-slate-400">Last Attempted At:</span>
                            <span id="modal-last-attempt" class="font-mono text-slate-800 ml-1 font-medium">—</span>
                        </div>
                    </div>
                </div>

                {{-- Cart Items Attempted --}}
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Products in Cart During Attempt</span>
                        <span id="modal-cart-total" class="text-xs font-bold text-slate-900">Total: ৳0</span>
                    </div>
                    <div id="modal-cart-items-container" class="divide-y divide-slate-100 max-h-48 overflow-y-auto">
                        <p class="text-slate-400 py-3 text-center">No products data captured for this attempt.</p>
                    </div>
                </div>
            </div>

            {{-- Tab 4: Raw JSON --}}
            <div id="tab-raw" class="modal-tab-pane hidden space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase text-slate-400">Complete Raw JSON Payload</span>
                    <button
                        type="button"
                        onclick="copyToClipboard(document.getElementById('modal-raw-json').textContent, this)"
                        class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 cursor-pointer"
                    >Copy JSON</button>
                </div>
                <pre id="modal-raw-json" class="max-h-96 overflow-auto rounded bg-slate-900 p-3 text-[11px] font-mono text-emerald-400 select-all whitespace-pre-wrap">{ }</pre>
            </div>
        </div>

        {{-- Modal Footer with Direct Security Actions --}}
        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-3 flex-wrap gap-2">
            <div class="flex items-center gap-2 flex-wrap">
                {{-- Quick Unblock Device Form --}}
                <form id="modal-unblock-device-form" method="POST" action="{{ route('admin.blocking-logs.unblock') }}" class="inline-block hidden">
                    @csrf
                    <input type="hidden" name="type" value="device">
                    <input type="hidden" name="value" id="modal-unblock-device-value">
                    <input type="hidden" name="redirect_tab" value="history">
                    <button
                        type="submit"
                        onclick="return confirm('Unblock this device and reset rate limits so it can order again?')"
                        class="rounded bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700 transition cursor-pointer"
                    >
                        Unblock Device
                    </button>
                </form>

                {{-- Quick Unblock Phone Form --}}
                <form id="modal-unblock-phone-form" method="POST" action="{{ route('admin.blocking-logs.unblock') }}" class="inline-block hidden">
                    @csrf
                    <input type="hidden" name="type" value="phone">
                    <input type="hidden" name="value" id="modal-unblock-phone-value">
                    <input type="hidden" name="redirect_tab" value="history">
                    <button
                        type="submit"
                        onclick="return confirm('Unblock this phone number?')"
                        class="rounded bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 transition cursor-pointer"
                    >
                        Unblock Phone
                    </button>
                </form>

                {{-- Quick Unblock IP Form --}}
                <form id="modal-unblock-ip-form" method="POST" action="{{ route('admin.blocking-logs.unblock') }}" class="inline-block hidden">
                    @csrf
                    <input type="hidden" name="type" value="ip">
                    <input type="hidden" name="value" id="modal-unblock-ip-value">
                    <input type="hidden" name="redirect_tab" value="history">
                    <button
                        type="submit"
                        onclick="return confirm('Unblock this IP address?')"
                        class="rounded bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-700 transition cursor-pointer"
                    >
                        Unblock IP
                    </button>
                </form>

                {{-- Delete Log Row Form --}}
                <form id="modal-delete-log-form" method="POST" action="" class="inline-block">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        onclick="return confirm('Permanently delete this audit log entry?')"
                        class="rounded border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition cursor-pointer"
                    >
                        Delete Log
                    </button>
                </form>
            </div>

            <button
                type="button"
                id="modal-log-dismiss-btn"
                class="rounded border border-slate-300 bg-white px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition cursor-pointer"
            >
                Close
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        window.copyToClipboard = function (text, btn) {
            if (!text || text === '—') return;
            navigator.clipboard.writeText(text).then(function () {
                const orig = btn.innerHTML;
                btn.innerHTML = '<span class="text-emerald-700 font-bold">Copied!</span>';
                setTimeout(() => { btn.innerHTML = orig; }, 1500);
            });
        };

        const modal = document.getElementById('blocking-log-detail-modal');
        if (!modal) return;

        const closeBtn = document.getElementById('modal-log-close-btn');
        const dismissBtn = document.getElementById('modal-log-dismiss-btn');

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (dismissBtn) dismissBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // Tab navigation inside modal
        const tabBtns = modal.querySelectorAll('.modal-tab-btn');
        const tabPanes = modal.querySelectorAll('.modal-tab-pane');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const targetId = this.getAttribute('data-tab-target');

                tabBtns.forEach(b => {
                    b.classList.remove('border-emerald-600', 'text-emerald-700');
                    b.classList.add('border-transparent', 'text-slate-500');
                });
                this.classList.remove('border-transparent', 'text-slate-500');
                this.classList.add('border-emerald-600', 'text-emerald-700');

                tabPanes.forEach(p => {
                    p.classList.toggle('hidden', p.id !== targetId);
                });
            });
        });

        // Function to populate and open modal with log data
        window.openBlockingLogModal = function (data) {
            if (!data) return;

            // Reset tabs to first tab
            const firstTab = tabBtns[0];
            if (firstTab) firstTab.click();

            // Header badges
            document.getElementById('modal-log-badge-id').textContent = '#' + (data.id || '0');
            document.getElementById('modal-log-badge-type').textContent = data.type_label || data.block_type || 'Security Vector';
            document.getElementById('modal-log-badge-attempts').textContent = (data.attempts_count || 1) + ' Attempt' + ((data.attempts_count || 1) > 1 ? 's' : '');

            // Device tab
            document.getElementById('modal-device-id').textContent = data.device_id || '—';
            document.getElementById('modal-device-type').textContent = data.device_type || 'Desktop';
            document.getElementById('modal-device-os').textContent = data.os || 'Unknown OS';
            document.getElementById('modal-device-browser').textContent = data.browser || 'Unknown Browser';
            document.getElementById('modal-device-screen').textContent = data.screen ? `${data.screen} (${data.color_depth || '24-bit'}, ${data.pixel_ratio || 1}x)` : '—';
            document.getElementById('modal-device-cpu-ram').textContent = `${data.cores ? data.cores + ' Cores' : '—'} • ${data.memory_gb ? data.memory_gb + ' GB RAM' : '—'}`;
            document.getElementById('modal-device-timezone').textContent = `${data.timezone || '—'} (${data.language || 'en'})`;
            document.getElementById('modal-device-gpu').textContent = data.gpu_renderer ? `${data.gpu_vendor ? data.gpu_vendor + ' ~ ' : ''}${data.gpu_renderer}` : '—';
            document.getElementById('modal-device-touch').textContent = data.touch_points !== null && data.touch_points !== undefined ? `${data.touch_points} Max Touch Points` : '—';
            document.getElementById('modal-device-audio').textContent = data.audio_sig || '—';

            // Network tab
            document.getElementById('modal-ip-address').textContent = data.ip_address || '—';
            document.getElementById('modal-network-country').textContent = data.country || 'Bangladesh';
            document.getElementById('modal-network-flag').textContent = data.country_code === 'BD' ? '🇧🇩' : (data.country_code || '🌐');
            document.getElementById('modal-network-city-region').textContent = `${data.city || '—'}, ${data.region || '—'}`;
            document.getElementById('modal-network-isp').textContent = data.isp || '—';
            document.getElementById('modal-network-org').textContent = data.org || '—';
            document.getElementById('modal-user-agent').textContent = data.user_agent || '—';

            // Order tab
            document.getElementById('modal-customer-name').textContent = data.customer_name || 'Guest / Bot';
            document.getElementById('modal-customer-phone').textContent = data.phone || '—';
            document.getElementById('modal-customer-address').textContent = data.customer_address || '—';
            document.getElementById('modal-log-reason').textContent = data.reason || '—';
            document.getElementById('modal-first-attempt').textContent = data.first_attempt_at || '—';
            document.getElementById('modal-last-attempt').textContent = data.last_attempt_at || '—';

            // Cart Items
            const cartContainer = document.getElementById('modal-cart-items-container');
            cartContainer.innerHTML = '';
            if (Array.isArray(data.cart_items) && data.cart_items.length > 0) {
                data.cart_items.forEach(item => {
                    const row = document.createElement('div');
                    row.className = 'py-2 flex items-center justify-between text-xs';
                    row.innerHTML = `
                        <div>
                            <span class="font-medium text-slate-800">${escapeHtml(item.name || 'Product')}</span>
                            <span class="text-slate-400 ml-1">× ${item.quantity || 1}</span>
                        </div>
                        <span class="font-mono text-slate-700">৳${numberFormat((item.price || 0) * (item.quantity || 1))}</span>
                    `;
                    cartContainer.appendChild(row);
                });
                document.getElementById('modal-cart-total').textContent = `Total: ৳${numberFormat(data.total_amount || 0)}`;
            } else {
                cartContainer.innerHTML = '<p class="text-slate-400 py-3 text-center">No product details captured for this attempt.</p>';
                document.getElementById('modal-cart-total').textContent = 'Total: ৳0';
            }

            // Raw JSON tab
            document.getElementById('modal-raw-json').textContent = JSON.stringify(data, null, 2);

            // Footer Action Forms
            // 1. Unblock Device Form
            const unblockDevForm = document.getElementById('modal-unblock-device-form');
            if (data.device_id && data.device_id !== '—') {
                document.getElementById('modal-unblock-device-value').value = data.device_id;
                unblockDevForm.classList.remove('hidden');
            } else {
                unblockDevForm.classList.add('hidden');
            }

            // 2. Unblock Phone Form
            const unblockPhoneForm = document.getElementById('modal-unblock-phone-form');
            if (data.phone && data.phone !== '—') {
                document.getElementById('modal-unblock-phone-value').value = data.phone;
                unblockPhoneForm.classList.remove('hidden');
            } else {
                unblockPhoneForm.classList.add('hidden');
            }

            // 3. Unblock IP Form
            const unblockIpForm = document.getElementById('modal-unblock-ip-form');
            if (data.ip_address && data.ip_address !== '—' && !data.is_local) {
                document.getElementById('modal-unblock-ip-value').value = data.ip_address;
                unblockIpForm.classList.remove('hidden');
            } else {
                unblockIpForm.classList.add('hidden');
            }

            // 4. Delete Log Form
            const deleteForm = document.getElementById('modal-delete-log-form');
            deleteForm.action = `/admin/blocking-logs/${data.id}`;

            // Show modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        };

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function numberFormat(num) {
            return Number(num).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        // Attach click listeners to all rows with [data-log-details]
        document.addEventListener('click', function (e) {
            const row = e.target.closest('[data-log-details]');
            if (!row) return;

            // If user clicked a button, form, link, or input inside row, don't trigger modal
            if (e.target.closest('button, form, a, input, select')) return;

            try {
                const raw = row.getAttribute('data-log-details');
                if (raw) {
                    const data = JSON.parse(raw);
                    window.openBlockingLogModal(data);
                }
            } catch (err) {
                console.error('Failed to parse log details:', err);
            }
        });
    })();
</script>
