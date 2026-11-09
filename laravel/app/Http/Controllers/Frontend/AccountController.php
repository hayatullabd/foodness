<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Display the customer's dashboard and order history.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $orders = $user
            ->orders()
            ->withCount('items')
            ->latest()
            ->paginate(10);

        $stats = [
            'total' => $user->orders()->count(),
            'pending' => $user->orders()->whereIn('status', ['pending', 'processing', 'confirmed'])->count(),
            'delivered' => $user->orders()->whereIn('status', ['delivered', 'completed'])->count(),
            'total_spent' => (float) $user->orders()->whereIn('status', ['delivered', 'completed'])->sum('total'),
        ];

        return view('store.account.index', [
            'orders' => $orders,
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Display the customer's profile settings and address details.
     */
    public function profile(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $stats = [
            'total' => $user->orders()->count(),
            'delivered' => $user->orders()->whereIn('status', ['delivered', 'completed'])->count(),
        ];

        return view('store.account.profile', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Update the customer's personal profile information.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $email = $request->filled('email') ? trim((string) $request->input('email')) : null;
        $phone = $request->filled('phone') ? trim((string) $request->input('phone')) : null;

        if ($email !== null && ! CustomerAuthController::isValidEmail($email)) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'দয়া করে একটি সঠিক ও কার্যকর ইমেইল অ্যাড্রেস প্রদান করুন (যেমন: name@example.com)।']);
        }

        if ($phone !== null) {
            $normalizedPhone = CustomerAuthController::normalizeAndValidatePhone($phone);
            if (! $normalizedPhone) {
                return back()
                    ->withInput()
                    ->withErrors(['phone' => 'দয়া করে একটি সঠিক ১১ ডিজিটের মোবাইল নম্বর প্রদান করুন (যেমন: 017XXXXXXXX)।']);
            }
            $phone = $normalizedPhone;
        }

        $request->merge([
            'email' => $email ? strtolower($email) : null,
            'phone' => $phone,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                'required_without:phone',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore($user->id),
                'required_without:email',
            ],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'email.required_without' => 'ইমেইল অথবা ফোন নম্বরের যেকোনো একটি প্রদান করা আবশ্যক।',
            'phone.required_without' => 'ফোন নম্বর অথবা ইমেইল অ্যাড্রেসের যেকোনো একটি প্রদান করা আবশ্যক।',
            'email.unique' => 'এই ইমেইল দিয়ে ইতিমধ্যে একটি অ্যাকাউন্ট তৈরি করা হয়েছে।',
            'phone.unique' => 'এই ফোন নম্বর দিয়ে ইতিমধ্যে একটি অ্যাকাউন্ট তৈরি করা হয়েছে।',
        ]);

        $user->update($validated);

        return redirect()
            ->route('account.profile')
            ->with('status', 'আপনার প্রোফাইল তথ্য সফলভাবে আপডেট করা হয়েছে।');
    }

    /**
     * Change the customer's password after verifying their current password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('account.profile')
            ->with('status', 'পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে।');
    }

    /**
     * Display specific customer order details.
     */
    public function showOrder(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load('items');

        return view('store.account.order', [
            'order' => $order,
        ]);
    }
}
