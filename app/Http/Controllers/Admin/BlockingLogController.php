<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpBlock;
use App\Models\Order;
use App\Models\OrderBlockLog;
use App\Models\OrderRestrictionSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BlockingLogController extends Controller
{
    /**
     * Display a detailed Excel/spreadsheet-like listing of blocked order attempts and blacklists.
     */
    public function index(Request $request): View
    {
        $settings = OrderRestrictionSetting::current();

        // Auto-clean logs older than 30 days
        OrderBlockLog::pruneOlderThan(30);

        $query = OrderBlockLog::query()->orderByRaw('COALESCE(last_attempt_at, created_at) DESC');

        // Filter by block type if requested
        if ($request->filled('type')) {
            $query->where('block_type', $request->query('type'));
        }

        // Search across IP, phone, device_id, customer_name, reason, offending_value
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

        // Date range filter
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

        // Statistics (Sum total attempts for accurate volume)
        $totalBlockedAttempts = (int) (OrderBlockLog::query()->sum('attempts_count') ?: OrderBlockLog::query()->count());
        $todayBlockedAttempts = (int) (OrderBlockLog::query()
            ->where(function ($q): void {
                $q->whereDate('created_at', today())
                    ->orWhereDate('last_attempt_at', today());
            })
            ->sum('attempts_count') ?: 0);

        // Active blocked entities
        $activeBlockedIps = $this->collectActiveIps($settings);
        $activeBlockedPhones = $settings->parseList($settings->blocked_phones);
        $activeBlockedDevices = $settings->parseList($settings->blocked_devices);

        // Combined active blacklist rows for the Active Blacklist Excel sheet
        $activeBlacklistRows = [];
        foreach ($activeBlockedIps as $ipItem) {
            $activeBlacklistRows[] = [
                'type' => 'IP Address',
                'raw_type' => 'ip',
                'value' => $ipItem['ip'],
                'reason' => $ipItem['reason'] ?? 'Manual IP Blacklist',
                'source' => $ipItem['created_at'],
            ];
        }
        foreach ($activeBlockedPhones as $phone) {
            $activeBlacklistRows[] = [
                'type' => 'Phone Number',
                'raw_type' => 'phone',
                'value' => $phone,
                'reason' => 'Blacklisted Phone in Settings',
                'source' => 'Active Setting',
            ];
        }
        foreach ($activeBlockedDevices as $device) {
            $activeBlacklistRows[] = [
                'type' => 'Device / Bot',
                'raw_type' => 'device',
                'value' => $device,
                'reason' => 'Blacklisted Device / User-Agent',
                'source' => 'Active Setting',
            ];
        }

        return view('admin.blocking-logs.index', [
            'logs' => $logs,
            'settings' => $settings,
            'totalBlockedAttempts' => $totalBlockedAttempts,
            'todayBlockedAttempts' => $todayBlockedAttempts,
            'activeBlockedIps' => $activeBlockedIps,
            'activeBlockedPhones' => $activeBlockedPhones,
            'activeBlockedDevices' => $activeBlockedDevices,
            'activeBlacklistRows' => $activeBlacklistRows,
            'activeTab' => $request->query('tab', 'history'),
        ]);
    }

    /**
     * Export blocking history log to CSV / Excel spreadsheet.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = OrderBlockLog::query()->latest('id');

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

        $filename = 'blocking_history_log_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, [
                'SL',
                'ID',
                'First Attempt Date',
                'Latest Attempt Date',
                'Attempts Count',
                'Protection Vector',
                'Offending Value',
                'Customer Name',
                'Customer Phone',
                'IP Address',
                'Device ID',
                'Delivery Address',
                'Enforced Reason / Message',
            ]);

            $sl = 1;
            $query->chunk(200, function ($records) use ($handle, &$sl): void {
                foreach ($records as $record) {
                    fputcsv($handle, [
                        $sl++,
                        $record->id,
                        $record->created_at->format('Y-m-d H:i:s'),
                        $record->last_attempt_at ? $record->last_attempt_at->format('Y-m-d H:i:s') : $record->created_at->format('Y-m-d H:i:s'),
                        $record->attempts_count ?? 1,
                        $record->typeLabel(),
                        $record->offending_value ?: '',
                        $record->customer_name ?: '',
                        $record->phone ?: '',
                        $record->ip_address ?: '',
                        $record->device_id ?: '',
                        $record->customer_address ?: '',
                        $record->reason,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Clear / purge all records in the blocking history log AND reset active blacklists.
     */
    public function clear(Request $request): RedirectResponse
    {
        // 1. Purge all log history records
        OrderBlockLog::query()->delete();

        // 2. Clear all active IP blocks from ip_blocks table
        IpBlock::query()->delete();

        // 3. Reset all active blacklists in order_restriction_settings
        $settings = OrderRestrictionSetting::current();
        $settings->update([
            'blocked_ips' => null,
            'blocked_phones' => null,
            'blocked_devices' => null,
        ]);

        // 4. Release all past device order rate limit counts so all devices can place orders immediately
        Order::query()->whereNotNull('device_id')->update(['device_id' => null]);

        $tab = $request->input('tab', $request->query('tab', 'history'));

        return redirect()
            ->route('admin.blocking-logs.index', ['tab' => $tab])
            ->with('success', 'All blocking history logs and active blacklists (IPs, Phones, Devices) have been cleared successfully, and all devices are unblocked.');
    }

    /**
     * Delete an individual log row and unblock the associated device / blacklist entry.
     */
    public function destroy(OrderBlockLog $log): RedirectResponse
    {
        $settings = OrderRestrictionSetting::current();

        // 1. If device_id is present, unblock from blacklist AND reset prior device order rate limits
        if ($log->device_id) {
            $devices = array_filter(
                $settings->parseList($settings->blocked_devices),
                fn (string $d): bool => strcasecmp($d, (string) $log->device_id) !== 0
            );
            $settings->update(['blocked_devices' => implode("\n", $devices) ?: null]);

            // Release prior device orders so this device can order again
            Order::query()->where('device_id', $log->device_id)->update(['device_id' => null]);
        }

        // 2. If phone is present, unblock from blacklist
        if ($log->phone) {
            $phones = array_filter(
                $settings->parseList($settings->blocked_phones),
                fn (string $p): bool => strcasecmp($p, (string) $log->phone) !== 0
            );
            $settings->update(['blocked_phones' => implode("\n", $phones) ?: null]);
        }

        // 3. If IP is present, unblock from blacklist
        if ($log->ip_address) {
            IpBlock::query()->where('ip', $log->ip_address)->delete();
            $ips = array_filter(
                $settings->parseList($settings->blocked_ips),
                fn (string $ip): bool => strcasecmp($ip, (string) $log->ip_address) !== 0
            );
            $settings->update(['blocked_ips' => implode("\n", $ips) ?: null]);
        }

        // 4. Delete the log row
        $log->delete();

        return redirect()
            ->back()
            ->with('success', 'Log entry deleted and target device unblocked successfully.');
    }

    /**
     * Manually add an entity (IP, Phone, or Device) to the active blacklist.
     */
    public function blockEntity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:ip,phone,device'],
            'value' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $type = $validated['type'];
        $value = trim($validated['value']);
        $reason = trim($validated['reason'] ?? 'Manually blocked by admin');
        $settings = OrderRestrictionSetting::current();

        if ($type === 'ip') {
            // Add to IpBlock table
            IpBlock::query()->updateOrCreate(
                ['ip' => $value],
                ['reason' => $reason],
            );

            // Add to settings blocked_ips if not already present
            $currentIps = $settings->parseList($settings->blocked_ips);
            if (! in_array($value, $currentIps, true)) {
                $currentIps[] = $value;
                $settings->update(['blocked_ips' => implode("\n", $currentIps)]);
            }

            $message = "IP address '{$value}' has been permanently blocked.";
        } elseif ($type === 'phone') {
            $currentPhones = $settings->parseList($settings->blocked_phones);
            if (! in_array($value, $currentPhones, true)) {
                $currentPhones[] = $value;
                $settings->update(['blocked_phones' => implode("\n", $currentPhones)]);
            }

            $message = "Phone number '{$value}' has been blocked.";
        } else {
            $currentDevices = $settings->parseList($settings->blocked_devices);
            if (! in_array($value, $currentDevices, true)) {
                $currentDevices[] = $value;
                $settings->update(['blocked_devices' => implode("\n", $currentDevices)]);
            }

            $message = "Device signature '{$value}' has been blocked.";
        }

        return redirect()
            ->route('admin.blocking-logs.index', ['tab' => $request->input('redirect_tab', 'active-blocks')])
            ->with('success', $message);
    }

    /**
     * Remove an entity (IP, Phone, or Device) from active blacklists.
     */
    public function unblockEntity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:ip,phone,device'],
            'value' => ['required', 'string', 'max:255'],
        ]);

        $type = $validated['type'];
        $value = trim($validated['value']);
        $settings = OrderRestrictionSetting::current();

        if ($type === 'ip') {
            IpBlock::query()->where('ip', $value)->delete();

            $currentIps = array_filter(
                $settings->parseList($settings->blocked_ips),
                fn (string $ip): bool => strcasecmp($ip, $value) !== 0,
            );
            $settings->update(['blocked_ips' => implode("\n", $currentIps)]);

            $message = "IP address '{$value}' has been unblocked.";
        } elseif ($type === 'phone') {
            $currentPhones = array_filter(
                $settings->parseList($settings->blocked_phones),
                fn (string $p): bool => strcasecmp($p, $value) !== 0,
            );
            $settings->update(['blocked_phones' => implode("\n", $currentPhones)]);

            $message = "Phone number '{$value}' has been unblocked.";
        } else {
            $currentDevices = array_filter(
                $settings->parseList($settings->blocked_devices),
                fn (string $d): bool => strcasecmp($d, $value) !== 0,
            );
            $settings->update(['blocked_devices' => implode("\n", $currentDevices) ?: null]);

            // Release prior device orders rate limit so this device can order again
            Order::query()->where('device_id', $value)->update(['device_id' => null]);

            $message = "Device '{$value}' has been unblocked.";
        }

        return redirect()
            ->route('admin.blocking-logs.index', ['tab' => $request->input('redirect_tab', 'active-blocks')])
            ->with('success', $message);
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

        // 1. From IpBlock table
        $dbBlocks = IpBlock::query()->latest('id')->get();
        foreach ($dbBlocks as $dbBlock) {
            $seen[$dbBlock->ip] = true;
            $result[] = [
                'ip' => $dbBlock->ip,
                'reason' => $dbBlock->reason,
                'created_at' => $dbBlock->created_at?->format('Y-m-d H:i') ?? 'Database Registry',
            ];
        }

        // 2. From settings blocked_ips
        foreach ($settings->parseList($settings->blocked_ips) as $ip) {
            if (! isset($seen[$ip])) {
                $seen[$ip] = true;
                $result[] = [
                    'ip' => $ip,
                    'reason' => 'Blacklist rule in Order Restrictions',
                    'created_at' => 'Active Setting',
                ];
            }
        }

        return $result;
    }
}
