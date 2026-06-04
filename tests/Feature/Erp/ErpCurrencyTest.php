<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Currency;
use App\Erp\Models\ExchangeRate;
use App\Erp\Services\Currency\CurrencyService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ErpCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private CurrencyService $currencyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('erp_admin');

        $this->currencyService = app(CurrencyService::class);

        Currency::create(['code' => 'TRY', 'name' => 'Türk Lirası', 'symbol' => '₺', 'is_active' => true]);
        Currency::create(['code' => 'USD', 'name' => 'Amerikan Doları', 'symbol' => '$', 'is_active' => true]);
        Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_active' => true]);
    }

    private function addRate(string $from, string $to, float $rate, ?string $date = null): void
    {
        ExchangeRate::create([
            'from_currency' => $from,
            'to_currency'   => $to,
            'rate'          => $rate,
            'rate_date'     => $date ?? now()->toDateString(),
            'source'        => 'manual',
        ]);
    }

    public function test_same_currency_conversion_returns_same_amount(): void
    {
        $result = $this->currencyService->convert(1000, 'TRY', 'TRY', now());

        $this->assertEquals(1000.0, $result);
    }

    public function test_conversion_uses_stored_rate(): void
    {
        $this->addRate('USD', 'TRY', 32.50, '2026-06-01');

        $result = $this->currencyService->convert(100, 'USD', 'TRY', Carbon::parse('2026-06-01'));

        $this->assertEquals(3250.0, $result);
    }

    public function test_inverse_rate_applied_when_direct_missing(): void
    {
        // USD → TRY kuru var, TRY → USD direkt yok
        $this->addRate('USD', 'TRY', 32.50, '2026-06-01');

        $result = $this->currencyService->convert(3250, 'TRY', 'USD', Carbon::parse('2026-06-01'));

        $this->assertEqualsWithDelta(100.0, $result, 0.01);
    }

    public function test_manual_rate_can_be_saved(): void
    {
        $rate = $this->currencyService->saveManualRate('EUR', 'TRY', 35.00);

        $this->assertDatabaseHas('erp_exchange_rates', [
            'from_currency' => 'EUR',
            'to_currency'   => 'TRY',
            'rate'          => 35.00,
        ]);
    }

    public function test_to_functional_currency_returns_try(): void
    {
        $this->addRate('USD', 'TRY', 32.50, now()->toDateString());

        $result = $this->currencyService->toFunctionalCurrency(100, 'USD', now());

        $this->assertEquals(3250.0, $result);
    }

    public function test_to_functional_currency_try_unchanged(): void
    {
        $result = $this->currencyService->toFunctionalCurrency(500, 'TRY', now());

        $this->assertEquals(500.0, $result);
    }

    public function test_currencies_index_accessible(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.currencies.index'))
            ->assertOk();
    }

    public function test_viewer_cannot_add_exchange_rate(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole('erp_viewer');

        $this->actingAs($viewer, 'admin')
            ->post(route('erp.currencies.store-rate'), [
                'from_currency' => 'USD',
                'to_currency'   => 'TRY',
                'rate'          => 32.00,
                'rate_date'     => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_get_rate_falls_back_to_previous_day(): void
    {
        // Bugün için kur yok, dünkü kur var
        $yesterday = now()->subDay()->toDateString();
        $this->addRate('USD', 'TRY', 32.00, $yesterday);

        $rate = $this->currencyService->getRate('USD', 'TRY', now());

        $this->assertEquals(32.00, $rate);
    }
}
