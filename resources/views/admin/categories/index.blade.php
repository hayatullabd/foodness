@extends('layouts.admin')

@section('title', 'Categories')
@section('heading', 'Categories')
@section('subheading', 'Manage storefront category structure')

@section('actions')
    <a href="{{ route('admin.categories.create') }}" class="btn-primary">+ Add Category</a>
@endsection

@section('content')
    <form method="GET" class="mb-4 flex justify-end">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search categories..." class="field max-w-xs">
    </form>

    <div class="border border-ink/10 bg-white rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist text-muted border-b border-ink/10">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-xs uppercase tracking-wider">SL</th>
                        <th class="px-4 py-3 font-semibold text-xs uppercase tracking-wider">Image</th>
                        <th class="px-4 py-3 font-semibold text-xs uppercase tracking-wider">Category Name</th>
                        <th class="px-4 py-3 font-semibold text-xs uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 font-semibold text-xs uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-canvas/60 transition-colors" data-category-row="{{ $category->id }}">
                            <td class="px-4 py-3 text-muted text-xs font-mono">{{ $categories->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3">
                                @if ($category->image)
                                    <img src="{{ asset('storage/'.$category->image) }}" alt="{{ $category->name }}" class="h-12 w-12 rounded-lg object-cover border border-ink/10 shadow-2xs" onerror="this.style.opacity='0.3'">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-mist border border-ink/10 text-xs font-semibold text-muted">N/A</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-ink">{{ $category->name }}</span>
                                    @if ($category->is_front)
                                        <span class="inline-flex rounded-full bg-sage/10 px-2 py-0.5 text-[10px] font-bold text-sage uppercase tracking-wider">Front</span>
                                    @endif
                                </div>
                                <div class="text-xs text-muted mt-0.5">{{ $category->products_count }} {{ Str::plural('product', $category->products_count) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    class="category-status-pill inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold transition-all duration-200 cursor-pointer {{ $category->status ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-400/20 hover:bg-slate-200' }}"
                                    data-target-category="{{ $category->id }}"
                                    title="Click to toggle status"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full transition-colors {{ $category->status ? 'bg-emerald-600' : 'bg-slate-400' }}" data-status-dot></span>
                                    <span data-status-text>{{ $category->status ? 'Active' : 'Inactive' }}</span>
                                </button>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="inline-flex items-center gap-3">
                                    {{-- Interactive Switch Toggle --}}
                                    <form method="POST" action="{{ route('admin.categories.toggle', $category) }}" class="inline-flex items-center gap-2 category-toggle-form" data-category-id="{{ $category->id }}">
                                        @csrf
                                        <button
                                            type="button"
                                            role="switch"
                                            aria-checked="{{ $category->status ? 'true' : 'false' }}"
                                            class="category-toggle-btn relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-sage/40 {{ $category->status ? 'bg-sage' : 'bg-slate-300' }}"
                                            title="{{ $category->status ? 'Click to Deactivate' : 'Click to Activate' }}"
                                            data-category-id="{{ $category->id }}"
                                            data-toggle-url="{{ route('admin.categories.toggle', $category) }}"
                                            data-category-name="{{ $category->name }}"
                                        >
                                            <span class="sr-only">Toggle {{ $category->name }} status</span>
                                            <span
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ $category->status ? 'translate-x-5' : 'translate-x-0' }}"
                                                data-toggle-knob
                                            ></span>
                                        </button>
                                        <span class="text-xs font-semibold text-muted hidden sm:inline select-none" data-toggle-label>
                                            {{ $category->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </form>

                                    <div class="h-4 w-px bg-ink/15"></div>

                                    {{-- Edit Link --}}
                                    <a
                                        href="{{ route('admin.categories.edit', $category) }}"
                                        class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-semibold text-sage hover:bg-sage/10 transition"
                                        title="Edit Category"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                        <span>Edit</span>
                                    </a>

                                    {{-- Delete Button --}}
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Delete category: {{ $category->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-semibold text-accent hover:bg-accent/10 transition cursor-pointer"
                                            title="Delete Category"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-muted">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between border-t border-ink/10 px-4 py-3 text-xs text-muted bg-canvas/30">
            <div>
                @if ($categories->total())
                    Showing {{ $categories->firstItem() }} to {{ $categories->lastItem() }} of {{ $categories->total() }} entries
                @endif
            </div>
            <div>{{ $categories->links() }}</div>
        </div>
    </div>

    {{-- Interactive Floating Toast Container --}}
    <div id="category-toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <script>
    (() => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function showCategoryToast(message, isSuccess = true) {
            const container = document.getElementById('category-toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `pointer-events-auto flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-semibold shadow-lg backdrop-blur-md transition-all duration-300 transform translate-y-2 opacity-0 ${
                isSuccess ? 'bg-[#0B733E] text-white shadow-[#0B733E]/20' : 'bg-accent text-white shadow-accent/20'
            }`;
            toast.innerHTML = `
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    ${isSuccess 
                        ? '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />' 
                        : '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />'
                    }
                </svg>
                <span>${message}</span>
            `;

            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            });

            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 2500);
        }

        function setCategoryUIState(categoryId, isActive) {
            const row = document.querySelector(`[data-category-row="${categoryId}"]`);
            if (!row) return;

            const btn = row.querySelector('.category-toggle-btn');
            const knob = row.querySelector('[data-toggle-knob]');
            const label = row.querySelector('[data-toggle-label]');
            const pill = row.querySelector('.category-status-pill');
            const statusDot = row.querySelector('[data-status-dot]');
            const statusText = row.querySelector('[data-status-text]');

            // Switch button update
            if (btn) {
                btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
                btn.title = isActive ? 'Click to Deactivate' : 'Click to Activate';
                if (isActive) {
                    btn.classList.add('bg-sage');
                    btn.classList.remove('bg-slate-300');
                } else {
                    btn.classList.remove('bg-sage');
                    btn.classList.add('bg-slate-300');
                }
            }

            // Knob slide animation
            if (knob) {
                if (isActive) {
                    knob.classList.add('translate-x-5');
                    knob.classList.remove('translate-x-0');
                } else {
                    knob.classList.remove('translate-x-5');
                    knob.classList.add('translate-x-0');
                }
            }

            // Toggle text label
            if (label) {
                label.textContent = isActive ? 'Active' : 'Inactive';
            }

            // Status pill update
            if (pill) {
                if (isActive) {
                    pill.className = 'category-status-pill inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold transition-all duration-200 cursor-pointer bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100';
                } else {
                    pill.className = 'category-status-pill inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold transition-all duration-200 cursor-pointer bg-slate-100 text-slate-500 ring-1 ring-slate-400/20 hover:bg-slate-200';
                }
            }

            if (statusDot) {
                statusDot.className = `h-1.5 w-1.5 rounded-full transition-colors ${isActive ? 'bg-emerald-600' : 'bg-slate-400'}`;
            }

            if (statusText) {
                statusText.textContent = isActive ? 'Active' : 'Inactive';
            }
        }

        async function triggerCategoryToggle(categoryId, btn) {
            const url = btn.getAttribute('data-toggle-url');
            const name = btn.getAttribute('data-category-name') || 'Category';
            const isCurrentlyActive = btn.getAttribute('aria-checked') === 'true';
            const newActiveState = !isCurrentlyActive;

            // Optimistic UI update
            setCategoryUIState(categoryId, newActiveState);
            btn.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({}),
                });

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const data = await response.json();
                const finalState = data.status ?? newActiveState;
                setCategoryUIState(categoryId, finalState);

                const msg = data.message || `${name} status updated to ${finalState ? 'Active' : 'Inactive'}.`;
                showCategoryToast(msg, true);
            } catch (err) {
                console.error('Category toggle error:', err);
                // Rollback on failure
                setCategoryUIState(categoryId, isCurrentlyActive);
                showCategoryToast(`Failed to update ${name}. Please try again.`, false);
            } finally {
                btn.disabled = false;
            }
        }

        // Attach to toggle buttons
        document.querySelectorAll('.category-toggle-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const categoryId = btn.getAttribute('data-category-id');
                if (categoryId) {
                    triggerCategoryToggle(categoryId, btn);
                }
            });
        });

        // Also make the Status Pill clickable to toggle
        document.querySelectorAll('.category-status-pill').forEach((pill) => {
            pill.addEventListener('click', (e) => {
                e.preventDefault();
                const categoryId = pill.getAttribute('data-target-category');
                const row = document.querySelector(`[data-category-row="${categoryId}"]`);
                const btn = row?.querySelector('.category-toggle-btn');
                if (btn && categoryId) {
                    triggerCategoryToggle(categoryId, btn);
                }
            });
        });
    })();
    </script>
@endsection
