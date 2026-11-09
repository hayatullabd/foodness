<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Display a specific content or policy page by its slug.
     */
    public function show(string $slug): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $allPages = Page::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'title']);

        return view('store.pages.show', [
            'page' => $page,
            'allPages' => $allPages,
        ]);
    }

    public function showPrivacy(): View
    {
        return $this->show('privacy-policy');
    }

    public function showRefund(): View
    {
        return $this->show('refund-return-policy');
    }

    public function showTerms(): View
    {
        return $this->show('terms-and-conditions');
    }

    public function showShipping(): View
    {
        return $this->show('shipping-policy');
    }

    public function showPayment(): View
    {
        return $this->show('payment-policy');
    }

    public function showHowToOrder(): View
    {
        return $this->show('how-to-order');
    }

    /**
     * Display the live order tracking page and handle search requests.
     */
    public function track(Request $request): View
    {
        $page = Page::query()->where('slug', 'order-track')->first();
        $query = trim((string) $request->input('query', ''));
        $orders = collect();

        if ($query !== '') {
            $invoice = ltrim($query, '#');
            $phone = preg_replace('/\D+/', '', $query) ?? '';

            if (str_starts_with($phone, '880') && strlen($phone) === 13) {
                $phone = '0'.substr($phone, 3);
            }

            $orders = Order::query()
                ->with(['items', 'orderStatus'])
                ->where(function ($q) use ($invoice, $phone): void {
                    $q->where('invoice_no', $invoice);

                    if (strlen($phone) === 11 && str_starts_with($phone, '01')) {
                        $q->orWhere('customer_phone', $phone);
                    }
                })
                ->latest('id')
                ->limit(5)
                ->get();
        }

        return view('store.pages.track', [
            'page' => $page,
            'query' => $query,
            'orders' => $orders,
        ]);
    }

    /**
     * Display the dedicated FAQ page.
     */
    public function faq(): View
    {
        $page = Page::query()->where('slug', 'faq')->first();

        return view('store.pages.faq', [
            'page' => $page,
        ]);
    }

    /**
     * Display the Contact Us page.
     */
    public function contact(): View
    {
        $page = Page::query()->where('slug', 'contact')->first();

        return view('store.pages.contact', [
            'page' => $page,
        ]);
    }

    /**
     * Process contact form inquiries.
     */
    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        ContactMessage::query()->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'],
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('contact.index')
            ->with('status', 'ধন্যবাদ! আপনার বার্তাটি সফলভাবে পাঠানো হয়েছে। আমাদের প্রতিনিধি শীঘ্রই আপনার সাথে যোগাযোগ করবেন।');
    }
}
