<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Support\MoneyFormatter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {
    }

    public function incomeVsExpense(): View
    {
        $data = $this->reportService->incomeVsExpenseSummary();

        return view('reports.income-vs-expense', [
            'rows' => $data['rows'],
            'chart' => $data['chart'],
            'currencyExposure' => $this->reportService->currencyExposureReport(),
            'baseCurrency' => MoneyFormatter::baseCurrency(),
        ]);
    }

    public function categorized(Request $request): View
    {
        $data = $this->reportService->categorizedBreakdown(
            $request->input('date_from'),
            $request->input('date_to')
        );

        return view('reports.categorized', [
            'rows' => $data['rows'],
            'charts' => $data['charts'],
            'filters' => $request->only(['date_from', 'date_to']),
            'baseCurrency' => MoneyFormatter::baseCurrency(),
        ]);
    }

    public function detailed(Request $request): View
    {
        $dateFrom = $request->input('date_from') ?: now()->subMonths(11)->startOfMonth()->toDateString();
        $dateTo = $request->input('date_to') ?: now()->toDateString();
        $groupBy = $request->input('group_by', 'month');

        $data = $this->reportService->detailedAnalytics($dateFrom, $dateTo, $groupBy);

        return view('reports.detailed', [
            'summary' => $data['summary'],
            'timeSeries' => $data['timeSeries'],
            'categoryBars' => $data['categoryBars'],
            'categoryByPeriod' => $data['categoryByPeriod'],
            'typeBreakdown' => $data['typeBreakdown'],
            'periodRows' => $data['periodRows'],
            'categoryRows' => $data['categoryRows'],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'group_by' => $groupBy,
            ],
            'baseCurrency' => MoneyFormatter::baseCurrency(),
        ]);
    }
}
