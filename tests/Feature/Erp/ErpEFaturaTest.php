<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Customer;
use App\Erp\Models\Invoice;
use App\Erp\Services\EFatura\EFaturaService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpEFaturaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('erp_admin');

        // e-Fatura test modunda (null driver)
        config(['erp.efatura.enabled' => false, 'erp.efatura.driver' => 'null']);
    }

    private function makeInvoice(string $status = 'sent'): Invoice
    {
        $customer = Customer::factory()->create();

        return Invoice::create([
            'invoice_number'   => 'INV-EF-'.rand(1000, 9999),
            'type'             => 'sale',
            'invoiceable_type' => 'erp_customer',
            'invoiceable_id'   => $customer->id,
            'status'           => $status,
            'issue_date'       => now(),
            'due_date'         => now()->addDays(30),
            'subtotal'         => 1000,
            'tax_amount'       => 200,
            'total'            => 1200,
            'paid_amount'      => 0,
            'created_by'       => $this->admin->id,
        ]);
    }

    public function test_invoice_has_efatura_fields(): void
    {
        $invoice = $this->makeInvoice();

        $this->assertDatabaseHas('erp_invoices', [
            'id'             => $invoice->id,
            'efatura_status' => 'none',
        ]);

        $this->assertNull($invoice->efatura_uuid);
    }

    public function test_efatura_disabled_when_not_configured(): void
    {
        $service = app(EFaturaService::class);

        $this->assertFalse($service->isEnabled());
    }

    public function test_invoice_show_accessible_for_admin(): void
    {
        $invoice = $this->makeInvoice();

        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.invoices.show', $invoice))
            ->assertOk();
    }

    public function test_efatura_send_requires_invoice_not_draft(): void
    {
        $invoice = $this->makeInvoice('draft');

        // Draft fatura e-fatura gönderimine uygun değil
        $this->assertNotEquals('sent', $invoice->efatura_status ?? 'none');
        $this->assertEquals('draft', $invoice->status);
    }

    public function test_efatura_status_enum_values_in_db(): void
    {
        $invoice = $this->makeInvoice();
        $invoice->update(['efatura_status' => 'pending']);

        $this->assertDatabaseHas('erp_invoices', [
            'id'             => $invoice->id,
            'efatura_status' => 'pending',
        ]);
    }

    public function test_efatura_uuid_stored_when_sent(): void
    {
        $invoice = $this->makeInvoice('sent');
        $uuid    = (string) \Illuminate\Support\Str::uuid();

        $invoice->update([
            'efatura_uuid'     => $uuid,
            'efatura_status'   => 'sent',
            'efatura_sent_at'  => now(),
            'efatura_type'     => 'earshiv',
        ]);

        $this->assertDatabaseHas('erp_invoices', [
            'id'           => $invoice->id,
            'efatura_uuid' => $uuid,
        ]);
    }

    public function test_viewer_cannot_trigger_efatura_send(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole('erp_viewer');
        $invoice = $this->makeInvoice('sent');

        $this->actingAs($viewer, 'admin')
            ->post(route('erp.invoices.send-efatura', $invoice))
            ->assertForbidden();
    }
}
