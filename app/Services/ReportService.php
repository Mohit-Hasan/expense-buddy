<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Support\MoneyFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array{rows: Collection<int, object>, chart: array<string, mixed>}
     */
    public function incomeVsExpenseSummary(int $months = 12): array
    {
        $periodExpression = $this->periodExpression();
        $cutoff = now()->subMonths($months)->startOfMonth()->toDateString();

        $rows = Transaction::query()
            ->selectRaw("{$periodExpression} as period")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount WHEN type = 'transfer' AND id < transfer_reference_id THEN amount ELSE 0 END) as total_expense")
            ->where('transaction_date', '>=', $cutoff)
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function (object $row): object {
                $income = bcadd((string) $row->total_income, '0', 4);
                $expense = bcadd((string) $row->total_expense, '0', 4);

                return (object) [
                    'period' => (string) $row->period,
                    'total_income' => $income,
                    'total_expense' => $expense,
                    'net_margin' => bcsub($income, $expense, 4),
                ];
            });

        return [
            'rows' => $rows,
            'chart' => [
                'labels' => $rows->pluck('period')->values()->all(),
                'income' => $rows->pluck('total_income')->values()->all(),
                'expense' => $rows->pluck('total_expense')->values()->all(),
                'margin' => $rows->pluck('net_margin')->values()->all(),
            ],
        ];
    }

    /**
     * @return array{rows: Collection<int, object>, charts: array<string, mixed>}
     */
    public function categorizedBreakdown(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = Transaction::query()
            ->select([
                'transaction_categories.id as category_id',
                'transaction_categories.name as category_name',
                'transaction_categories.type as category_type',
            ])
            ->selectRaw('SUM(transactions.amount) as total_amount')
            ->leftJoin('transaction_categories', 'transaction_categories.id', '=', 'transactions.category_id')
            ->whereIn('transactions.type', ['income', 'expense'])
            ->groupBy(
                'transaction_categories.id',
                'transaction_categories.name',
                'transaction_categories.type'
            )
            ->orderByDesc('total_amount');

        if ($dateFrom !== null) {
            $query->whereDate('transactions.transaction_date', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->whereDate('transactions.transaction_date', '<=', $dateTo);
        }

        $rows = $query->get()->map(function (object $row): object {
            return (object) [
                'category_id' => $row->category_id !== null ? (int) $row->category_id : null,
                'category_name' => $row->category_name ?? 'Uncategorized',
                'category_type' => (string) ($row->category_type ?? 'unknown'),
                'total_amount' => bcadd((string) $row->total_amount, '0', 4),
            ];
        });

        $incomeRows = $rows->where('category_type', 'income')->values();
        $expenseRows = $rows->where('category_type', 'expense')->values();

        return [
            'rows' => $rows,
            'charts' => [
                'income' => [
                    'labels' => $incomeRows->pluck('category_name')->all(),
                    'values' => $incomeRows->pluck('total_amount')->all(),
                ],
                'expense' => [
                    'labels' => $expenseRows->pluck('category_name')->all(),
                    'values' => $expenseRows->pluck('total_amount')->all(),
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     transactions: Collection<int, Transaction>,
     *     summary: array<string, string|int>,
     *     chart: array<string, mixed>,
     *     contacts: Collection<int, \App\Models\Contact>
     * }
     */
    public function lendingOverviewLedger(): array
    {
        $contacts = \App\Models\Contact::query()
            ->where('status', 'active')
            ->orderByDesc('current_balance')
            ->orderBy('name')
            ->get();

        $transactions = Transaction::query()
            ->with(['account.currency', 'category', 'paymentMethod', 'currency', 'contact'])
            ->whereNotNull('contact_id')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $totalOutstanding = $contacts->reduce(
            fn (string $carry, \App\Models\Contact $contact): string => bcadd($carry, (string) $contact->current_balance, 4),
            '0.0000'
        );

        $withBalance = $contacts->filter(
            fn (\App\Models\Contact $contact): bool => bccomp((string) $contact->current_balance, '0', 4) > 0
        )->count();

        return [
            'contacts' => $contacts,
            'transactions' => $transactions,
            'summary' => [
                'total_outstanding' => $totalOutstanding,
                'people_with_balance' => $withBalance,
                'total_people' => $contacts->count(),
                'recent_activity_count' => $transactions->count(),
            ],
            'chart' => [
                'labels' => $contacts->take(8)->pluck('name')->values()->all(),
                'values' => $contacts->take(8)->pluck('current_balance')->map(fn ($v) => (string) $v)->values()->all(),
            ],
        ];
    }

    /**
     * @return array{
     *     transactions: Collection<int, Transaction>,
     *     summary: array<string, string>,
     *     chart: array<string, mixed>
     * }
     */
    public function contactBalanceLedger(int $contactId): array
    {
        $transactions = Transaction::query()
            ->with(['account.currency', 'category', 'paymentMethod', 'currency'])
            ->where('contact_id', $contactId)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $running = '0.0000';
        $chartLabels = [];
        $chartValues = [];

        foreach ($transactions as $transaction) {
            $amount = (string) $transaction->amount;
            $running = $this->applyContactBalanceDelta($running, $transaction->type, $amount);

            $chartLabels[] = $transaction->transaction_date->format('M d');
            $chartValues[] = $running;
        }

        $totalVolume = $transactions->reduce(
            fn (string $carry, Transaction $t): string => bcadd($carry, (string) $t->amount, 4),
            '0.0000'
        );

        return [
            'transactions' => $transactions,
            'summary' => [
                'total_volume' => $totalVolume,
                'transaction_count' => (string) $transactions->count(),
                'current_balance' => $running,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'values' => $chartValues,
            ],
        ];
    }

    private function applyContactBalanceDelta(string $running, string $type, string $amount): string
    {
        return match ($type) {
            'lending' => bcadd($running, $amount, 4),
            'income' => bcsub($running, $amount, 4),
            default => $running,
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function currencyExposureReport(): array
    {
        $baseCurrency = MoneyFormatter::baseCurrency();

        $groups = Account::query()
            ->with('currency')
            ->get()
            ->groupBy('currency_id')
            ->map(function (Collection $accounts) use ($baseCurrency): array {
                $currency = $accounts->first()?->currency;
                $native = $accounts->reduce(
                    fn (string $c, Account $a): string => bcadd($c, (string) $a->current_balance, 4),
                    '0.0000'
                );
                $rate = (string) ($currency?->exchange_rate ?? '1.0000');

                return [
                    'currency' => $currency,
                    'accounts' => $accounts->count(),
                    'balance_native' => $native,
                    'balance_base' => MoneyFormatter::convertToBase($native, $rate),
                    'share_percent' => '0.00',
                ];
            })
            ->values();

        $totalBase = $groups->reduce(
            fn (string $c, array $row): string => bcadd($c, (string) $row['balance_base'], 4),
            '0.0000'
        );

        return $groups->map(function (array $row) use ($totalBase): array {
            if (bccomp($totalBase, '0', 4) > 0) {
                $row['share_percent'] = bcmul(
                    bcdiv((string) $row['balance_base'], $totalBase, 6),
                    '100',
                    2
                );
            }

            return $row;
        })->all();
    }

    /**
     * @return array{
     *     summary: array<string, string>,
     *     timeSeries: array<string, mixed>,
     *     categoryBars: array<string, mixed>,
     *     categoryByPeriod: array<string, mixed>,
     *     typeBreakdown: array<string, mixed>,
     *     periodRows: Collection<int, object>,
     *     categoryRows: Collection<int, object>
     * }
     */
    public function detailedAnalytics(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $groupBy = 'month',
    ): array {
        $groupBy = in_array($groupBy, ['day', 'week', 'month'], true) ? $groupBy : 'month';
        $periodExpression = $this->groupByExpression($groupBy);

        $timeQuery = Transaction::query()
            ->selectRaw("{$periodExpression} as period")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount WHEN type = 'transfer' AND id < transfer_reference_id THEN amount ELSE 0 END) as total_expense")
            ->selectRaw("SUM(CASE WHEN type = 'lending' THEN amount ELSE 0 END) as total_lending")
            ->groupBy('period')
            ->orderBy('period');

        $this->applyDateRange($timeQuery, $dateFrom, $dateTo, 'transaction_date');

        $periodRows = $timeQuery->get()->map(function (object $row): object {
            $income = bcadd((string) $row->total_income, '0', 4);
            $expense = bcadd((string) $row->total_expense, '0', 4);
            $lending = bcadd((string) $row->total_lending, '0', 4);

            return (object) [
                'period' => (string) $row->period,
                'total_income' => $income,
                'total_expense' => $expense,
                'total_lending' => $lending,
                'net_margin' => bcsub($income, $expense, 4),
            ];
        });

        $totalIncome = $periodRows->reduce(
            fn (string $carry, object $row): string => bcadd($carry, $row->total_income, 4),
            '0.0000'
        );
        $totalExpense = $periodRows->reduce(
            fn (string $carry, object $row): string => bcadd($carry, $row->total_expense, 4),
            '0.0000'
        );
        $totalLending = $periodRows->reduce(
            fn (string $carry, object $row): string => bcadd($carry, $row->total_lending, 4),
            '0.0000'
        );

        $categoryData = $this->categorizedBreakdown($dateFrom, $dateTo);
        $categoryRows = $categoryData['rows'];
        $incomeCategories = $categoryRows->where('category_type', 'income')->values();
        $expenseCategories = $categoryRows->where('category_type', 'expense')->values();

        $topCategoryIds = $categoryRows
            ->sortByDesc(fn (object $row): float => (float) $row->total_amount)
            ->take(8)
            ->pluck('category_id')
            ->filter()
            ->values()
            ->all();

        $categoryByPeriod = $this->categoryByPeriodChart($periodExpression, $topCategoryIds, $dateFrom, $dateTo);

        return [
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'total_lending' => $totalLending,
                'net_margin' => bcsub($totalIncome, $totalExpense, 4),
                'period_count' => (string) $periodRows->count(),
            ],
            'timeSeries' => [
                'labels' => $periodRows->pluck('period')->values()->all(),
                'income' => $periodRows->pluck('total_income')->values()->all(),
                'expense' => $periodRows->pluck('total_expense')->values()->all(),
                'lending' => $periodRows->pluck('total_lending')->values()->all(),
                'margin' => $periodRows->pluck('net_margin')->values()->all(),
            ],
            'categoryBars' => [
                'income' => [
                    'labels' => $incomeCategories->pluck('category_name')->all(),
                    'values' => $incomeCategories->pluck('total_amount')->all(),
                ],
                'expense' => [
                    'labels' => $expenseCategories->pluck('category_name')->all(),
                    'values' => $expenseCategories->pluck('total_amount')->all(),
                ],
            ],
            'categoryByPeriod' => $categoryByPeriod,
            'typeBreakdown' => [
                'labels' => ['Income', 'Expense', 'Lending'],
                'values' => [$totalIncome, $totalExpense, $totalLending],
            ],
            'periodRows' => $periodRows,
            'categoryRows' => $categoryRows,
        ];
    }

    /**
     * @param list<int|null> $categoryIds
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    private function categoryByPeriodChart(
        string $periodExpression,
        array $categoryIds,
        ?string $dateFrom,
        ?string $dateTo,
    ): array {
        if ($categoryIds === []) {
            return ['labels' => [], 'datasets' => []];
        }

        $query = Transaction::query()
            ->selectRaw("{$periodExpression} as period")
            ->selectRaw('COALESCE(transaction_categories.name, \'Uncategorized\') as category_name')
            ->selectRaw('SUM(transactions.amount) as total_amount')
            ->leftJoin('transaction_categories', 'transaction_categories.id', '=', 'transactions.category_id')
            ->whereIn('transactions.type', ['income', 'expense'])
            ->whereIn('transactions.category_id', $categoryIds)
            ->groupByRaw("{$periodExpression}, COALESCE(transaction_categories.name, 'Uncategorized')")
            ->orderBy('period');

        $this->applyDateRange($query, $dateFrom, $dateTo, 'transactions.transaction_date');

        $rows = $query->get();
        $labels = $rows->pluck('period')->unique()->sort()->values()->all();
        $categories = $rows->pluck('category_name')->unique()->values()->all();

        $lookup = [];
        foreach ($rows as $row) {
            $lookup[(string) $row->period][(string) $row->category_name] = bcadd((string) $row->total_amount, '0', 4);
        }

        $datasets = [];
        foreach ($categories as $index => $categoryName) {
            $datasets[] = [
                'label' => $categoryName,
                'data' => array_map(
                    fn (string $period): string => $lookup[$period][$categoryName] ?? '0.0000',
                    $labels
                ),
                'colorIndex' => $index,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<Transaction> $query
     */
    private function applyDateRange($query, ?string $dateFrom, ?string $dateTo, string $column): void
    {
        if ($dateFrom !== null) {
            $query->whereDate($column, '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->whereDate($column, '<=', $dateTo);
        }
    }

    private function periodExpression(): string
    {
        return $this->groupByExpression('month');
    }

    private function groupByExpression(string $groupBy): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($groupBy) {
            'day' => match ($driver) {
                'mysql' => "DATE_FORMAT(transaction_date, '%Y-%m-%d')",
                'pgsql' => "to_char(transaction_date, 'YYYY-MM-DD')",
                default => "strftime('%Y-%m-%d', transaction_date)",
            },
            'week' => match ($driver) {
                'mysql' => "DATE_FORMAT(transaction_date, '%x-W%v')",
                'pgsql' => "to_char(transaction_date, 'IYYY-\"W\"IW')",
                default => "strftime('%Y-W%W', transaction_date)",
            },
            default => match ($driver) {
                'mysql' => "DATE_FORMAT(transaction_date, '%Y-%m')",
                'pgsql' => "to_char(transaction_date, 'YYYY-MM')",
                default => "strftime('%Y-%m', transaction_date)",
            },
        };
    }
}
