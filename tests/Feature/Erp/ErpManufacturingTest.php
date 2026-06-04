<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Bom;
use App\Erp\Models\BomComponent;
use App\Erp\Models\Product;
use App\Erp\Models\ProductCategory;
use App\Erp\Models\StockLevel;
use App\Erp\Models\Unit;
use App\Erp\Models\Warehouse;
use App\Erp\Models\WorkOrder;
use App\Erp\Services\Manufacturing\ManufacturingService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpManufacturingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ManufacturingService $service;
    private Warehouse $warehouse;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('erp_admin');

        $this->unit      = Unit::factory()->create(['name' => 'Adet', 'abbreviation' => 'pcs']);
        $this->warehouse = Warehouse::factory()->create(['is_default' => true]);
        $this->service   = app(ManufacturingService::class);
    }

    private function makeProduct(string $sku, float $stock = 0): Product
    {
        $product = Product::factory()->create([
            'sku'     => $sku,
            'unit_id' => $this->unit->id,
            'type'    => 'product',
        ]);

        if ($stock > 0) {
            StockLevel::create([
                'product_id'        => $product->id,
                'warehouse_id'      => $this->warehouse->id,
                'quantity'          => $stock,
                'reserved_quantity' => 0,
            ]);
        }

        return $product;
    }

    public function test_work_order_number_generated(): void
    {
        $num = $this->service->generateWoNumber();

        $this->assertStringStartsWith('WO-', $num);
    }

    public function test_release_reserves_components(): void
    {
        $this->actingAs($this->admin, 'admin');

        $mamul     = $this->makeProduct('MAM-001');
        $hammadde  = $this->makeProduct('HM-001', 50);

        $bom = Bom::create([
            'product_id' => $mamul->id,
            'version'    => '1.0',
            'is_active'  => true,
            'quantity'   => 1,
        ]);

        BomComponent::create([
            'bom_id'            => $bom->id,
            'component_id'      => $hammadde->id,
            'quantity'          => 5,
        ]);

        $wo = WorkOrder::create([
            'wo_number'         => 'WO-TEST-001',
            'bom_id'            => $bom->id,
            'product_id'        => $mamul->id,
            'warehouse_id'      => $this->warehouse->id,
            'planned_quantity'  => 3,
            'produced_quantity' => 0,
            'status'            => 'draft',
            'planned_start'     => now(),
            'planned_end'       => now()->addDays(5),
            'created_by'        => $this->admin->id,
        ]);

        $this->service->releaseWorkOrder($wo);

        $wo->refresh();
        $this->assertEquals('released', $wo->status);

        // Hammadde rezervasyonu artmış olmalı (5 adet × 3 üretim = 15)
        $level = StockLevel::where('product_id', $hammadde->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(15, $level->reserved_quantity);
    }

    public function test_complete_work_order_moves_stock(): void
    {
        $this->actingAs($this->admin, 'admin');

        $mamul    = $this->makeProduct('MAM-002');
        $hammadde = $this->makeProduct('HM-002', 30);

        StockLevel::where('product_id', $hammadde->id)->update(['reserved_quantity' => 10]);

        $bom = Bom::create([
            'product_id' => $mamul->id,
            'version'    => '1.0',
            'is_active'  => true,
            'quantity'   => 1,
        ]);

        BomComponent::create([
            'bom_id'       => $bom->id,
            'component_id' => $hammadde->id,
            'quantity'     => 5,
        ]);

        $wo = WorkOrder::create([
            'wo_number'         => 'WO-TEST-002',
            'bom_id'            => $bom->id,
            'product_id'        => $mamul->id,
            'warehouse_id'      => $this->warehouse->id,
            'planned_quantity'  => 2,
            'produced_quantity' => 0,
            'status'            => 'released',
            'planned_start'     => now()->subDays(2),
            'planned_end'       => now(),
            'created_by'        => $this->admin->id,
        ]);

        $this->service->completeWorkOrder($wo, 2);

        $wo->refresh();
        $this->assertEquals('completed', $wo->status);
        $this->assertEquals(2, $wo->produced_quantity);

        // Mamul stoğu artmış olmalı
        $mamulLevel = StockLevel::where('product_id', $mamul->id)->first();
        $this->assertNotNull($mamulLevel);
        $this->assertEquals(2, $mamulLevel->quantity);
    }

    public function test_bom_cost_calculation(): void
    {
        $mamul    = $this->makeProduct('MAM-003');
        $hammadde = $this->makeProduct('HM-003');
        $hammadde->update(['purchase_price' => 100]);

        $bom = Bom::create([
            'product_id' => $mamul->id,
            'version'    => '1.0',
            'is_active'  => true,
            'quantity'   => 1,
        ]);

        BomComponent::create([
            'bom_id'       => $bom->id,
            'component_id' => $hammadde->id,
            'quantity'     => 3,
        ]);

        $cost = $this->service->calculateBomCost($bom);

        $this->assertEquals(300.0, $cost); // 3 × 100
    }

    public function test_admin_can_access_bom_list(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.boms.index'))
            ->assertOk();
    }

    public function test_viewer_cannot_create_work_order(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole('erp_viewer');

        $this->actingAs($viewer, 'admin')
            ->get(route('erp.work-orders.create'))
            ->assertForbidden();
    }
}
