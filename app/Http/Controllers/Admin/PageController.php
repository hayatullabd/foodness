<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Display the index list of all customizable pages and received customer inquiries.
     */
    public function index(Request $request): View
    {
        $pages = Page::query()
            ->orderBy('sort_order')
            ->get();

        $messages = ContactMessage::query()
            ->latest()
            ->paginate(15);

        $unreadCount = ContactMessage::query()
            ->where('is_read', false)
            ->count();

        return view('admin.pages.index', [
            'pages' => $pages,
            'messages' => $messages,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Display the edit form for a specific page.
     */
    public function edit(Page $page): View
    {
        return view('admin.pages.edit', [
            'page' => $page,
        ]);
    }

    /**
     * Update a page's content, title, subtitle, and SEO metadata.
     */
    public function update(Request $request, Page $page): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $page->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'content' => $validated['content'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', "'{$page->title}' updated successfully.");
    }

    /**
     * Toggle a page's published active status.
     */
    public function toggle(Page $page): RedirectResponse
    {
        $page->update([
            'is_active' => ! $page->is_active,
        ]);

        $status = $page->is_active ? 'published' : 'draft';

        return redirect()
            ->route('admin.pages.index')
            ->with('success', "'{$page->title}' is now {$status}.");
    }

    /**
     * Delete a received customer contact inquiry.
     */
    public function destroyMessage(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Customer inquiry removed successfully.');
    }
}
