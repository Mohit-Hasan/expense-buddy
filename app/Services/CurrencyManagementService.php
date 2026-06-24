<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CurrencyManagementService
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Currency
    {
        $exchangeRate = bcadd((string) $data['exchange_rate'], '0', 4);

        if (bccomp($exchangeRate, '0', 4) <= 0) {
            throw new InvalidArgumentException('Exchange rate must be greater than zero.');
        }

        return Currency::query()->create([
                'name' => (string) $data['name'],
                'code' => strtoupper((string) $data['code']),
                'symbol' => (string) $data['symbol'],
                'exchange_rate' => $exchangeRate,
                'is_default' => false,
            ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): Currency
    {
        $currency = Currency::query()->find($id);

        if ($currency === null) {
            throw new InvalidArgumentException("Currency [{$id}] not found.");
        }

        $exchangeRate = bcadd((string) $data['exchange_rate'], '0', 4);

        if (bccomp($exchangeRate, '0', 4) <= 0) {
            throw new InvalidArgumentException('Exchange rate must be greater than zero.');
        }

        return DB::transaction(function () use ($currency, $data, $exchangeRate): Currency {
            $currency->fill([
                'name' => (string) $data['name'],
                'code' => strtoupper((string) $data['code']),
                'symbol' => (string) $data['symbol'],
                'exchange_rate' => $currency->is_default ? '1.0000' : $exchangeRate,
            ]);

            $currency->save();

            return $currency->fresh();
        });
    }

    public function delete(int $id): void
    {
        $currency = Currency::query()->find($id);

        if ($currency === null) {
            throw new InvalidArgumentException("Currency [{$id}] not found.");
        }

        if ($currency->is_default) {
            throw new InvalidArgumentException('Cannot delete the default base currency.');
        }

        if ($currency->accounts()->exists() || $currency->transactions()->exists()) {
            throw new InvalidArgumentException('Currency is in use by accounts or transactions.');
        }

        $currency->delete();
    }
}
