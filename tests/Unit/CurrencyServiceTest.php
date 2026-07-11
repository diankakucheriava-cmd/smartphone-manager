<?php

namespace Tests\Unit;

use App\Enums\Currency;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    private const NBU_URL = 'bank.gov.ua/*';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'currency.fallback_usd_rate' => 41.5,
            'currency.fallback_eur_rate' => 45.0,
        ]);
    }

    public function test_usd_does_not_request_exchange_rates(): void
    {
        Http::fake();

        $result = app(CurrencyService::class)
            ->convert(100.456, Currency::USD);

        $this->assertSame(100.46, $result);

        Http::assertNothingSent();
    }

    public function test_rates_are_cached(): void
    {
        Http::fake([
            self::NBU_URL => Http::response([
                ['cc' => 'USD', 'rate' => 41.5],
                ['cc' => 'EUR', 'rate' => 45.0],
            ]),
        ]);

        $service = app(CurrencyService::class);

        $service->convert(100, Currency::UAH);
        $service->convert(100, Currency::EUR);
        $service->convert(200, Currency::UAH);

        Http::assertSentCount(1);

        $this->assertTrue(Cache::has('nbu_exchange_rates'));
    }

    public function test_uses_existing_cached_rates(): void
    {
        Cache::put('nbu_exchange_rates', [
            'USD' => 40.0,
            'EUR' => 50.0,
        ], now()->addDay());

        Http::fake();

        $result = app(CurrencyService::class)
            ->convert(100, Currency::EUR);

        $this->assertSame(80.0, $result);

        Http::assertNothingSent();
    }

    public function test_uses_fallback_when_nbu_returns_error(): void
    {
        Http::fake([
            self::NBU_URL => Http::response([], 500),
        ]);

        $result = app(CurrencyService::class)
            ->convert(100, Currency::UAH);

        $this->assertSame(4150.0, $result);
    }

    public function test_uses_fallback_when_required_rate_is_missing(): void
    {
        Http::fake([
            self::NBU_URL => Http::response([
                ['cc' => 'USD', 'rate' => 41.5],
            ]),
        ]);

        $result = app(CurrencyService::class)
            ->convert(100, Currency::EUR);

        $expected = round(100 * 41.5 / 45.0, 2);

        $this->assertSame($expected, $result);
    }

    public function test_fallback_rates_are_read_from_config(): void
    {
        config([
            'currency.fallback_usd_rate' => 40.0,
            'currency.fallback_eur_rate' => 50.0,
        ]);

        Http::fake([
            self::NBU_URL => Http::response([], 500),
        ]);

        $result = app(CurrencyService::class)
            ->convert(100, Currency::EUR);

        $this->assertSame(80.0, $result);
    }
}
