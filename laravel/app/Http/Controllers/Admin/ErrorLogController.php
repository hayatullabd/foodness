<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ErrorLogViewer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class ErrorLogController extends Controller
{
    public function __construct(private ErrorLogViewer $errorLogViewer) {}

    public function index(): View
    {
        return view('admin.error-log.index', [
            'log' => $this->errorLogViewer->status(),
        ]);
    }

    public function writeTest(): RedirectResponse
    {
        try {
            $this->errorLogViewer->writeTestEntry();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.error-log.index')
                ->with('error', 'Failed to write test log: '.$exception->getMessage());
        }

        return redirect()
            ->route('admin.error-log.index')
            ->with('success', 'Test log entry written successfully.');
    }

    public function createFile(): RedirectResponse
    {
        try {
            $this->errorLogViewer->ensureFileExists();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.error-log.index')
                ->with('error', 'Failed to create log file: '.$exception->getMessage());
        }

        return redirect()
            ->route('admin.error-log.index')
            ->with('success', 'Log file is ready at storage/logs/laravel.log.');
    }

    public function clear(): RedirectResponse
    {
        try {
            $this->errorLogViewer->clear();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.error-log.index')
                ->with('error', 'Failed to clear log file: '.$exception->getMessage());
        }

        return redirect()
            ->route('admin.error-log.index')
            ->with('success', 'Log file cleared successfully.');
    }
}
