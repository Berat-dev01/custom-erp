<?php

namespace App\Erp\Services\Assets;

use App\Erp\Models\Asset;
use App\Erp\Models\DepreciationEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepreciationService
{
    public function runMonthlyDepreciation(?int $year = null, ?int $month = null): int
    {
        $year  = $year  ?? now()->year;
        $month = $month ?? now()->month;

        $processed = 0;

        Asset::where('status', 'active')
            ->with('category')
            ->chunkById(100, function ($assets) use ($year, $month, &$processed): void {
                foreach ($assets as $asset) {
                    try {
                        $this->depreciateAsset($asset, $year, $month);
                        $processed++;
                    } catch (\Throwable $e) {
                        Log::error('Asset depreciation failed', [
                            'asset_id' => $asset->id,
                            'year'     => $year,
                            'month'    => $month,
                            'error'    => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $processed;
    }

    public function depreciateAsset(Asset $asset, int $year, int $month): ?DepreciationEntry
    {
        if ((float) $asset->current_value <= 0) {
            return null;
        }

        $existing = DepreciationEntry::where('asset_id', $asset->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existing) {
            return $existing;
        }

        $monthlyAmount = $asset->monthlyDepreciationAmount();

        if ($monthlyAmount <= 0) {
            return null;
        }

        $bookValueAfter = max(0, (float) $asset->current_value - $monthlyAmount);

        return DB::transaction(function () use ($asset, $year, $month, $monthlyAmount, $bookValueAfter): DepreciationEntry {
            $entry = DepreciationEntry::create([
                'asset_id'         => $asset->id,
                'year'             => $year,
                'month'            => $month,
                'amount'           => $monthlyAmount,
                'book_value_after' => $bookValueAfter,
            ]);

            $asset->update(['current_value' => $bookValueAfter]);

            app(\App\Erp\Services\Accounting\AccountingService::class)->postDepreciation($entry->load('asset'));

            return $entry;
        });
    }
}
