<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierApi;
use App\Services\Courier\PathaoTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourierApiController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = strtolower(trim((string) $request->query('type', '')));
        $status = strtolower(trim((string) $request->query('status', '')));
        $viewMode = $request->query('view') === 'grid' ? 'grid' : 'list';

        $methods = CourierApi::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('provider', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($type, CourierApi::PROVIDERS, true), fn ($query) => $query->where('provider', $type))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('is_preferred')
            ->orderBy('provider')
            ->orderBy('name')
            ->get();

        $editing = null;
        $editId = $request->integer('edit');
        if ($editId > 0) {
            $editing = CourierApi::query()->find($editId);
        }

        return view('admin.courier-apis.index', [
            'methods' => $methods,
            'stats' => [
                'total' => CourierApi::query()->count(),
                'active' => CourierApi::query()->where('is_active', true)->count(),
                'inactive' => CourierApi::query()->where('is_active', false)->count(),
            ],
            'filters' => [
                'q' => $search,
                'type' => $type,
                'status' => $status,
                'view' => $viewMode,
            ],
            'editing' => $editing,
            'openForm' => $request->boolean('create') || $editing !== null || $request->session()->hasOldInput(),
            'redxBaseUrls' => CourierApi::REDX_BASE_URLS,
            'steadfastWebhookUrl' => rtrim((string) config('app.url'), '/').'/api/steadfast/webhook',
            'redxWebhookUrl' => rtrim((string) config('app.url'), '/').'/api/redx/webhook',
            'carrybeeWebhookUrl' => rtrim((string) config('app.url'), '/').'/api/carrybee/webhook',
            'carrybeeBaseUrls' => CourierApi::CARRYBEE_BASE_URLS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $courierApi = new CourierApi;
        $this->persistMethod($request, $courierApi);

        return redirect()
            ->route('admin.courier-apis.index')
            ->with('success', 'Delivery method created.');
    }

    public function update(Request $request, CourierApi $courierApi): RedirectResponse
    {
        $this->persistMethod($request, $courierApi);

        return redirect()
            ->route('admin.courier-apis.index')
            ->with('success', 'Delivery method updated.');
    }

    public function destroy(CourierApi $courierApi): RedirectResponse
    {
        $courierApi->delete();

        return redirect()
            ->route('admin.courier-apis.index')
            ->with('success', 'Delivery method deleted.');
    }

    public function generatePathaoToken(Request $request, CourierApi $courierApi, PathaoTokenService $tokens): RedirectResponse
    {
        abort_unless($courierApi->provider === CourierApi::PROVIDER_PATHAO, 404);

        $validated = $request->validate([
            'base_url' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:2000'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:2000'],
        ]);

        $courierApi->mergeSecrets([
            'base_url' => CourierApi::normalizeBaseUrl(
                $validated['base_url'] ?? null,
                CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_PATHAO],
            ),
            'api_key' => $validated['api_key'] ?? '',
            'secret_key' => $validated['secret_key'] ?? '',
            'username' => $validated['username'] ?? $courierApi->username,
            'password' => $validated['password'] ?? '',
        ]);
        $courierApi->save();

        $result = $tokens->generateFor(
            $courierApi->fresh(),
            filled($validated['password'] ?? null) ? $validated['password'] : null,
        );

        return redirect()
            ->route('admin.courier-apis.index', ['edit' => $courierApi->id])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function persistMethod(Request $request, CourierApi $courierApi): void
    {
        $provider = strtolower(trim((string) $request->input('provider', $courierApi->provider)));

        abort_unless(in_array($provider, CourierApi::PROVIDERS, true), 404);

        $validated = $request->validate($this->rulesFor($provider, $courierApi->exists));

        $courierApi->provider = $provider;
        $courierApi->name = trim((string) $validated['name']);
        $courierApi->is_active = $request->boolean('is_active');
        $courierApi->is_preferred = $request->boolean('is_preferred');

        match ($provider) {
            CourierApi::PROVIDER_STEADFAST => $this->applySteadfast($courierApi, $validated, $request),
            CourierApi::PROVIDER_PATHAO => $this->applyPathao($courierApi, $validated, $request),
            CourierApi::PROVIDER_REDX => $this->applyRedx($courierApi, $validated, $request),
            CourierApi::PROVIDER_CARRYBEE => $this->applyCarrybee($courierApi, $validated, $request),
        };

        $courierApi->save();
        $courierApi->markPreferredExclusive();
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesFor(string $provider, bool $updating): array
    {
        $base = [
            'name' => ['required', 'string', 'max:120'],
            'provider' => [$updating ? 'nullable' : 'required', Rule::in(CourierApi::PROVIDERS)],
            'is_active' => ['nullable', 'boolean'],
            'is_preferred' => ['nullable', 'boolean'],
            'send_product_names' => ['nullable', 'boolean'],
            'default_shipping_note' => ['nullable', 'string', 'max:2000'],
        ];

        return match ($provider) {
            CourierApi::PROVIDER_STEADFAST => array_merge($base, [
                'api_key' => ['nullable', 'string', 'max:255'],
                'secret_key' => ['nullable', 'string', 'max:2000'],
                'base_url' => ['nullable', 'string', 'max:255'],
                'token' => ['nullable', 'string', 'max:2000'],
                'account_email' => ['nullable', 'email', 'max:255'],
                'account_password' => ['nullable', 'string', 'max:2000'],
            ]),
            CourierApi::PROVIDER_PATHAO => array_merge($base, [
                'base_url' => ['nullable', 'string', 'max:255'],
                'api_key' => ['nullable', 'string', 'max:255'],
                'secret_key' => ['nullable', 'string', 'max:2000'],
                'username' => ['nullable', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'max:2000'],
                'token' => ['nullable', 'string', 'max:5000'],
                'store_id' => ['nullable', 'integer', 'min:1'],
            ]),
            CourierApi::PROVIDER_REDX => array_merge($base, [
                'environment' => ['required', Rule::in(['production', 'sandbox'])],
                'token' => ['nullable', 'string', 'max:5000'],
                'default_area_id' => ['nullable', 'integer', 'min:1'],
                'default_area_name' => ['nullable', 'string', 'max:255'],
                'pickup_store_id' => ['nullable', 'integer', 'min:1'],
            ]),
            CourierApi::PROVIDER_CARRYBEE => array_merge($base, [
                'environment' => ['required', Rule::in(['production', 'sandbox'])],
                'api_key' => ['nullable', 'string', 'max:255'],
                'secret_key' => ['nullable', 'string', 'max:2000'],
                'username' => ['nullable', 'string', 'max:255'],
                'token' => ['nullable', 'string', 'max:2000'],
                'store_id' => ['nullable', 'string', 'max:80'],
                'delivery_type' => ['nullable', 'integer', Rule::in([1, 2])],
                'product_type' => ['nullable', 'integer', Rule::in([1, 2, 3])],
                'item_weight' => ['nullable', 'integer', 'min:1', 'max:25000'],
            ]),
            default => $base,
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applySteadfast(CourierApi $courierApi, array $validated, Request $request): void
    {
        $courierApi->mergeSecrets([
            'api_key' => $validated['api_key'] ?? '',
            'secret_key' => $validated['secret_key'] ?? '',
            'base_url' => CourierApi::normalizeBaseUrl(
                $validated['base_url'] ?? null,
                CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_STEADFAST],
            ),
            'token' => CourierApi::normalizeBearerToken($validated['token'] ?? null) ?? '',
        ]);

        $meta = is_array($courierApi->meta) ? $courierApi->meta : [];
        $meta['send_product_names'] = $request->boolean('send_product_names');
        $meta['default_shipping_note'] = trim((string) ($validated['default_shipping_note'] ?? ''));

        $accountEmail = trim((string) ($validated['account_email'] ?? ''));
        if ($accountEmail !== '') {
            $meta['account_email'] = $accountEmail;
        }

        $accountPassword = trim((string) ($validated['account_password'] ?? ''));
        if ($accountPassword !== '') {
            $meta['account_password'] = $accountPassword;
        }

        if (($meta['default_shipping_note'] ?? '') === '') {
            unset($meta['default_shipping_note']);
        }

        $courierApi->meta = $meta === [] ? null : $meta;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyPathao(CourierApi $courierApi, array $validated, Request $request): void
    {
        $courierApi->mergeSecrets([
            'base_url' => CourierApi::normalizeBaseUrl(
                $validated['base_url'] ?? null,
                CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_PATHAO],
            ),
            'api_key' => $validated['api_key'] ?? '',
            'secret_key' => $validated['secret_key'] ?? '',
            'username' => $validated['username'] ?? '',
            'password' => $validated['password'] ?? '',
            'token' => CourierApi::normalizeBearerToken($validated['token'] ?? null) ?? '',
        ]);

        $meta = is_array($courierApi->meta) ? $courierApi->meta : [];
        $meta['send_product_names'] = $request->boolean('send_product_names');
        $meta['default_shipping_note'] = trim((string) ($validated['default_shipping_note'] ?? ''));

        if (! empty($validated['store_id'])) {
            $meta['store_id'] = (int) $validated['store_id'];
        }

        if (($meta['default_shipping_note'] ?? '') === '') {
            unset($meta['default_shipping_note']);
        }

        $courierApi->meta = $meta === [] ? null : $meta;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyRedx(CourierApi $courierApi, array $validated, Request $request): void
    {
        $courierApi->mergeSecrets([
            'base_url' => CourierApi::REDX_BASE_URLS[$validated['environment']],
            'token' => CourierApi::normalizeBearerToken($validated['token'] ?? null) ?? '',
        ]);

        $meta = is_array($courierApi->meta) ? $courierApi->meta : [];
        $meta['send_product_names'] = $request->boolean('send_product_names');
        $meta['default_shipping_note'] = trim((string) ($validated['default_shipping_note'] ?? ''));

        if (! empty($validated['default_area_id'])) {
            $meta['default_area_id'] = (int) $validated['default_area_id'];
        }
        if (! empty($validated['default_area_name'])) {
            $meta['default_area_name'] = trim((string) $validated['default_area_name']);
        }
        if (! empty($validated['pickup_store_id'])) {
            $meta['pickup_store_id'] = (int) $validated['pickup_store_id'];
        }

        if (($meta['default_shipping_note'] ?? '') === '') {
            unset($meta['default_shipping_note']);
        }

        $courierApi->meta = $meta === [] ? null : $meta;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyCarrybee(CourierApi $courierApi, array $validated, Request $request): void
    {
        $courierApi->mergeSecrets([
            'base_url' => CourierApi::CARRYBEE_BASE_URLS[$validated['environment']],
            'api_key' => $validated['api_key'] ?? '',
            'secret_key' => $validated['secret_key'] ?? '',
            'username' => $validated['username'] ?? '',
            'token' => trim((string) ($validated['token'] ?? '')),
        ], ['api_key', 'secret_key', 'username', 'token']);

        $meta = is_array($courierApi->meta) ? $courierApi->meta : [];
        $meta['send_product_names'] = $request->boolean('send_product_names');
        $meta['default_shipping_note'] = trim((string) ($validated['default_shipping_note'] ?? ''));
        $meta['delivery_type'] = (int) ($validated['delivery_type'] ?? 1);
        $meta['product_type'] = (int) ($validated['product_type'] ?? 1);
        $meta['item_weight'] = (int) ($validated['item_weight'] ?? 500);

        $storeId = trim((string) ($validated['store_id'] ?? ''));
        if ($storeId !== '') {
            $meta['store_id'] = $storeId;
        }

        if (($meta['default_shipping_note'] ?? '') === '') {
            unset($meta['default_shipping_note']);
        }

        $courierApi->meta = $meta === [] ? null : $meta;
    }
}
