<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Customer;
use App\Erp\Models\Invoice;
use App\Erp\Models\InvoiceItem;
use App\Erp\Services\Finance\InvoiceService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpFinanceModuleTest extends TestCase
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

    private function createInvoice(array $attrs = []): Invoice
    {
        $customer = Customer::factory()->create();

        $invoice = Invoice::create(array_merge([
            'invoice_number'   => 'INV-TEST-'.rand(1000, 9999),
            'type'             => 'sale',
            'invoiceable_type' => 'erp_customer',
            'invoiceable_id'   => $customer->id,
            'status'           => 'sent',
            'issue_date'       => now(),
            'due_date'         => now()->addDays(30),
            'subtotal'         => 1000,
            'tax_amount'       => 200,
            'total'            => 1200,
            'paid_amount'      => 0,
            'created_by'       => $this->admin->id,
        ], $attrs));

        return $invoice;
    }

    public function test_viewer_can_see_invoice_list(): void
    {
        $this->createInvoice();

        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.invoices.index'))
            ->assertOk();
    }

    public function test_viewer_cannot_create_invoice(): void
    {
        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.invoices.create'))
            ->assertForbidden();
    }

    public function test_payment_reduces_remaining_balance(): void
    {
        $invoice = $this->createInvoice(['total' => 1200, 'paid_amount' => 0, 'status' => 'sent']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.invoices.payments.store', $invoice), [
                'amount'       => 600,
                'payment_date' => now()->format('Y-m-d'),
                'method'       => 'bank_transfer',
            ])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(600, (float) $invoice->paid_amount);
        $this->assertEquals('partial', $invoice->status);
    }

    public function test_full_payment_marks_invoice_paid(): void
    {
        $invoice = $this->createInvoice(['total' => 1200, 'paid_amount' => 0, 'status' => 'sent']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.invoices.payments.store', $invoice), [
                'amount'       => 1200,
                'payment_date' => now()->format('Y-m-d'),
                'method'       => 'cash',
            ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_mark_overdue_invoices(): void
    {
        $overdue = $this->createInvoice([
            'status'   => 'sent',
            'due_date' => now()->subDays(5),
        ]);

        $fresh = $this->createInvoice([
            'status'   => 'sent',
            'due_date' => now()->addDays(10),
        ]);

        $count = app(InvoiceService::class)->markOverdueInvoices();

        $this->assertEquals(1, $count);
        $this->assertEquals('overdue', $overdue->fresh()->status);
        $this->assertEquals('sent', $fresh->fresh()->status);
    }

    public function test_viewer_cannot_record_payment(): void
    {
        $invoice = $this->createInvoice();

        $this->actingAs($this->viewer, 'admin')
            ->post(route('erp.invoices.payments.store', $invoice), [
                'amount'       => 100,
                'payment_date' => now()->format('Y-m-d'),
                'method'       => 'cash',
            ])
            ->assertForbidden();
    }

    public function test_expense_can_be_created(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.expenses.store'), [
                'title'          => 'Ofis Kirası',
                'category'       => 'rent',
                'amount'         => 5000,
                'expense_date'   => now()->format('Y-m-d'),
                'payment_method' => 'bank_transfer',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('erp_expenses', ['title' => 'Ofis Kirası']);
    }
}
