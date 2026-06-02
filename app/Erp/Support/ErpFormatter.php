<?php

namespace App\Erp\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class ErpFormatter
{
    public function money(float|int|string|null $amount, ?string $currency = null): string
    {
        $currency ??= config('erp.currency', 'TRY');

        return number_format((float) ($amount ?? 0), 2, ',', '.').' '.$currency;
    }

    public function date(DateTimeInterface|string|null $value): string
    {
        $date = $this->carbon($value);

        return $date ? $date->format('d.m.Y') : '-';
    }

    public function datetime(DateTimeInterface|string|null $value): string
    {
        $date = $this->carbon($value);

        return $date ? $date->format('d.m.Y H:i') : '-';
    }

    public function status(string $status): string
    {
        return __((string) str($status)->replace('_', ' ')->headline());
    }

    private function carbon(DateTimeInterface|string|null $value): ?CarbonInterface
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse($value);
    }
}
