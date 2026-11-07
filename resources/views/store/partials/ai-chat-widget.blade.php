@php
    $geminiService = app(\App\Services\GeminiService::class);
    $assistantEnabled = $geminiService->isAssistantEnabled();

    $rawSupportWa = preg_replace('/[^0-9]/', '', $store['floating_whatsapp_number'] ?? ($store['store_phone'] ?? ''));
    $agentWaNumber = str_starts_with($rawSupportWa, '0') ? '88'.$rawSupportWa : ($rawSupportWa ?: '');
    $agentWaMessage = urlencode('Hello, I am chatting from the website and need help from a live agent.');
    $agentCallNumber = preg_replace('/[^0-9+]/', '', $store['floating_call_number'] ?? ($store['store_phone'] ?? ''));
@endphp

@if ($assistantEnabled)
<div id="foodness-ai-widget" class="relative z-50">
    {{-- Floating Trigger Button --}}
    <button
        id="ai-chat-toggle-btn"
        type="button"
        onclick="window.foodnessAiChat.toggle()"
        class="fixed bottom-20 md:bottom-6 left-4 sm:left-6 z-40 flex items-center gap-2.5 rounded-full bg-[#013F1E] px-4 py-3 text-white shadow-2xl transition-all duration-300 hover:scale-105 hover:bg-[#012E16] focus:outline-none focus:ring-2 focus:ring-[#013F1E] focus:ring-offset-2"
        aria-label="Open AI Shopping Assistant"
    >
        <span class="relative flex h-3 w-3">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#A5000B] opacity-75"></span>
            <span class="relative inline-flex h-3 w-3 rounded-full bg-[#A5000B]"></span>
        </span>
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
        <span class="text-xs font-semibold tracking-wide hidden sm:inline">AI Assistant</span>
    </button>

    {{-- Chat Window --}}
    <div
        id="ai-chat-panel"
        class="fixed bottom-20 md:bottom-6 left-4 sm:left-6 z-50 hidden flex-col w-[calc(100vw-2rem)] sm:w-[390px] h-[520px] max-h-[82vh] rounded-2xl bg-white shadow-2xl border border-slate-200/80 overflow-hidden transition-all duration-300 origin-bottom-left"
        role="dialog"
        aria-modal="true"
        aria-label="AI Shopping Concierge"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between bg-[#013F1E] px-4 py-3.5 text-white shadow-sm">
            <div class="flex items-center gap-3">
                <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full overflow-hidden {{ $chatAvatar ? 'bg-white p-0.5' : 'bg-white/20 backdrop-blur-sm' }} ring-1 ring-white/30 shadow-xs">
                    @if ($chatAvatar)
                        <img src="{{ $chatAvatar }}" alt="AI Assistant" class="h-full w-full rounded-full object-cover">
                    @else
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    @endif
                    <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-[#013F1E]"></span>
                </div>
                <div>
                    <h3 class="text-sm font-bold leading-tight">{{ $store['store_name'] ?? config('app.name', 'Store') }} AI</h3>
                    <p class="text-[11px] text-[#E8F4ED] font-medium">শপিং অ্যাসিস্ট্যান্ট • Online</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                @if ($agentWaNumber)
                    <a
                        href="https://wa.me/{{ $agentWaNumber }}?text={{ $agentWaMessage }}"
                        target="_blank"
                        rel="noopener"
                        title="Talk to Human Agent on WhatsApp"
                        class="flex items-center gap-1 rounded-full bg-emerald-500/20 hover:bg-emerald-500 px-2.5 py-1 text-[11px] font-semibold text-white ring-1 ring-emerald-400/40 transition hover:scale-105"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Live Agent</span>
                    </a>
                @endif
                <button
                    type="button"
                    onclick="window.foodnessAiChat.clearChat()"
                    title="Clear Conversation"
                    class="rounded-lg p-1.5 text-white/80 hover:bg-white/10 hover:text-white transition"
                    aria-label="Clear chat history"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
                <button
                    type="button"
                    onclick="window.foodnessAiChat.toggle()"
                    class="rounded-lg p-1.5 text-white/80 hover:bg-white/10 hover:text-white transition"
                    aria-label="Close chat"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages Container --}}
        <div id="ai-chat-messages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-[#FAFBF9] text-xs sm:text-sm">
            {{-- Initial Welcome message --}}
            <div class="flex gap-2.5 items-start">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full overflow-hidden {{ $chatAvatar ? 'bg-white border border-[#013F1E]/15 p-0.5' : 'bg-[#E8F4ED] text-[#013F1E] font-bold text-xs' }} shadow-xs">
                    @if ($chatAvatar)
                        <img src="{{ $chatAvatar }}" alt="AI" class="h-full w-full rounded-full object-cover">
                    @else
                        AI
                    @endif
                </div>
                <div class="rounded-2xl rounded-tl-none bg-white p-3.5 shadow-sm border border-slate-100 text-slate-800 leading-relaxed max-w-[85%]">
                    <p class="font-medium text-[#013F1E] mb-1">আসসালামু আলাইকুম! 👋</p>
                    <p>আমি <strong>{{ $store['store_name'] ?? config('app.name', 'আমাদের স্টোর') }}</strong>-এর স্মার্ট এআই সহকারী। সাইজ, কালার, ডেলিভারি বা পছন্দের পণ্য সংক্রান্ত যেকোনো প্রশ্ন করতে পারেন।</p>
                </div>
            </div>

            {{-- Suggestion Chips --}}
            <div id="ai-suggestion-chips" class="pt-1 flex flex-wrap gap-1.5">
                <button
                    type="button"
                    onclick="window.foodnessAiChat.sendSuggested('জনপ্রিয় কালেকশন ও ক্যাটাগরি কি কি আছে?')"
                    class="rounded-full bg-white px-3 py-1 text-[11px] font-medium text-[#013F1E] border border-[#013F1E]/20 shadow-xs hover:bg-[#E8F4ED] transition"
                >
                    🛍️ জনপ্রিয় কালেকশন
                </button>
                <button
                    type="button"
                    onclick="window.foodnessAiChat.sendSuggested('ডেলিভারি চার্জ কত এবং কতদিন সময় লাগে?')"
                    class="rounded-full bg-white px-3 py-1 text-[11px] font-medium text-[#013F1E] border border-[#013F1E]/20 shadow-xs hover:bg-[#E8F4ED] transition"
                >
                    🚚 ডেলিভারি চার্জ
                </button>
                <button
                    type="button"
                    onclick="window.foodnessAiChat.sendSuggested('ক্যাশ অন ডেলিভারি এবং রিটার্ন পলিসি কি?')"
                    class="rounded-full bg-white px-3 py-1 text-[11px] font-medium text-[#013F1E] border border-[#013F1E]/20 shadow-xs hover:bg-[#E8F4ED] transition"
                >
                    💳 পেমেন্ট ও রিটার্ন
                </button>
                @if ($agentWaNumber)
                    <a
                        href="https://wa.me/{{ $agentWaNumber }}?text={{ $agentWaMessage }}"
                        target="_blank"
                        rel="noopener"
                        class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-800 border border-emerald-300 shadow-xs hover:bg-emerald-100 transition flex items-center gap-1"
                    >
                        <span>💬 লাইভ এজেন্টের সাথে কথা বলুন</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Loading / Typing Indicator --}}
        <div id="ai-chat-typing" class="hidden px-4 py-2 bg-[#FAFBF9] border-t border-slate-100">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <div class="flex space-x-1">
                    <span class="h-2 w-2 rounded-full bg-[#013F1E] animate-bounce"></span>
                    <span class="h-2 w-2 rounded-full bg-[#013F1E] animate-bounce [animation-delay:0.2s]"></span>
                    <span class="h-2 w-2 rounded-full bg-[#013F1E] animate-bounce [animation-delay:0.4s]"></span>
                </div>
                <span>এআই টাইপ করছে...</span>
            </div>
        </div>

        {{-- Input Form --}}
        <form id="ai-chat-form" onsubmit="window.foodnessAiChat.submit(event)" class="border-t border-slate-200 bg-white p-3">
            <div class="flex items-center gap-2">
                <input
                    id="ai-chat-input"
                    type="text"
                    maxlength="500"
                    placeholder="আপনার প্রশ্নটি লিখুন..."
                    autocomplete="off"
                    class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:border-[#013F1E] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#013F1E]"
                />
                <button
                    id="ai-chat-send-btn"
                    type="submit"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#013F1E] text-white transition hover:bg-[#012E16] disabled:opacity-50"
                    aria-label="Send message"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </div>
            <div class="mt-1.5 flex items-center justify-between px-1 text-[10px] text-slate-400">
                <span>Google Gemini AI দ্বারা চালিত</span>
                <span>Press Enter ↵</span>
            </div>
        </form>
    </div>
</div>

<script>
window.foodnessAiChat = (function() {
    var chatEndpoint = '{{ route("ai-assistant.chat") }}';
    var csrfToken = '{{ csrf_token() }}';
    var storageKey = 'foodness_ai_chat_history';
    var chatAvatarUrl = @json($chatAvatar);
    var agentWaNumber = @json($agentWaNumber);
    var agentWaMessage = @json($agentWaMessage);
    var conversation = [];

    // Load from sessionStorage if available
    try {
        var saved = sessionStorage.getItem(storageKey);
        if (saved) {
            conversation = JSON.parse(saved);
        }
    } catch (e) {
        conversation = [];
    }

    function saveHistory() {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(conversation.slice(-10)));
        } catch (e) {}
    }

    function scrollToBottom() {
        var container = document.getElementById('ai-chat-messages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatMessage(text) {
        var escaped = escapeHtml(text);
        // Replace bold **text**
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Replace line breaks
        escaped = escaped.replace(/\n/g, '<br>');
        return escaped;
    }

    function appendMessageUI(role, text) {
        var container = document.getElementById('ai-chat-messages');
        if (!container) return;

        var row = document.createElement('div');
        if (role === 'user') {
            row.className = 'flex justify-end';
            row.innerHTML = '<div class="rounded-2xl rounded-tr-none bg-[#013F1E] px-3.5 py-2.5 text-white max-w-[85%] leading-relaxed shadow-sm break-words">' +
                formatMessage(text) +
            '</div>';
        } else {
            row.className = 'flex gap-2.5 items-start';
            var avatarMarkup = chatAvatarUrl
                ? '<div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full overflow-hidden bg-white border border-[#013F1E]/15 p-0.5 shadow-xs"><img src="' + escapeHtml(chatAvatarUrl) + '" alt="AI" class="h-full w-full rounded-full object-cover"></div>'
                : '<div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#E8F4ED] text-[#013F1E] font-bold text-xs">AI</div>';
            var replyHtml = formatMessage(text);
            if (agentWaNumber && (text.includes('WhatsApp') || text.includes('হোয়াটসঅ্যাপ') || text.includes('লাইভ সাপোর্ট') || text.includes('হোয়াটসঅ্যাপ') || text.includes('live support') || text.includes('live agent') || text.includes('এজেন্ট'))) {
                replyHtml += '<div class="mt-2.5 pt-2 border-t border-slate-100"><a href="https://wa.me/' + agentWaNumber + '?text=' + agentWaMessage + '" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-[#25D366] px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-[#20ba5a] transition"><span>💬 WhatsApp-এ কথা বলুন</span></a></div>';
            }
            row.innerHTML = avatarMarkup +
                '<div class="rounded-2xl rounded-tl-none bg-white p-3.5 shadow-sm border border-slate-100 text-slate-800 leading-relaxed max-w-[85%] break-words">' +
                replyHtml +
            '</div>';
        }

        container.appendChild(row);
        scrollToBottom();
    }

    function restoreUI() {
        if (!conversation || !conversation.length) return;
        conversation.forEach(function(item) {
            appendMessageUI(item.role, item.text);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        restoreUI();
    });

    return {
        toggle: function() {
            var panel = document.getElementById('ai-chat-panel');
            var btn = document.getElementById('ai-chat-toggle-btn');
            if (!panel) return;

            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                panel.classList.add('flex');
                if (btn) btn.classList.add('hidden');
                var input = document.getElementById('ai-chat-input');
                if (input) {
                    setTimeout(function() { input.focus(); }, 150);
                }
                scrollToBottom();
            } else {
                panel.classList.add('hidden');
                panel.classList.remove('flex');
                if (btn) btn.classList.remove('hidden');
            }
        },

        clearChat: function() {
            conversation = [];
            try { sessionStorage.removeItem(storageKey); } catch (e) {}
            var container = document.getElementById('ai-chat-messages');
            if (container) {
                // keep only the initial greeting and chips
                var rows = container.querySelectorAll('.flex.justify-end, .flex.gap-2.5.items-start:not(:first-child)');
                rows.forEach(function(r) { r.remove(); });
            }
        },

        sendSuggested: function(text) {
            var input = document.getElementById('ai-chat-input');
            if (input) {
                input.value = text;
                this.submit(new Event('submit'));
            }
        },

        submit: function(event) {
            if (event && event.preventDefault) event.preventDefault();

            var input = document.getElementById('ai-chat-input');
            var sendBtn = document.getElementById('ai-chat-send-btn');
            var typing = document.getElementById('ai-chat-typing');
            if (!input) return;

            var message = input.value.trim();
            if (!message) return;

            // Clear input & disable controls
            input.value = '';
            input.disabled = true;
            if (sendBtn) sendBtn.disabled = true;

            // Hide suggestion chips once user interacts
            var chips = document.getElementById('ai-suggestion-chips');
            if (chips) chips.style.display = 'none';

            // Append user message to UI & history
            appendMessageUI('user', message);
            conversation.push({ role: 'user', text: message });
            saveHistory();

            if (typing) typing.classList.remove('hidden');
            scrollToBottom();

            fetch(chatEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    message: message,
                    history: conversation.slice(-8)
                })
            })
            .then(function(res) {
                return res.json().then(function(data) {
                    return { status: res.status, data: data };
                });
            })
            .then(function(result) {
                if (typing) typing.classList.add('hidden');
                input.disabled = false;
                if (sendBtn) sendBtn.disabled = false;
                input.focus();

                var reply = (result.data && result.data.reply)
                    ? result.data.reply
                    : 'দুঃখিত, এই মুহূর্তে উত্তর দেওয়া সম্ভব হচ্ছে না। অনুগ্রহ করে কিছুক্ষণ পর আবার চেষ্টা করুন।';

                appendMessageUI('model', reply);
                conversation.push({ role: 'model', text: reply });
                saveHistory();
            })
            .catch(function(err) {
                if (typing) typing.classList.add('hidden');
                input.disabled = false;
                if (sendBtn) sendBtn.disabled = false;
                input.focus();

                var fallback = 'ইন্টারনেট সংযোগ বা সার্ভার ত্রুটি হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।';
                appendMessageUI('model', fallback);
            });
        }
    };
})();
</script>
@endif
