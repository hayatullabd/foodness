<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpBlock;
use App\Models\OrderBlockLog;
use App\Models\OrderRestrictionSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderRestrictionSettingController extends Controller
{
    public function edit(Request $request): View
    {
        $settings = OrderRestrictionSetting::current();

        // Auto-clean logs older than 30 days
        OrderBlockLog::pruneOlderThan(30);

        $query = OrderBlockLog::query()->orderByRaw('COALESCE(last_attempt_at, created_at) DESC');

        if ($request->filled('type')) {
            $query->where('block_type', $request->query('type'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($sub) use ($search): void {
                $sub->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('device_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('offending_value', 'like', "%{$search}%");
            });
        }

        if ($request->query('date') === 'today') {
            $query->where(function ($q): void {
                $q->whereDate('created_at', today())
                    ->orWhereDate('last_attempt_at', today());
            });
        } elseif ($request->query('date') === 'week') {
            $query->where(function ($q): void {
                $q->where('created_at', '>=', now()->subDays(7))
                    ->orWhere('last_attempt_at', '>=', now()->subDays(7));
            });
        } elseif ($request->query('date') === 'month') {
            $query->where(function ($q): void {
                $q->where('created_at', '>=', now()->subDays(30))
                    ->orWhere('last_attempt_at', '>=', now()->subDays(30));
            });
        }

        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));
        $logs = $query->paginate($perPage)->withQueryString();

        $totalBlockedAttempts = (int) (OrderBlockLog::query()->sum('attempts_count') ?: OrderBlockLog::query()->count());
        $todayBlockedAttempts = (int) (OrderBlockLog::query()
            ->where(function ($q): void {
                $q->whereDate('created_at', today())
                    ->orWhereDate('last_attempt_at', today());
            })
            ->sum('attempts_count') ?: 0);

        $activeBlockedIps = $this->collectActiveIps($settings);
        $activeBlockedPhones = $settings->parseList($settings->blocked_phones);
        $activeBlockedDevices = $settings->parseList($settings->blocked_devices);

        $activeBlacklistRows = $this->buildActiveBlacklistRows($settings, $activeBlockedIps);

        return view('admin.order-restrictions.edit', [
            'settings' => $settings,
            'logs' => $logs,
            'totalBlockedAttempts' => $totalBlockedAttempts,
            'todayBlockedAttempts' => $todayBlockedAttempts,
            'activeBlockedIps' => $activeBlockedIps,
            'activeBlockedPhones' => $activeBlockedPhones,
            'activeBlockedDevices' => $activeBlockedDevices,
            'activeBlacklistRows' => $activeBlacklistRows,
            'activeTab' => $request->query('tab', 'settings'),
        ]);
    }

    /**
     * Combine unique IPs from both the IpBlock database table and settings blocked_ips.
     *
     * @return array<int, array{ip: string, reason: string|null, created_at: string|null}>
     */
    private function collectActiveIps(OrderRestrictionSetting $settings): array
    {
        $result = [];
        $seen = [];

        foreach (IpBlock::query()->latest('id')->get() as $dbBlock) {
            $seen[$dbBlock->ip] = true;
            $result[] = [
                'ip' => $dbBlock->ip,
                'reason' => $dbBlock->reason,
                'created_at' => $dbBlock->created_at?->format('Y-m-d H:i') ?? 'Database Registry',
            ];
        }

        foreach ($settings->parseList($settings->blocked_ips) as $ip) {
            if (! isset($seen[$ip])) {
                $seen[$ip] = true;
                $result[] = [
                    'ip' => $ip,
                    'reason' => 'Blacklisted IP in Settings',
                    'created_at' => 'Active Setting',
                ];
            }
        }

        return $result;
    }

    /**
     * Build unified rows for the active blacklist spreadsheet.
     *
     * @param  array<int, array{ip: string, reason: string|null, created_at: string|null}>  $activeBlockedIps
     * @return array<int, array{type: string, raw_type: string, value: string, reason: string, source: string}>
     */
    private function buildActiveBlacklistRows(OrderRestrictionSetting $settings, array $activeBlockedIps): array
    {
        $rows = [];
        foreach ($activeBlockedIps as $ipItem) {
            $rows[] = [
                'type' => 'IP Address',
                'raw_type' => 'ip',
                'value' => $ipItem['ip'],
                'reason' => $ipItem['reason'] ?? 'Manual IP Blacklist',
                'source' => $ipItem['created_at'],
            ];
        }
        foreach ($settings->parseList($settings->blocked_phones) as $phone) {
            $rows[] = [
                'type' => 'Phone Number',
                'raw_type' => 'phone',
                'value' => $phone,
                'reason' => 'Blacklisted Phone in Settings',
                'source' => 'Active Setting',
            ];
        }
        foreach ($settings->parseList($settings->blocked_devices) as $device) {
            $rows[] = [
                'type' => 'Device / Bot',
                'raw_type' => 'device',
                'value' => $device,
                'reason' => 'Blacklisted Device / User-Agent',
                'source' => 'Active Setting',
            ];
        }

        return $rows;
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'max_per_product' => ['required', 'integer', 'min:1', 'max:1000'],

            // IP Restrictions
            'block_ip_enabled' => ['nullable', 'boolean'],
            'blocked_ips' => ['nullable', 'string'],
            'max_per_ip' => ['nullable', 'integer', 'min:1', 'max:1000'],

            // Phone Restrictions
            'block_phone_enabled' => ['nullable', 'boolean'],
            'blocked_phones' => ['nullable', 'string'],
            'max_per_phone' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'validate_bd_phone' => ['nullable', 'boolean'],

            // Device Restrictions
            'block_device_enabled' => ['nullable', 'boolean'],
            'blocked_devices' => ['nullable', 'string'],
            'max_per_device' => ['nullable', 'integer', 'min:1', 'max:1000'],

            // Anti-Spam Content Restrictions
            'block_words_enabled' => ['nullable', 'boolean'],
            'blocked_words' => ['nullable', 'string'],
            'min_address_length' => ['nullable', 'integer', 'min:1', 'max:250'],
        ]);

        $settings = OrderRestrictionSetting::current();
        $settings->fill([
            'enabled' => $request->boolean('enabled'),
            'hours' => (int) $validated['hours'],
            'max_per_product' => (int) $validated['max_per_product'],

            'block_ip_enabled' => $request->boolean('block_ip_enabled'),
            'blocked_ips' => $validated['blocked_ips'] ?? null,
            'max_per_ip' => ! empty($validated['max_per_ip']) ? (int) $validated['max_per_ip'] : null,

            'block_phone_enabled' => $request->boolean('block_phone_enabled'),
            'blocked_phones' => $validated['blocked_phones'] ?? null,
            'max_per_phone' => ! empty($validated['max_per_phone']) ? (int) $validated['max_per_phone'] : null,
            'validate_bd_phone' => $request->boolean('validate_bd_phone'),

            'block_device_enabled' => $request->boolean('block_device_enabled'),
            'blocked_devices' => $validated['blocked_devices'] ?? null,
            'max_per_device' => ! empty($validated['max_per_device']) ? (int) $validated['max_per_device'] : null,

            'block_words_enabled' => $request->boolean('block_words_enabled'),
            'blocked_words' => $validated['blocked_words'] ?? null,
            'min_address_length' => ! empty($validated['min_address_length']) ? (int) $validated['min_address_length'] : null,
        ]);
        $settings->save();

        return redirect()
            ->route('admin.order-restrictions.edit')
            ->with('success', 'Advance order restrictions & anti-fraud settings updated successfully.');
    }
}
