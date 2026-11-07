<?php

namespace App\Services;

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Display the Reports and Analytics Dashboard.
     */
    public function index(Request $request): View
    {
        $range = $request->string('range', '30_days')->toString();
        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        $dateRange = $this->reportService->resolveDateRange($range, $from, $to);
        $metrics = $this->reportService->getMetrics(
            $dateRange['start'],
            $dateRange['end'],
            $dateRange['range'],
            $dateRange['label']
        );

        return view('admin.reports.index', [
            'metrics' => $metrics,
            'activeRange' => $dateRange['range'],
            'rangeLabel' => $dateRange['label'],
            'startDate' => $dateRange['start']->toDateString(),
            'endDate' => $dateRange['end']->toDateString(),
        ]);
    }

    /**
     * Stream CSV export of report orders.
     */
    public function export(Request $request): StreamedResponse
    {
        $range = $request->string('range', '30_days')->toString();
        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        $dateRange = $this->reportService->resolveDateRange($range, $from, $to);

        return $this->reportService->exportCsv(
            $dateRange['start'],
            $dateRange['end'],
            $dateRange['label']
        );
    }

    /**
     * Generate on-demand AI executive summary using Gemini AI.
     */
    public function generateAiInsight(Request $request): JsonResponse
    {
        $range = $request->string('range', '30_days')->toString();
        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;
        $language = in_array($request->string('language')->toString(), ['bn', 'en'], true)
            ? $request->string('language')->toString()
            : 'en';
        $question = $request->string('question')->toString() ?: null;

        $dateRange = $this->reportService->resolveDateRange($range, $from, $to);
        $metrics = $this->reportService->getMetrics(
            $dateRange['start'],
            $dateRange['end'],
            $dateRange['range'],
            $dateRange['label']
        );

        $insight = $this->reportService->generateAiInsight(
            $metrics,
            $language,
            $question
        );

        return response()->json([
            'ok' => true,
            'rangeLabel' => $dateRange['label'],
            'language' => $language,
            'insight' => $insight,
        ]);
    }
}
