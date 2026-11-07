<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Update\SystemUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SystemUpdateController extends Controller
{
    public function index(SystemUpdateService $updater): View
    {
        $currentVersion = (string) config('version.version', '1.0.0');
        $buildDate = (string) config('version.release_date', '2026-10-19');
        $updateInfo = $updater->checkUpdate();

        return view('admin.system-update.index', [
            'currentVersion' => $currentVersion,
            'buildDate' => $buildDate,
            'updateInfo' => $updateInfo,
            'phpVersion' => PHP_VERSION,
            'isZipLoaded' => class_exists(\ZipArchive::class),
            'githubRepo' => config('version.github_repo'),
            'autoUpdateEnabled' => config('version.auto_update_enabled'),
        ]);
    }

    public function applyZip(Request $request, SystemUpdateService $updater): RedirectResponse
    {
        $request->validate([
            'update_zip' => ['required', 'file', 'mimes:zip', 'max:102400'], // 100MB limit
        ]);

        $result = $updater->applyZipUpdate($request->file('update_zip'));

        if (! $result['ok']) {
            return redirect()
                ->route('admin.system-update.index')
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.system-update.index')
            ->with('success', $result['message']);
    }

    public function applyOnline(Request $request, SystemUpdateService $updater): RedirectResponse
    {
        $validated = $request->validate([
            'download_url' => ['required', 'url'],
        ]);

        $tempPath = storage_path('app/temp_update_'.time().'.zip');

        try {
            if (! $updater->downloadPackage($validated['download_url'], $tempPath)) {
                @unlink($tempPath);

                return redirect()
                    ->route('admin.system-update.index')
                    ->with('error', 'Could not download the update package from release source.');
            }

            $result = $updater->applyZipUpdate($tempPath);

            @unlink($tempPath);

            if (! $result['ok']) {
                return redirect()
                    ->route('admin.system-update.index')
                    ->with('error', $result['message']);
            }

            return redirect()
                ->route('admin.system-update.index')
                ->with('success', $result['message']);
        } catch (Throwable $e) {
            @unlink($tempPath);

            return redirect()
                ->route('admin.system-update.index')
                ->with('error', 'Online update failed: '.$e->getMessage());
        }
    }
}
