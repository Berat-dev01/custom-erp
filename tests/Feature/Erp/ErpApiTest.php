<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Customer;
use App\Erp\Models\Employee;
use App\Erp\Models\ErpApiToken;
use App\Erp\Models\Invoice;
use App\Erp\Models\Product;
use App\Erp\Models\Unit;
use App\Erp\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ErpApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $plainToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('erp_admin');

        $this->plainToken = Str::random(64);

        ErpApiToken::create([
            'user_id' => $this->admin->id,
            'name'    => 'Test Token',
            'token'   => hash('sha256', $this->plainToken),
        ]);
    }

    private function apiHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->plainToken];
    }

    public function test_unauthenticated_api_request_returns_401(): void
    {
        $this->getJson('/api/erp/employees')->assertUnauthorized();
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->getJson('/api/erp/employees', ['Authorization' => 'Bearer invalid-token'])
            ->assertUnauthorized();
    }

    public function test_employees_index_returns_paginated_list(): void
    {
        Employee::factory()->count(3)->create();

        $this->getJson('/api/erp/employees', $this->apiHeaders())
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'employee_number', 'first_name', 'last_name', 'email', 'status'],
                ],
                'meta' => ['total', 'per_page', 'current_page'],
            ]);
    }

    public function test_employee_show_returns_correct_employee(): void
    {
        $emp = Employee::factory()->create();

        $this->getJson("/api/erp/employees/{$emp->id}", $this->apiHeaders())
            ->assertOk()
            ->assertJsonPath('data.id', $emp->id)
            ->assertJsonPath('data.email', $emp->email);
    }

    public function test_products_index_returns_active_products(): void
    {
        $unit = Unit::factory()->create();
        Product::factory()->count(3)->create(['unit_id' => $unit->id, 'is_active' => true]);
        Product::factory()->create(['unit_id' => $unit->id, 'is_active' => false]);

        $this->getJson('/api/erp/products', $this->apiHeaders())
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_product_stock_endpoint(): void
    {
        $unit      = Unit::factory()->create();
        $product   = Product::factory()->create(['unit_id' => $unit->id]);
        $warehouse = Warehouse::factory()->create();

        \App\Erp\Models\StockLevel::create([
            'product_id'        => $product->id,
            'warehouse_id'      => $warehouse->id,
            'quantity'          => 50,
            'reserved_quantity' => 10,
        ]);

        $response = $this->getJson("/api/erp/products/{$product->id}/stock", $this->apiHeaders())
            ->assertOk();

        $this->assertEquals(50, $response->json('data.0.quantity'));
        $this->assertEquals(40, $response->json('data.0.available_quantity'));
    }

    public function test_invoices_index_requires_auth(): void
    {
        $this->getJson('/api/erp/invoices')->assertUnauthorized();
    }

    public function test_invoices_index_returns_list(): void
    {
        $customer = Customer::factory()->create();
        Invoice::create([
            'invoice_number'   => 'INV-API-001',
            'type'             => 'sale',
            'invoiceable_type' => 'erp_customer',
            'invoiceable_id'   => $customer->id,
            'status'           => 'sent',
            'issue_date'       => now(),
            'due_date'         => now()->addDays(30),
            'total'            => 1000,
            'created_by'       => $this->admin->id,
        ]);

        $this->getJson('/api/erp/invoices', $this->apiHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_stock_movement_api_creates_movement(): void
    {
        $unit      = Unit::factory()->create();
        $product   = Product::factory()->create(['unit_id' => $unit->id]);
        $warehouse = Warehouse::factory()->create();

        $this->postJson('/api/erp/stock-movements', [
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'type'         => 'in',
            'quantity'     => 25,
        ], $this->apiHeaders())
            ->assertCreated()
            ->assertJsonStructure(['message', 'movement_id']);

        $this->assertDatabaseHas('erp_stock_levels', [
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 25,
        ]);
    }

    public function test_api_token_expiry_denies_access(): void
    {
        $expiredToken = Str::random(64);

        ErpApiToken::create([
            'user_id'    => $this->admin->id,
            'name'       => 'Expired Token',
            'token'      => hash('sha256', $expiredToken),
            'expires_at' => now()->subDay(),
        ]);

        $this->getJson('/api/erp/employees', ['Authorization' => 'Bearer '.$expiredToken])
            ->assertUnauthorized();
    }

    public function test_api_per_page_is_capped_at_max(): void
    {
        Employee::factory()->count(5)->create();

        $this->getJson('/api/erp/employees?per_page=999', $this->apiHeaders())
            ->assertOk()
            ->assertJsonPath('meta.per_page', config('erp.api.max_per_page', 100));
    }
}
