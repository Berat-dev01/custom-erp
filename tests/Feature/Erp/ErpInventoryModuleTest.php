<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Product;
use App\Erp\Models\StockLevel;
use App\Erp\Models\Unit;
use App\Erp\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpInventoryModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);

        $this->admin  = User::factory()->create(['is_active' => true]);
        $this->viewer = User::factory()->create(['is_active' => true]);

        $this->admin->assignRole('erp_admin');
        $this->viewer->assignRole('erp_viewer');
    }

    public function test_product_list_visible_to_viewer(): void
    {
        Product::factory()->count(3)->create();

        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.products.index'))
            ->assertOk();
    }

    public function test_admin_can_create_product(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.products.store'), [
                'sku'            => 'TST-001',
                'name'           => 'Test Ürün',
                'unit_id'        => $unit->id,
                'purchase_price' => 100,
                'sale_price'     => 150,
                'tax_rate'       => 20,
                'type'           => 'product',
                'track_stock'    => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('erp_products', ['sku' => 'TST-001']);
    }

    public function test_viewer_cannot_create_product(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->viewer, 'admin')
            ->post(route('erp.products.store'), [
                'sku'     => 'TST-002',
                'name'    => 'Test',
                'unit_id' => $unit->id,
            ])
            ->assertForbidden();
    }

    public function test_stock_movement_in_increases_stock_level(): void
    {
        $product   = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.stock-movements.store'), [
                'product_id'   => $product->id,
                'warehouse_id' => $warehouse->id,
                'type'         => 'in',
                'quantity'     => 50,
                'notes'        => 'İlk stok girişi',
            ])
            ->assertRedirect();

        $level = StockLevel::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $this->assertNotNull($level);
        $this->assertEquals(50, (float) $level->quantity);
    }

    public function test_stock_movement_out_decreases_stock_level(): void
    {
        $product   = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        StockLevel::create([
            'product_id'        => $product->id,
            'warehouse_id'      => $warehouse->id,
            'quantity'          => 100,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.stock-movements.store'), [
                'product_id'   => $product->id,
                'warehouse_id' => $warehouse->id,
                'type'         => 'out',
                'quantity'     => 30,
            ])
            ->assertRedirect();

        $this->assertEquals(70, (float) StockLevel::first()->quantity);
    }

    public function test_viewer_cannot_create_stock_movement(): void
    {
        $product   = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($this->viewer, 'admin')
            ->post(route('erp.stock-movements.store'), [
                'product_id'   => $product->id,
                'warehouse_id' => $warehouse->id,
                'type'         => 'in',
                'quantity'     => 10,
            ])
            ->assertForbidden();
    }

    public function test_stock_movement_validation_requires_quantity(): void
    {
        $product   = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.stock-movements.store'), [
                'product_id'   => $product->id,
                'warehouse_id' => $warehouse->id,
                'type'         => 'in',
            ])
            ->assertSessionHasErrors('quantity');
    }
}
