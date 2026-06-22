<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\BalanceTrendPeriod;
use App\Support\TransactionType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class BalanceTrendChartBuilder
{
    public const MAX_POINTS = 120;

    /**
     * @return array{
     *     labels: list<string>,
     *     values: list<float>,
     *     meta: array{
     *         period: string,
     *         period_label: string,
     *         transaction_count: int,
     *         point_count: int,
     *         bucket: string
     *     }
     * }
     */
    public function build(Builder $query, string $period): array
    {
        $period = BalanceTrendPeriod::resolve($period);
        $startDate = BalanceTrendPeriod::startDate($period);

        $transactions = (clone $query)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get(['transaction_date', 'type', 'amount']);

        if ($transactions->isEmpty()) {
            return $this->emptyResult($period);
        }

        $running = '0.0000';
        $rawPoints = [];

        foreach ($transactions as $transaction) {
            $running = TransactionType::contactBalanceDelta(
                $running,
                (string) $transaction->type,
                (string) $transaction->amount
            );

            $rawPoints[] = [
                'date' => $transaction->transaction_date->copy()->startOfDay(),
                'value' => $running,
            ];
        }

        $points = $this->clipToPeriod($rawPoints, $startDate);
        $normalized = $this->normalizePoints($points, $period);
        $normalized = $this->ensureMinChartPoints($normalized, $points, $rawPoints);

        return [
            'labels' => array_map(static fn (array $point): string => $point['label'], $normalized),
            'values' => array_map(static fn (array $point): float => (float) $point['value'], $normalized),
            'meta' => [
                'period' => $period,
                'period_label' => BalanceTrendPeriod::label($period),
                'transaction_count' => $transactions->count(),
                'point_count' => count($normalized),
                'bucket' => $this->describeBucket($normalized, $period, count($rawPoints)),
            ],
        ];
    }

    /**
     * @param list<array{date: Carbon, value: string}> $rawPoints
     * @return list<array{date: Carbon, value: string}>
     */
    private function clipToPeriod(array $rawPoints, ?Carbon $startDate): array
    {
        if ($startDate === null) {
            return $rawPoints;
        }

        $opening = '0.0000';
        $inPeriod = [];

        foreach ($rawPoints as $point) {
            if ($point['date']->lt($startDate)) {
                $opening = $point['value'];
            } else {
                $inPeriod[] = $point;
            }
        }

        if ($inPeriod === []) {
            return [[
                'date' => $startDate->copy(),
                'value' => $opening,
            ]];
        }

        return array_merge(
            [[
                'date' => $startDate->copy(),
                'value' => $opening,
            ]],
            $inPeriod
        );
    }

    /**
     * @param list<array{date: Carbon, value: string}> $points
     * @return list<array{date: Carbon, value: string, label: string}>
     */
    private function normalizePoints(array $points, string $period): array
    {
        if ($points === []) {
            return [];
        }

        if (count($points) <= self::MAX_POINTS) {
            return $this->asTransactionPoints($points);
        }

        $daily = $this->bucketPoints($points, 'day');

        if (count($daily) <= self::MAX_POINTS) {
            return $daily;
        }

        $coarseBucket = $this->resolveCoarseBucket($period, $daily);
        $bucketed = $this->rebucketDailyPoints($daily, $coarseBucket);

        if (count($bucketed) <= self::MAX_POINTS) {
            return $bucketed;
        }

        return $this->limitPoints($bucketed, self::MAX_POINTS);
    }

    /**
     * @param list<array{date: Carbon, value: string}> $points
     * @return list<array{date: Carbon, value: string, label: string}>
     */
    private function asTransactionPoints(array $points): array
    {
        $perDay = [];

        foreach ($points as $point) {
            $dayKey = $point['date']->format('Y-m-d');
            $perDay[$dayKey] = ($perDay[$dayKey] ?? 0) + 1;
        }

        $seenPerDay = [];
        $result = [];

        foreach ($points as $point) {
            $dayKey = $point['date']->format('Y-m-d');
            $seenPerDay[$dayKey] = ($seenPerDay[$dayKey] ?? 0) + 1;
            $sequence = $seenPerDay[$dayKey];

            $label = ($perDay[$dayKey] ?? 0) > 1
                ? $point['date']->format('M j, Y').' · '.$sequence
                : $point['date']->format('M j, Y');

            $result[] = [
                'date' => $point['date'],
                'value' => $point['value'],
                'label' => $label,
            ];
        }

        return $result;
    }

    /**
     * Line charts need at least two points to render a visible trend.
     *
     * @param list<array{date: Carbon, value: string, label: string}> $normalized
     * @param list<array{date: Carbon, value: string}> $points
     * @param list<array{date: Carbon, value: string}> $rawPoints
     * @return list<array{date: Carbon, value: string, label: string}>
     */
    private function ensureMinChartPoints(array $normalized, array $points, array $rawPoints): array
    {
        if (count($normalized) !== 1) {
            return $normalized;
        }

        $only = $normalized[0];
        $opening = $only['value'];
        $transactionsOnDay = 0;

        foreach ($rawPoints as $point) {
            if ($point['date']->equalTo($only['date'])) {
                $transactionsOnDay++;
            }
        }

        if ($transactionsOnDay > 0 && count($points) === 1) {
            $opening = '0.0000';

            foreach ($rawPoints as $point) {
                if ($point['date']->lt($only['date'])) {
                    $opening = $point['value'];
                }
            }
        }

        $anchorDate = $only['date']->copy()->subDay();

        return [
            [
                'date' => $anchorDate,
                'value' => $opening,
                'label' => $anchorDate->format('M j, Y'),
            ],
            $only,
        ];
    }

    /**
     * @param list<array{date: Carbon, value: string, label: string}> $daily
     */
    private function resolveCoarseBucket(string $period, array $daily): string
    {
        return match ($period) {
            BalanceTrendPeriod::DAYS_7, BalanceTrendPeriod::DAYS_30 => 'day',
            BalanceTrendPeriod::DAYS_90 => 'week',
            BalanceTrendPeriod::YEAR_1 => 'month',
            default => $this->lifetimeBucket($daily),
        };
    }

    /**
     * @param list<array{date: Carbon, value: string, label: string}> $daily
     */
    private function lifetimeBucket(array $daily): string
    {
        $first = $daily[0]['date'];
        $last = $daily[array_key_last($daily)]['date'];
        $days = $first->diffInDays($last);

        if ($days <= 90) {
            return 'day';
        }

        if ($days <= 730) {
            return 'week';
        }

        return 'month';
    }

    /**
     * @param list<array{date: Carbon, value: string}> $points
     * @return list<array{date: Carbon, value: string, label: string}>
     */
    private function bucketPoints(array $points, string $bucket): array
    {
        $groups = [];

        foreach ($points as $point) {
            $key = match ($bucket) {
                'day' => $point['date']->format('Y-m-d'),
                'week' => $point['date']->copy()->startOfWeek()->format('Y-m-d'),
                'month' => $point['date']->format('Y-m'),
            };

            $groups[$key] = $point;
        }

        $result = [];

        foreach ($groups as $point) {
            $result[] = [
                'date' => $point['date'],
                'value' => $point['value'],
                'label' => $this->formatLabel($point['date'], $bucket),
            ];
        }

        return $result;
    }

    /**
     * @param list<array{date: Carbon, value: string, label: string}> $daily
     * @return list<array{date: Carbon, value: string, label: string}>
     */
    private function rebucketDailyPoints(array $daily, string $bucket): array
    {
        if ($bucket === 'day') {
            return $daily;
        }

        $groups = [];

        foreach ($daily as $point) {
            $key = match ($bucket) {
                'week' => $point['date']->copy()->startOfWeek()->format('Y-m-d'),
                'month' => $point['date']->format('Y-m'),
            };

            $groups[$key] = $point;
        }

        $result = [];

        foreach ($groups as $point) {
            $result[] = [
                'date' => $point['date'],
                'value' => $point['value'],
                'label' => $this->formatLabel($point['date'], $bucket),
            ];
        }

        return $result;
    }

    private function formatLabel(Carbon $date, string $bucket): string
    {
        return match ($bucket) {
            'day' => $date->format('M j, Y'),
            'week' => 'W/C '.$date->copy()->startOfWeek()->format('M j, Y'),
            'month' => $date->format('M Y'),
        };
    }

    /**
     * @param list<array{date: Carbon, value: string, label: string}> $points
     * @return list<array{date: Carbon, value: string, label: string}>
     */
    private function limitPoints(array $points, int $max): array
    {
        $count = count($points);

        if ($count <= $max) {
            return $points;
        }

        $result = [];
        $step = ($count - 1) / ($max - 1);

        for ($i = 0; $i < $max; $i++) {
            $result[] = $points[(int) round($i * $step)];
        }

        return $result;
    }

    /**
     * @param list<array{date: Carbon, value: string, label: string}> $normalized
     */
    private function describeBucket(array $normalized, string $period, int $transactionCount): string
    {
        if ($transactionCount <= self::MAX_POINTS && count($normalized) === $transactionCount) {
            return 'per transaction';
        }

        if ($period === BalanceTrendPeriod::DAYS_7 || $period === BalanceTrendPeriod::DAYS_30) {
            return 'daily';
        }

        if ($period === BalanceTrendPeriod::DAYS_90) {
            return 'weekly';
        }

        if ($period === BalanceTrendPeriod::YEAR_1) {
            return 'monthly';
        }

        return $this->lifetimeBucketLabel($normalized);
    }

    /**
     * @param list<array{date: Carbon, value: string, label: string}> $normalized
     */
    private function lifetimeBucketLabel(array $normalized): string
    {
        if ($normalized === []) {
            return 'daily';
        }

        return match ($this->lifetimeBucket($normalized)) {
            'day' => 'daily',
            'week' => 'weekly',
            default => 'monthly',
        };
    }

    /**
     * @return array{
     *     labels: list<string>,
     *     values: list<float>,
     *     meta: array{
     *         period: string,
     *         period_label: string,
     *         transaction_count: int,
     *         point_count: int,
     *         bucket: string
     *     }
     * }
     */
    private function emptyResult(string $period): array
    {
        return [
            'labels' => [],
            'values' => [],
            'meta' => [
                'period' => $period,
                'period_label' => BalanceTrendPeriod::label($period),
                'transaction_count' => 0,
                'point_count' => 0,
                'bucket' => 'daily',
            ],
        ];
    }
}
