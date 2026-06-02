<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ChartOfAccountsSeeder;
use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Account;
use App\Erp\Models\Customer;
use App\Erp\Models\Invoice;
use App\Erp\Models\JournalEntry;
use App\Erp\Models\Payment;
use App\Erp\Services\Accounting\AccountingService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpAccountingModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $this->admin  = User::factory()->create(['is_active' => true]);
        $this->viewer = User::factory()->create(['is_active' => true]);

        $this->admin->assignRole('erp_admin');
        $this->viewer->assignRole('erp_viewer');
    }

    private function makeInvoice(float $total = 1200, float $subtotal = 1000, float $tax = 200): Invoice
    {
        $customer = Customer::factory()->create();

        return Invoice::create([
            'invoice_number'   => 'INV-ACC-'.rand(1000, 9999),
            'type'             => 'sale',
            'invoiceable_type' => 'erp_customer',
            'invoiceable_id'   => $customer->id,
            'status'           => 'sent',
            'issue_date'       => now(),
            'due_date'         => now()->addDays(30),
            'subtotal'         => $subtotal,
            'tax_amount'       => $tax,
            'total'            => $total,
            'paid_amount'      => 0,
            'created_by'       => $this->admin->id,
        ]);
    }

    public function test_chart_of_accounts_seeded(): void
    {
        $this->assertDatabaseHas('erp_accounts', ['code' => '120', 'name' => 'Alıcılar']);
        $this->assertDatabaseHas('erp_accounts', ['code' => '600', 'name' => 'Yurt İçi Satışlar']);
        $this->assertDatabaseHas('erp_accounts', ['code' => '102', 'name' => 'Bankalar']);
        $this->assertDatabaseHas('erp_accounts', ['code' => '391', 'name' => 'Hesaplanan KDV']);
    }

    public function test_post_sale_invoice_creates_journal_entry(): void
    {
        $invoice = $this->makeInvoice(1200, 1000, 200);

        $entry = app(AccountingService::class)->postSaleInvoice($invoice);

        $this->assertNotNull($entry);
        $this->assertEquals('posted', $entry->status);
        $this->assertEquals('invoice', $entry->type);
        $this->assertCount(3, $entry->load('lines')->lines);
    }

    public function test_sale_invoice_journal_is_balanced(): void
    {
        $invoice = $this->makeInvoice(1200, 1000, 200);
        $entry   = app(AccountingService::class)->postSaleInvoice($invoice);

        $entry->load('lines');
        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(1200, $entry->totalDebit());
        $this->assertEquals(1200, $entry->totalCredit());
    }

    public function test_payment_received_creates_journal_entry(): void
    {
        $invoice = $this->makeInvoice();
        $payment = Payment::create([
            'invoice_id'   => $invoice->id,
            'amount'       => 600,
            'payment_date' => now(),
            'method'       => 'bank_transfer',
            'created_by'   => $this->admin->id,
        ]);
        $payment->load('invoice');

        $entry = app(AccountingService::class)->postPaymentReceived($payment);

        $this->assertNotNull($entry);
        $entry->load('lines');
        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(600, $entry->totalDebit());
    }

    public function test_record_payment_auto_posts_journal(): void
    {
        $invoice = $this->makeInvoice(1200, 1000, 200);
        $countBefore = JournalEntry::count();

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.invoices.payments.store', $invoice), [
                'amount'       => 600,
                'payment_date' => now()->format('Y-m-d'),
                'method'       => 'bank_transfer',
            ]);

        $this->assertEquals($countBefore + 1, JournalEntry::count());

        $entry = JournalEntry::latest('id')->first();
        $this->assertEquals('payment', $entry->type);
        $this->assertEquals('posted', $entry->status);
    }

    public function test_invoice_send_posts_sale_journal(): void
    {
        $customer = Customer::factory()->create();
        $invoice  = Invoice::create([
            'invoice_number'   => 'INV-SEND-001',
            'type'             => 'sale',
            'invoiceable_type' => 'erp_customer',
            'invoiceable_id'   => $customer->id,
            'status'           => 'draft',
            'issue_date'       => now(),
            'due_date'         => now()->addDays(30),
            'subtotal'         => 800,
            'tax_amount'       => 160,
            'total'            => 960,
            'paid_amount'      => 0,
            'created_by'       => $this->admin->id,
        ]);

        $countBefore = JournalEntry::count();

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.invoices.send', $invoice));

        $this->assertEquals($countBefore + 1, JournalEntry::count());
    }

    public function test_manual_journal_entry_can_be_created(): void
    {
        $receivables = Account::where('code', '120')->first();
        $sales       = Account::where('code', '600')->first();

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.journal-entries.store'), [
                'description' => 'Test Manuel Fişi',
                'entry_date'  => now()->format('Y-m-d'),
                'lines'       => [
                    ['account_id' => $receivables->id, 'debit' => 1000, 'credit' => 0],
                    ['account_id' => $sales->id,       'debit' => 0,    'credit' => 1000],
                ],
            ])
            ->assertRedirect(route('erp.journal-entries.index'));

        $this->assertDatabaseHas('erp_journal_entries', ['description' => 'Test Manuel Fişi', 'status' => 'posted']);
    }

    public function test_unbalanced_journal_entry_rejected(): void
    {
        $receivables = Account::where('code', '120')->first();
        $sales       = Account::where('code', '600')->first();

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.journal-entries.store'), [
                'description' => 'Dengesiz Fiş',
                'entry_date'  => now()->format('Y-m-d'),
                'lines'       => [
                    ['account_id' => $receivables->id, 'debit' => 1000, 'credit' => 0],
                    ['account_id' => $sales->id,       'debit' => 0,    'credit' => 800],
                ],
            ])
            ->assertSessionHasErrors('lines');
    }

    public function test_viewer_cannot_create_journal_entry(): void
    {
        $receivables = Account::where('code', '120')->first();
        $sales       = Account::where('code', '600')->first();

        $this->actingAs($this->viewer, 'admin')
            ->post(route('erp.journal-entries.store'), [
                'description' => 'Test',
                'entry_date'  => now()->format('Y-m-d'),
                'lines'       => [
                    ['account_id' => $receivables->id, 'debit' => 100, 'credit' => 0],
                    ['account_id' => $sales->id,       'debit' => 0,   'credit' => 100],
                ],
            ])
            ->assertForbidden();
    }

    public function test_trial_balance_page_loads(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.reports.trial-balance'))
            ->assertOk();
    }

    public function test_balance_sheet_page_loads(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.reports.balance-sheet'))
            ->assertOk();
    }

    public function test_income_statement_page_loads(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.reports.income-statement'))
            ->assertOk();
    }

    public function test_accounts_index_loads(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.accounts.index'))
            ->assertOk()
            ->assertViewHas('accounts');
    }

    public function test_journal_entries_index_loads(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.journal-entries.index'))
            ->assertOk();
    }
}
