<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Support\MoneyFormatter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $baseCurrency = MoneyFormatter::baseCurrency();
        $baseRate = (string) ($baseCurrency?->exchange_rate ?? '1.0000');

        $totalIncome = $this->sumIncome();
        $totalExpense = $this->sumExpense();
        $chartData = $this->buildSixMonthChart();
        $currencyBreakdown = $this->buildCurrencyBreakdown($baseRate);
        $recentTransactions = $this->recentTransactions();

        $netCashBase = '0.0000';
        foreach ($currencyBreakdown as $row) {
            $netCashBase = bcadd($netCashBase, (string) $row['balance_base'], 4);
        }

        return [
            'base_currency' => $baseCurrency,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_balance' => bcsub($totalIncome, $totalExpense, 4),
            'net_cash_assets' => $netCashBase,
            'currency_breakdown' => $currencyBreakdown,
            'recent_transactions' => $recentTransactions,
            'chart_labels' => $chartData['labels'],
            'chart_income' => $chartData['income'],
            'chart_expense' => $chartData['expense'],
            'chart_net' => $chartData['net'],
            'type_distribution' => $this->typeDistribution(),
            'account_balances' => $this->accountBalanceChart(),
        ];
    }

    private function sumIncome(): string
    {
        return bcadd((string) (Transaction::query()
            ->where('type', 'income')
            ->sum('amount') ?? '0'), '0', 4);
    }

    private function sumExpense(): string
    {
        return bcadd((string) (Transaction::query()
            ->where('type', 'expense')
            ->sum('amount') ?? '0'), '0', 4);
    }

    /**
     * @return array{labels: list<string>, income: list<string>, expense: list<string>, net: list<string>}
     */
    private function buildSixMonthChart(): array
    {
        $labels = [];
        $income = [];
        $expense = [];
        $net = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth()->toDateString();
            $end = $month->copy()->endOfMonth()->toDateString();

            $labels[] = $month->format('M Y');

            $monthIncome = bcadd((string) (Transaction::query()
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount') ?? '0'), '0', 4);

            $monthExpense = bcadd((string) (Transaction::query()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount') ?? '0'), '0', 4);

            $income[] = $monthIncome;
            $expense[] = $monthExpense;
            $net[] = bcsub($monthIncome, $monthExpense, 4);
        }

        return compact('labels', 'income', 'expense', 'net');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildCurrencyBreakdown(string $baseRate): array
    {
        return Account::query()
            ->with('currency')
            ->get()
            ->groupBy('currency_id')
            ->map(function (Collection $accounts) use ($baseRate): array {
                $currency = $accounts->first()?->currency;
                $nativeTotal = $accounts->reduce(
                    fn (string $carry, Account $account): string => bcadd($carry, (string) $account->current_balance, 4),
                    '0.0000'
                );
                $rate = (string) ($currency?->exchange_rate ?? '1.0000');
                $balanceBase = MoneyFormatter::convertToBase($nativeTotal, $rate);

                return [
                    'currency' => $currency,
                    'account_count' => $accounts->count(),
                    'balance_native' => $nativeTotal,
                    'balance_base' => $balanceBase,
                    'accounts' => $accounts,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function recentTransactions(): Collection
    {
        return Transaction::query()
            ->with(['account.currency', 'category', 'currency', 'contact'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }

    /**
     * @return array{labels: list<string>, values: list<string>}
     */
    private function typeDistribution(): array
    {
        $income = bcadd((string) (Transaction::query()->where('type', 'income')->sum('amount') ?? '0'), '0', 4);
        $expense = bcadd((string) (Transaction::query()->where('type', 'expense')->sum('amount') ?? '0'), '0', 4);
        $transfer = bcadd((string) (Transaction::query()->where('type', 'transfer')->sum('amount') ?? '0'), '0', 4);

        return [
            'labels' => ['Income', 'Expense', 'Transfers'],
            'values' => [$income, $expense, $transfer],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<string>, currencies: list<string>}
     */
    private function accountBalanceChart(): array
    {
        $accounts = Account::query()->with('currency')->orderByDesc('current_balance')->get();

        return [
            'labels' => $accounts->pluck('account_title')->all(),
            'values' => $accounts->map(fn (Account $a): string => (string) $a->current_balance)->all(),
            'currencies' => $accounts->map(fn (Account $a): string => $a->currency?->code ?? '—')->all(),
        ];
    }
}
