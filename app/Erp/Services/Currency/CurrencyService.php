<?php

namespace App\Erp\Services\Currency;

use App\Erp\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    private string $functional;

    public function __construct()
    {
        $this->functional = (string) config('erp.currency', 'TRY');
    }

    public function convert(float $amount, string $from, string $to, Carbon $date): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getRate($from, $to, $date);

        return round($amount * $rate, 2);
    }

    public function toFunctionalCurrency(float $amount, string $currency, Carbon $date): float
    {
        return $this->convert($amount, $currency, $this->functional, $date);
    }

    public function getRate(string $from, string $to, Carbon $date): float
    {
        if ($from === $to) {
            return 1.0;
        }

        // Doğrudan kur
        $rate = $this->findRate($from, $to, $date);
        if ($rate) {
            return (float) $rate->rate;
        }

        // Ters kur
        $inverse = $this->findRate($to, $from, $date);
        if ($inverse && (float) $inverse->rate > 0) {
            return round(1 / (float) $inverse->rate, 6);
        }

        // TRY üzerinden çapraz kur
        if ($from !== 'TRY' && $to !== 'TRY') {
            $fromTry = $this->getRate($from, 'TRY', $date);
            $toTry   = $this->getRate($to,   'TRY', $date);

            if ($fromTry > 0 && $toTry > 0) {
                return round($fromTry / $toTry, 6);
            }
        }

        Log::warning("CurrencyService: no rate found for {$from}/{$to} on {$date->toDateString()}, using 1.0");

        return 1.0;
    }

    /**
     * TCMB XML'den günlük kurları çek ve kaydet.
     * Endpoint: https://www.tcmb.gov.tr/kurlar/today.xml
     */
    public function fetchTcmbRates(): int
    {
        try {
            $response = Http::timeout(30)->get('https://www.tcmb.gov.tr/kurlar/today.xml');

            if (! $response->successful()) {
                Log::error('TCMB kurları alınamadı', ['status' => $response->status()]);

                return 0;
            }

            $xml    = simplexml_load_string($response->body());
            $date   = Carbon::parse((string) ($xml->attributes()['Date'] ?? now()));
            $saved  = 0;

            foreach ($xml->Currency as $currency) {
                $code      = (string) ($currency->attributes()['CurrencyCode'] ?? '');
                $banknoteSelling = (float) str_replace(',', '.', (string) $currency->BanknoteSelling);
                $unit      = (int) ($currency->Unit ?? 1);

                if (! $code || $banknoteSelling <= 0) {
                    continue;
                }

                $rate = $banknoteSelling / $unit;

                ExchangeRate::updateOrCreate(
                    ['from_currency' => $code, 'to_currency' => 'TRY', 'rate_date' => $date->toDateString()],
                    ['rate' => $rate, 'source' => 'tcmb']
                );

                $saved++;
            }

            return $saved;
        } catch (\Throwable $e) {
            Log::error('TCMB rate fetch failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    public function saveManualRate(string $from, string $to, float $rate, ?Carbon $date = null): ExchangeRate
    {
        return ExchangeRate::updateOrCreate(
            ['from_currency' => $from, 'to_currency' => $to, 'rate_date' => ($date ?? today())->toDateString()],
            ['rate' => $rate, 'source' => 'manual']
        );
    }

    private function findRate(string $from, string $to, Carbon $date): ?ExchangeRate
    {
        return ExchangeRate::where('from_currency', $from)
            ->where('to_currency', $to)
            ->whereDate('rate_date', '<=', $date->toDateString())
            ->orderByDesc('rate_date')
            ->first();
    }
}
