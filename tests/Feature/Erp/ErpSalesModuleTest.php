<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Customer;
use App\Erp\Models\Product;
use App\Erp\Models\SalesOrder;
use App\Erp\Models\SalesOrderItem;
use App\Erp\Models\StockLevel;
use App\Erp\Models\Unit;
use App\Erp\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpSalesModuleTest extends TestCase
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

    public function test_sales_order_list_is_accessible(): void
    {
        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.sales-orders.index'))
            ->assertOk();
    }

    public function test_viewer_cannot_create_sales_order(): void
    {
        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.sales-orders.create'))
            ->assertForbidden();
    }

    public function test_confirming_order_reserves_stock(): void
    {
        $unit      = Unit::factory()->create();
        $product   = Product::factory()->create(['unit_id' => $unit->id]);
        $warehouse = Warehouse::factory()->create();
        $customer  = Customer::factory()->create();

        StockLevel::create([
            'product_id'        => $product->id,
            'warehouse_id'      => $warehouse->id,
            'quantity'          => 100,
            'reserved_quantity' => 0,
        ]);

        $order = SalesOrder::create([
            'so_number'    => 'SO-TEST-001',
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date'   => now(),
            'status'       => 'draft',
            'created_by'   => $this->admin->id,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $order->id,
            'product_id'     => $product->id,
            'quantity'       => 10,
            'unit_price'     => 50,
            'tax_rate'       => 20,
            'discount_rate'  => 0,
            'line_total'     => 600,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.sales-orders.confirm', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertEquals('confirmed', $order->status);

        $level = StockLevel::first();
        $this->assertEquals(10, (float) $level->reserved_quantity);
    }

    public function test_delivering_order_decreases_stock(): void
    {
        $unit      = Unit::factory()->create();
        $product   = Product::factory()->create(['unit_id' => $unit->id]);
        $warehouse = Warehouse::factory()->create();
        $customer  = Customer::factory()->create();

        StockLevel::create([
            'product_id'        => $product->id,
            'warehouse_id'      => $warehouse->id,
            'quantity'          => 100,
            'reserved_quantity' => 20,
        ]);

        $order = SalesOrder::create([
            'so_number'    => 'SO-TEST-002',
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date'   => now(),
            'status'       => 'confirmed',
            'created_by'   => $this->admin->id,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $order->id,
            'product_id'     => $product->id,
            'quantity'       => 20,
            'unit_price'     => 50,
            'tax_rate'       => 20,
            'discount_rate'  => 0,
            'line_total'     => 1200,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.sales-orders.deliver', $order))
            ->assertRedirect();

        $level = StockLevel::first();
        $this->assertEquals(80, (float) $level->quantity);
        $this->assertEquals('delivered', $order->fresh()->status);
    }

    public function test_viewer_cannot_confirm_order(): void
    {
        $customer  = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $order = SalesOrder::create([
            'so_number'    => 'SO-TEST-003',
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date'   => now(),
            'status'       => 'draft',
            'created_by'   => $this->admin->id,
        ]);

        $this->actingAs($this->viewer, 'admin')
            ->post(route('erp.sales-orders.confirm', $order))
            ->assertForbidden();
    }
}
