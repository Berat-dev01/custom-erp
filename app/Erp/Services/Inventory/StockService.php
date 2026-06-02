<?php

namespace App\Erp\Services\Inventory;

use App\Erp\Models\Product;
use App\Erp\Models\StockLevel;
use App\Erp\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function recordMovement(array $data): StockMovement
    {
        return DB::transaction(function () use ($data): StockMovement {
            $movement = StockMovement::create($data);

            $this->applyMovement($data['product_id'], $data['warehouse_id'], $data['type'], (float) $data['quantity']);

            return $movement;
        });
    }

    public function availableStock(int $productId, int $warehouseId): float
    {
        $level = StockLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $level) {
            return 0;
        }

        return max(0, (float) $level->quantity - (float) $level->reserved_quantity);
    }

    public function checkReorderPoints(): void
    {
        Product::where('track_stock', true)
            ->where('reorder_point', '>', 0)
            ->with('stockLevels')
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $total = $product->stockLevels->sum('quantity');
                    if ($total <= $product->reorder_point) {
                        // Bildirim sistemi Faz 20'de eklenecek
                    }
                }
            });
    }

    public function lowStockCount(): int
    {
        return Product::where('track_stock', true)
            ->where('reorder_point', '>', 0)
            ->whereHas('stockLevels', function ($q): void {
                $q->whereColumn('quantity', '<=', 'erp_products.reorder_point');
            })
            ->count();
    }

    private function applyMovement(int $productId, int $warehouseId, string $type, float $quantity): void
    {
        $level = StockLevel::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['quantity' => 0, 'reserved_quantity' => 0]
        );

        $delta = match ($type) {
            'in'         =>  $quantity,
            'out'        => -$quantity,
            'adjustment' =>  $quantity, // signed quantity
            default      =>  0,
        };

        $level->increment('quantity', $delta);
    }
}
