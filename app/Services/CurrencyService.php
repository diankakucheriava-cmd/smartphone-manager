<?php

namespace App\Services;

use App\Enums\Currency;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    private const NBU_API_URL =
    'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?format=json';

    private const CACHE_KEY = 'nbu_exchange_rates';

    public function convert(float $amountUsd, Currency $to): float
    {
        if ($to === Currency::USD) {
            return round($amountUsd, 2);
        }

        $rates = $this->getRates();

        $amountUah = $amountUsd * $rates[Currency::USD->value];

        if ($to === Currency::UAH) {
            return round($amountUah, 2);
        }

        return round(
            $amountUah / $rates[Currency::EUR->value],
            2
        );
    }

    private function getRates(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addDay(),
            fn() => $this->fetchFromNbu()
        );
    }

    private function fetchFromNbu(): array
    {
        try {
            $response = Http::timeout(10)
                ->get(self::NBU_API_URL);

            if ($response->failed()) {
                return $this->fallbackRates();
            }

            $rates = collect($response->json())
                ->whereIn('cc', [
                    Currency::USD->value,
                    Currency::EUR->value,
                ])
                ->pluck('rate', 'cc')
                ->map(fn($rate) => (float) $rate)
                ->toArray();

            if (
                !isset(
                    $rates[Currency::USD->value],
                    $rates[Currency::EUR->value]
                )
            ) {
                return $this->fallbackRates();
            }

            return $rates;
        } catch (ConnectionException) {
            return $this->fallbackRates();
        }
    }

    private function fallbackRates(): array
    {
        return [
            Currency::USD->value =>
            (float) config('currency.fallback_usd_rate', 41.5),

            Currency::EUR->value =>
            (float) config('currency.fallback_eur_rate', 45.0),
        ];
    }
}
