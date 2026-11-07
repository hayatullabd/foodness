@extends('layouts.admin')

@section('title', 'Media Library — Foodness')
@section('heading', 'Media Library')
@section('subheading', 'Browse, upload, and control all website media and files in one compact gallery.')

@section('actions')
    <button type="button" onclick="toggleUploadDrawer()" class="admin-btn-primary inline-flex items-center gap-1.5 cursor-pointer text-xs font-semibold px-3 py-1.5">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span>Add Media File</span>
    </button>
@endsection

@section('content')
<div class="space-y-3">
    {{-- Alerts --}}
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-medium text-emerald-800 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-medium text-rose-800 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 font-bold">&times;</button>
        </div>
    @endif

    {{-- Upload Drawer (Collapsible) --}}
    <div id="upload-drawer" class="rounded-xl border-2 border-dashed border-sage/40 bg-white p-4 shadow-sm hidden transition-all">
        <div class="flex items-center justify-between pb-3 border-b border-ink/8">
            <div>
                <h3 class="text-sm font-bold text-ink">Upload Media Files</h3>
                <p class="text-[11px] text-muted">Upload product photos, category images, banners, or files to public storage.</p>
            </div>
            <button type="button" onclick="toggleUploadDrawer()" class="rounded-lg p-1 text-muted hover:bg-mist hover:text-ink">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data" class="mt-3 space-y-3" id="media-upload-form">
            @csrf
            <div class="grid sm:grid-cols-3 gap-2.5">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Target Folder</label>
                    <select name="folder" id="upload-folder-select" onchange="toggleCustomFolder(this.value)" class="w-full rounded-lg border border-ink/15 bg-white px-2.5 py-1.5 text-xs font-medium text-ink focus:border-sage focus:outline-none">
                        <option value="media">media (General Media)</option>
                        <option value="products">products (Product Photos)</option>
                        <option value="categories">categories (Category Images)</option>
                        <option value="brands">brands (Brand Logos)</option>
                        <option value="customization">customization (Hero & Banners)</option>
                        <option value="stories">stories (Stories Media)</option>
                        <option value="settings">settings (Store Settings)</option>
                        <option value="custom">+ Create New Folder</option>
                    </select>
                </div>

                <div id="custom-folder-box" class="hidden">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">New Folder Name</label>
                    <input type="text" name="custom_folder" placeholder="e.g. banners" class="w-full rounded-lg border border-ink/15 bg-white px-2.5 py-1.5 text-xs text-ink focus:border-sage focus:outline-none">
                </div>
            </div>

            <div class="relative rounded-lg border-2 border-dashed border-slate-300 bg-slate-50/50 p-5 text-center hover:border-sage hover:bg-slate-50 transition cursor-pointer" onclick="document.getElementById('media-files-input').click()">
                <input type="file" name="files[]" id="media-files-input" multiple class="hidden" onchange="handleFileSelect(this)">
                <svg class="mx-auto h-8 w-8 text-muted/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                </svg>
                <p class="mt-1.5 text-xs font-semibold text-ink">Click to browse or drag & drop files here</p>
                <p class="text-[11px] text-muted">Supports JPG, PNG, WebP, GIF, SVG, MP4, PDF, etc. (Up to 50MB per file)</p>
                <div id="selected-files-preview" class="mt-2 hidden text-xs font-medium text-sage text-left bg-white p-2.5 rounded-lg border border-sage/20"></div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" onclick="toggleUploadDrawer()" class="admin-btn-secondary text-xs px-3 py-1.5">Cancel</button>
                <button type="submit" class="admin-btn-primary text-xs px-3.5 py-1.5 font-semibold">Start Upload</button>
            </div>
        </form>
    </div>

    {{-- Compact WordPress-Style Media Toolbar --}}
    <div class="rounded-lg border border-slate-200 bg-white p-2.5 shadow-xs">
        <form method="GET" action="{{ route('admin.media.index') }}" class="flex flex-wrap items-center justify-between gap-2.5" id="filter-form">
            <input type="hidden" name="view" value="{{ $viewMode }}">

            {{-- Left Controls: View Switchers, Type Filter, Folder Filter, Bulk Select --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- View Switcher Icons --}}
                <div class="inline-flex items-center border border-slate-300 rounded bg-white overflow-hidden shadow-2xs">
                    <a
                        href="{{ route('admin.media.index', array_merge(request()->query(), ['view' => 'table'])) }}"
                        class="p-1.5 text-slate-600 hover:bg-slate-100 transition {{ $viewMode === 'table' ? 'bg-slate-100 text-ink font-bold' : '' }}"
                        title="List View"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </a>
                    <a
                        href="{{ route('admin.media.index', array_merge(request()->query(), ['view' => 'grid'])) }}"
                        class="p-1.5 text-slate-600 hover:bg-slate-100 transition border-l border-slate-300 {{ $viewMode === 'grid' ? 'bg-slate-100 text-ink font-bold' : '' }}"
                        title="Grid View"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </a>
                </div>

                {{-- Type Dropdown --}}
                <select name="type" onchange="document.getElementById('filter-form').submit()" class="rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 font-normal focus:border-blue-500 focus:outline-none">
                    <option value="" @selected(empty($currentType))>All media items</option>
                    <option value="image" @selected($currentType === 'image')>Images</option>
                    <option value="video" @selected($currentType === 'video')>Video</option>
                    <option value="document" @selected($currentType === 'document')>Documents</option>
                    <option value="other" @selected($currentType === 'other')>Other</option>
                </select>

                {{-- Folder Dropdown --}}
                <select name="folder" onchange="document.getElementById('filter-form').submit()" class="rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 font-normal focus:border-blue-500 focus:outline-none">
                    <option value="all" @selected(empty($currentFolder) || $currentFolder === 'all')>All folders</option>
                    @foreach ($folders as $fldr)
                        <option value="{{ $fldr }}" @selected($currentFolder === $fldr)>{{ $fldr }}</option>
                    @endforeach
                </select>

                {{-- Sort Dropdown --}}
                <select name="sort" onchange="document.getElementById('filter-form').submit()" class="rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 font-normal focus:border-blue-500 focus:outline-none">
                    <option value="date_desc" @selected($currentSort === 'date_desc')>Newest</option>
                    <option value="date_asc" @selected($currentSort === 'date_asc')>Oldest</option>
                    <option value="name_asc" @selected($currentSort === 'name_asc')>A → Z</option>
                    <option value="name_desc" @selected($currentSort === 'name_desc')>Z → A</option>
                    <option value="size_desc" @selected($currentSort === 'size_desc')>Largest</option>
                </select>

                {{-- Bulk Select Toggle Button --}}
                <button
                    type="button"
                    id="bulk-toggle-btn"
                    onclick="toggleBulkSelectMode()"
                    class="rounded border border-blue-600 bg-white px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50 transition"
                >
                    Bulk select
                </button>

                {{-- Bulk Delete Action (Appears when items are checked) --}}
                <div id="bulk-action-wrapper" class="hidden items-center gap-2">
                    <span class="text-xs font-semibold text-rose-700" id="bulk-count-badge">0 selected</span>
                    <button
                        type="button"
                        onclick="submitBulkDelete()"
                        class="rounded bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-rose-700 transition"
                    >
                        Delete Selected
                    </button>
                    <button
                        type="button"
                        onclick="selectAllCurrentPage()"
                        class="text-xs text-slate-600 hover:underline"
                    >
                        Select all
                    </button>
                </div>
            </div>

            {{-- Right Controls: Search Media --}}
            <div class="flex items-center gap-2">
                <label for="search-media-input" class="text-xs text-slate-600 font-normal">Search media</label>
                <div class="relative">
                    <input
                        type="text"
                        id="search-media-input"
                        name="q"
                        value="{{ $q }}"
                        placeholder=""
                        class="w-48 sm:w-60 rounded border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-800 focus:border-blue-500 focus:outline-none"
                    >
                    @if ($q)
                        <a href="{{ route('admin.media.index', array_filter(['folder' => $currentFolder, 'type' => $currentType, 'sort' => $currentSort, 'view' => $viewMode])) }}" class="absolute right-2 top-1 text-xs text-slate-400 hover:text-slate-700 font-bold">&times;</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Main Media Gallery --}}
    @if ($files->isEmpty())
        <div class="rounded-lg border border-slate-200 bg-white p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
            <p class="mt-2 text-xs font-medium text-slate-500">No media items found.</p>
            @if ($q || $currentFolder || $currentType)
                <a href="{{ route('admin.media.index') }}" class="mt-2 inline-block text-xs text-blue-600 hover:underline">Reset filters</a>
            @endif
        </div>
    @else
        @if ($viewMode === 'grid')
            {{-- Compact Grid View (12 items per row) --}}
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-12 gap-1.5 sm:gap-2" id="media-grid-container">
                @foreach ($files as $file)
                    <div
                        class="media-card group relative aspect-square rounded border border-slate-200 bg-white hover:border-[#0b733e] hover:ring-1 hover:ring-[#0b733e] transition flex flex-col justify-between overflow-hidden cursor-pointer select-none"
                        data-path="{{ $file['path'] }}"
                        onclick='handleCardClick(this, event, @json($file))'
                    >
                        {{-- Checkbox (Always visible in bulk select mode; or on hover) --}}
                        <div class="media-checkbox-wrapper absolute top-1 left-1 z-10 hidden">
                            <input
                                type="checkbox"
                                value="{{ $file['path'] }}"
                                class="media-item-checkbox h-3.5 w-3.5 rounded border-slate-300 text-blue-600 focus:ring-0 cursor-pointer"
                                onclick="event.stopPropagation()"
                                onchange="onCheckboxToggled()"
                            >
                        </div>

                        {{-- Quick Copy URL Icon on Hover --}}
                        <button
                            type="button"
                            onclick='event.stopPropagation(); copyToClipboard("{{ $file['url'] }}", this)'
                            class="absolute top-1 right-1 z-10 hidden group-hover:flex rounded bg-black/60 p-1 text-white hover:bg-black transition shadow-xs"
                            title="Copy URL"
                        >
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>

                        {{-- Upper Thumbnail Area --}}
                        <div class="flex-1 w-full bg-slate-50 flex items-center justify-center overflow-hidden relative">
                            @if ($file['is_image'])
                                <img
                                    src="{{ $file['url'] }}"
                                    alt="{{ $file['name'] }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover"
                                >
                            @elseif ($file['is_video'])
                                <div class="flex flex-col items-center justify-center text-slate-500">
                                    <div class="rounded-full bg-black/50 p-1.5 text-white">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center text-slate-400 p-2">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    <span class="text-[8px] font-bold uppercase text-slate-500">{{ $file['extension'] }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Bottom Caption Strip (Title or Filename) --}}
                        <div class="px-1 py-0.5 bg-white border-t border-slate-100 text-center w-full">
                            <span class="block truncate text-[10px] leading-tight text-slate-700 font-medium" title="{{ $file['title'] ?? $file['name'] }}">
                                {{ !empty($file['title']) ? $file['title'] : $file['name'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Compact Table / List View --}}
            <div class="rounded-lg border border-slate-200 bg-white overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 text-[11px] font-semibold">
                            <tr>
                                <th class="w-8 px-3 py-2 text-center">
                                    <input type="checkbox" id="table-select-all" onchange="toggleTableSelectAll(this)" class="rounded border-slate-300 text-blue-600 focus:ring-0">
                                </th>
                                <th class="w-12 px-2 py-2">File</th>
                                <th class="px-3 py-2">Title</th>
                                <th class="px-3 py-2">Folder</th>
                                <th class="px-3 py-2">Size</th>
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($files as $file)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-3 py-1.5 text-center">
                                        <input
                                            type="checkbox"
                                            value="{{ $file['path'] }}"
                                            class="media-item-checkbox h-3.5 w-3.5 rounded border-slate-300 text-blue-600 focus:ring-0"
                                            onchange="onCheckboxToggled()"
                                        >
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <div
                                            class="h-10 w-10 rounded bg-slate-100 overflow-hidden border border-slate-200 flex items-center justify-center cursor-pointer"
                                            onclick='openMediaModal(@json($file))'
                                        >
                                            @if ($file['is_image'])
                                                <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" class="h-full w-full object-cover">
                                            @elseif ($file['is_video'])
                                                <svg class="h-5 w-5 text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                            @else
                                                <span class="text-[9px] font-bold text-slate-500 uppercase">{{ $file['extension'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-1.5">
                                        <button
                                            type="button"
                                            onclick='openMediaModal(@json($file))'
                                            class="font-medium text-slate-800 hover:text-blue-600 transition block truncate max-w-xs text-left"
                                            title="{{ !empty($file['title']) ? $file['title'] : $file['name'] }}"
                                        >
                                            {{ !empty($file['title']) ? $file['title'] : $file['name'] }}
                                        </button>
                                        <span class="text-[10px] text-slate-400 block font-mono">{{ $file['name'] }}</span>
                                    </td>
                                    <td class="px-3 py-1.5 text-slate-600">
                                        {{ $file['folder'] }}
                                    </td>
                                    <td class="px-3 py-1.5 text-slate-600">
                                        {{ $file['size_formatted'] }}
                                        @if ($file['dimensions'])
                                            <span class="text-[10px] text-slate-400 block">{{ $file['dimensions'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-1.5 text-slate-500 text-[11px]">
                                        {{ $file['last_modified_formatted'] }}
                                    </td>
                                    <td class="px-3 py-1.5 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <button
                                                type="button"
                                                onclick='copyToClipboard("{{ $file['url'] }}", this)'
                                                class="rounded p-1 text-slate-500 hover:text-blue-600 hover:bg-slate-100"
                                                title="Copy URL"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            </button>
                                            <a
                                                href="{{ route('admin.media.download', ['path' => $file['path']]) }}"
                                                class="rounded p-1 text-slate-500 hover:text-blue-600 hover:bg-slate-100"
                                                title="Download"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            </a>
                                            <form action="{{ route('admin.media.destroy') }}" method="POST" class="inline" onsubmit="return confirm('Permanently delete this file?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="path" value="{{ $file['path'] }}">
                                                <button type="submit" class="rounded p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50" title="Delete">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Pagination & Count footer --}}
        <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-xs text-slate-500">
            <span>Showing {{ $files->firstItem() }} to {{ $files->lastItem() }} of {{ $files->total() }} media files</span>
            <div>
                {{ $files->links() }}
            </div>
        </div>
    @endif
</div>

{{-- Hidden Form for Bulk Deletion --}}
<form id="bulk-delete-hidden-form" action="{{ route('admin.media.bulk-destroy') }}" method="POST" class="hidden">
    @csrf
    <div id="bulk-delete-inputs"></div>
</form>

{{-- Detail & Preview Modal (Attachment Details) --}}
<div id="media-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
    <div class="relative w-full max-w-3xl rounded-xl bg-white shadow-2xl overflow-hidden border border-slate-200 transition-all my-auto">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2.5 bg-slate-50">
            <div class="flex items-center gap-2">
                <span class="rounded bg-sage/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-sage">Media Details</span>
                <h3 class="text-xs font-bold text-slate-700 truncate pr-4" id="modal-header-title">Attachment Details</h3>
            </div>
            <button type="button" onclick="closeMediaModal()" class="rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-800 leading-none">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Modal Body: Preview on left, editable form & metadata on right --}}
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-0 max-h-[80vh] overflow-y-auto">
            {{-- Preview Box (Left) --}}
            <div class="sm:col-span-5 bg-slate-900 flex flex-col items-center justify-center p-4">
                <div class="aspect-square w-full max-w-[240px] flex items-center justify-center overflow-hidden rounded bg-slate-950/70" id="modal-preview-box">
                    {{-- Dynamically populated: image/video/doc --}}
                </div>

                {{-- Quick Meta under preview --}}
                <div class="w-full max-w-[240px] mt-3 space-y-1 text-[11px] text-slate-400 border-t border-slate-800 pt-2.5">
                    <div class="flex justify-between">
                        <span>Folder:</span>
                        <span class="text-slate-200 font-medium" id="modal-folder"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Size:</span>
                        <span class="text-slate-200 font-medium" id="modal-size"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Dimensions:</span>
                        <span class="text-slate-200 font-medium font-mono" id="modal-dimensions"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Uploaded:</span>
                        <span class="text-slate-200 font-medium" id="modal-date"></span>
                    </div>
                </div>
            </div>

            {{-- Editable Form & Fields (Right) --}}
            <div class="sm:col-span-7 p-4 flex flex-col justify-between bg-white text-xs">
                <form id="media-edit-form" onsubmit="handleMediaFormSubmit(event)" class="space-y-3">
                    <input type="hidden" id="edit-path" name="path">

                    {{-- Title --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1" for="edit-title">
                            Title / Display Name
                        </label>
                        <input
                            type="text"
                            id="edit-title"
                            name="title"
                            placeholder="e.g. Pure Honey Jar"
                            class="field !py-1.5 !text-xs w-full"
                        >
                        <p class="text-[10px] text-slate-400 mt-0.5">Used as label, tooltip and search keyword</p>
                    </div>

                    {{-- Physical File Name (Rename) --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1" for="edit-filename">
                            File Name (Disk File)
                        </label>
                        <div class="flex items-center gap-1.5">
                            <input
                                type="text"
                                id="edit-filename"
                                name="new_name"
                                class="field !py-1.5 !text-xs font-mono w-full"
                            >
                        </div>
                        <p class="text-[10px] text-slate-400 mt-0.5">Renaming updates all links and references automatically</p>
                    </div>

                    {{-- Alt Text & Caption --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1" for="edit-alt">
                                Alt Text
                            </label>
                            <input
                                type="text"
                                id="edit-alt"
                                name="alt_text"
                                placeholder="Descriptive text"
                                class="field !py-1.5 !text-xs w-full"
                            >
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1" for="edit-caption">
                                Caption
                            </label>
                            <input
                                type="text"
                                id="edit-caption"
                                name="caption"
                                placeholder="Optional caption"
                                class="field !py-1.5 !text-xs w-full"
                            >
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1" for="edit-desc">
                            Description
                        </label>
                        <textarea
                            id="edit-desc"
                            name="description"
                            rows="2"
                            placeholder="Detailed description or notes about this file…"
                            class="field !py-1.5 !text-xs w-full resize-y"
                        ></textarea>
                    </div>

                    {{-- File URL with Copy --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                            File URL
                        </label>
                        <div class="flex items-center gap-1">
                            <input
                                type="text"
                                id="modal-url-input"
                                readonly
                                class="field !py-1 !text-[11px] font-mono text-slate-600 bg-slate-50 w-full select-all"
                            >
                            <button
                                type="button"
                                onclick="copyModalUrl(this)"
                                class="admin-btn-secondary !py-1 !px-2.5 !text-[11px] shrink-0 cursor-pointer"
                            >
                                Copy
                            </button>
                        </div>
                    </div>

                    {{-- Form Feedback Message --}}
                    <div id="edit-feedback-msg" class="hidden rounded-lg p-2 text-xs font-medium"></div>

                    {{-- Save Button --}}
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                        <button
                            type="submit"
                            id="save-media-btn"
                            class="admin-btn bg-emerald-600 text-white hover:bg-emerald-700 !py-1.5 !px-3.5 !text-xs font-semibold inline-flex items-center gap-1"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span>Save Changes</span>
                        </button>

                        <div class="flex items-center gap-1.5">
                            <a id="modal-open-link" href="#" target="_blank" rel="noopener noreferrer" class="admin-btn-secondary !py-1.5 !px-2.5 !text-xs">
                                View
                            </a>
                            <a id="modal-download-link" href="#" class="admin-btn bg-slate-800 text-white hover:bg-black !py-1.5 !px-2.5 !text-xs font-semibold">
                                Download
                            </a>
                        </div>
                    </div>
                </form>

                {{-- Delete Form (Separate footer action) --}}
                <div class="pt-2.5 mt-2.5 border-t border-slate-100 flex items-center justify-between">
                    <form action="{{ route('admin.media.destroy') }}" method="POST" id="modal-delete-form" onsubmit="return confirm('Permanently delete this file? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="path" id="modal-delete-path">
                        <button type="submit" class="admin-btn-danger !py-1 !px-2 !text-[11px] cursor-pointer">
                            Delete File
                        </button>
                    </form>
                    <button type="button" onclick="closeMediaModal()" class="text-xs text-slate-500 hover:text-slate-800">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let bulkSelectActive = false;

function toggleUploadDrawer() {
    const el = document.getElementById('upload-drawer');
    el.classList.toggle('hidden');
    if (!el.classList.contains('hidden')) {
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function toggleCustomFolder(val) {
    const box = document.getElementById('custom-folder-box');
    if (val === 'custom') {
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
    }
}

function handleFileSelect(input) {
    const preview = document.getElementById('selected-files-preview');
    if (!input.files || input.files.length === 0) {
        preview.classList.add('hidden');
        preview.innerHTML = '';
        return;
    }
    const count = input.files.length;
    let listHtml = `<strong>${count} file(s) selected:</strong><ul class="list-disc pl-4 mt-1 text-muted">`;
    for (let i = 0; i < Math.min(count, 5); i++) {
        listHtml += `<li>${input.files[i].name} (${(input.files[i].size / 1024).toFixed(1)} KB)</li>`;
    }
    if (count > 5) {
        listHtml += `<li>...and ${count - 5} more</li>`;
    }
    listHtml += `</ul>`;
    preview.innerHTML = listHtml;
    preview.classList.remove('hidden');
}

function toggleBulkSelectMode() {
    bulkSelectActive = !bulkSelectActive;
    const btn = document.getElementById('bulk-toggle-btn');
    const wrappers = document.querySelectorAll('.media-checkbox-wrapper');

    if (bulkSelectActive) {
        btn.innerText = 'Cancel select';
        btn.classList.remove('text-blue-700', 'border-blue-600');
        btn.classList.add('text-slate-600', 'border-slate-300', 'bg-slate-100');
        wrappers.forEach(w => w.classList.remove('hidden'));
    } else {
        btn.innerText = 'Bulk select';
        btn.classList.add('text-blue-700', 'border-blue-600');
        btn.classList.remove('text-slate-600', 'border-slate-300', 'bg-slate-100');
        wrappers.forEach(w => w.classList.add('hidden'));

        // uncheck all
        document.querySelectorAll('.media-item-checkbox').forEach(cb => {
            cb.checked = false;
            cb.closest('.media-card')?.classList.remove('ring-2', 'ring-blue-600', 'border-blue-600');
        });
        onCheckboxToggled();
    }
}

function handleCardClick(card, event, file) {
    if (bulkSelectActive) {
        const checkbox = card.querySelector('.media-item-checkbox');
        if (checkbox) {
            checkbox.checked = !checkbox.checked;
            onCheckboxToggled();
        }
    } else {
        openMediaModal(file);
    }
}

function onCheckboxToggled() {
    const checked = document.querySelectorAll('.media-item-checkbox:checked');
    const count = checked.length;
    const actionWrapper = document.getElementById('bulk-action-wrapper');
    const badge = document.getElementById('bulk-count-badge');

    // Highlight selected cards
    document.querySelectorAll('.media-card').forEach(card => {
        const cb = card.querySelector('.media-item-checkbox');
        if (cb && cb.checked) {
            card.classList.add('ring-2', 'ring-blue-600', 'border-blue-600');
        } else {
            card.classList.remove('ring-2', 'ring-blue-600', 'border-blue-600');
        }
    });

    if (count > 0) {
        actionWrapper.classList.remove('hidden');
        actionWrapper.classList.add('flex');
        badge.innerText = `${count} selected`;
    } else {
        actionWrapper.classList.add('hidden');
        actionWrapper.classList.remove('flex');
    }
}

function selectAllCurrentPage() {
    const checkboxes = document.querySelectorAll('.media-item-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => { cb.checked = !allChecked; });
    onCheckboxToggled();
}

function toggleTableSelectAll(master) {
    document.querySelectorAll('.media-item-checkbox').forEach(cb => {
        cb.checked = master.checked;
    });
    onCheckboxToggled();
}

function submitBulkDelete() {
    const checked = document.querySelectorAll('.media-item-checkbox:checked');
    if (checked.length === 0) return;
    if (!confirm(`Are you sure you want to permanently delete ${checked.length} selected file(s)?`)) return;

    const container = document.getElementById('bulk-delete-inputs');
    container.innerHTML = '';
    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'paths[]';
        input.value = cb.value;
        container.appendChild(input);
    });

    document.getElementById('bulk-delete-hidden-form').submit();
}

function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = `<svg class="h-3 w-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`;
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    }).catch(() => {
        prompt('Copy this link:', text);
    });
}

function copyModalUrl(btn) {
    const input = document.getElementById('modal-url-input');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        const orig = btn.innerText;
        btn.innerText = 'Copied!';
        setTimeout(() => { btn.innerText = orig; }, 1500);
    });
}

function openMediaModal(file) {
    const modal = document.getElementById('media-modal');
    document.getElementById('modal-header-title').innerText = file.title || file.name;
    document.getElementById('edit-path').value = file.path;
    document.getElementById('edit-title').value = file.title || '';
    document.getElementById('edit-filename').value = file.name;
    document.getElementById('edit-alt').value = file.alt_text || '';
    document.getElementById('edit-caption').value = file.caption || '';
    document.getElementById('edit-desc').value = file.description || '';
    document.getElementById('modal-url-input').value = file.url;
    document.getElementById('modal-folder').innerText = file.folder;
    document.getElementById('modal-size').innerText = file.size_formatted;
    document.getElementById('modal-dimensions').innerText = file.dimensions || (file.is_image ? 'Image file' : file.extension);
    document.getElementById('modal-date').innerText = file.last_modified_formatted;
    document.getElementById('modal-delete-path').value = file.path;
    document.getElementById('modal-open-link').href = file.url;
    document.getElementById('modal-download-link').href = `{{ route('admin.media.download') }}?path=${encodeURIComponent(file.path)}`;

    const feedback = document.getElementById('edit-feedback-msg');
    if (feedback) {
        feedback.className = 'hidden rounded-lg p-2 text-xs font-medium';
        feedback.innerText = '';
    }

    const previewBox = document.getElementById('modal-preview-box');
    if (file.is_image) {
        previewBox.innerHTML = `<img src="${file.url}" alt="${file.name}" class="h-full w-full object-contain rounded">`;
    } else if (file.is_video) {
        previewBox.innerHTML = `<video controls class="h-full w-full object-contain rounded"><source src="${file.url}"></video>`;
    } else {
        previewBox.innerHTML = `<div class="text-center text-slate-300 p-2"><svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg><p class="mt-1 text-[10px] font-semibold uppercase text-slate-400">${file.extension}</p></div>`;
    }

    modal.classList.remove('hidden');
}

function handleMediaFormSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('save-media-btn');
    const feedback = document.getElementById('edit-feedback-msg');
    const form = document.getElementById('media-edit-form');
    const formData = new FormData(form);

    btn.disabled = true;
    btn.classList.add('opacity-70');
    feedback.className = 'hidden';

    fetch('{{ route("admin.media.update") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async (res) => {
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Failed to save changes');
        return data;
    })
    .then((data) => {
        feedback.innerText = data.message || 'Saved successfully!';
        feedback.className = 'rounded-lg p-2 text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 block';

        if (data.path) {
            document.getElementById('edit-path').value = data.path;
            document.getElementById('modal-delete-path').value = data.path;
        }
        if (data.name) {
            document.getElementById('edit-filename').value = data.name;
        }
        if (data.url) {
            document.getElementById('modal-url-input').value = data.url;
            document.getElementById('modal-open-link').href = data.url;
        }
        if (data.meta && data.meta.title) {
            document.getElementById('modal-header-title').innerText = data.meta.title;
        }

        setTimeout(() => {
            window.location.reload();
        }, 800);
    })
    .catch((err) => {
        feedback.innerText = err.message;
        feedback.className = 'rounded-lg p-2 text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 block';
    })
    .finally(() => {
        btn.disabled = false;
        btn.classList.remove('opacity-70');
    });
}

function closeMediaModal() {
    const modal = document.getElementById('media-modal');
    modal.classList.add('hidden');
    document.getElementById('modal-preview-box').innerHTML = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMediaModal();
});
document.getElementById('media-modal')?.addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeMediaModal();
});
</script>
@endsection
