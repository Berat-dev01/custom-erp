<?php

namespace Database\Seeders;

use App\Erp\Database\Seeders\ChartOfAccountsSeeder;
use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Account;
use App\Erp\Models\Customer;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\Invoice;
use App\Erp\Models\InvoiceItem;
use App\Erp\Models\JournalEntry;
use App\Erp\Models\JournalLine;
use App\Erp\Models\Payment;
use App\Erp\Models\PayrollParameter;
use App\Erp\Models\PayrollRun;
use App\Erp\Models\Payslip;
use App\Erp\Models\Position;
use App\Erp\Models\Product;
use App\Erp\Models\ProductCategory;
use App\Erp\Models\StockLevel;
use App\Erp\Models\Supplier;
use App\Erp\Models\Unit;
use App\Erp\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Büyük demo veri seti — 50 çalışan, 500 ürün, 200 fatura, 12 aylık bordro.
 * Üretim ortamına yüklenmez; sadece demo/test amaçlı.
 */
class ErpDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Temel izin ve hesap planı
        $this->call([
            ErpPermissionSeeder::class,
            ChartOfAccountsSeeder::class,
        ]);

        $admin = $this->ensureAdmin();

        // ── Birimler ──────────────────────────────────────────────────────
        $units = $this->seedUnits();

        // ── Kategoriler ───────────────────────────────────────────────────
        $categories = $this->seedCategories();

        // ── Depolar (3 adet) ──────────────────────────────────────────────
        $warehouses = $this->seedWarehouses();

        // ── Departman & Pozisyon ──────────────────────────────────────────
        [$depts, $positions] = $this->seedDepartmentsAndPositions();

        // ── 50 Çalışan (2 yıllık geçmiş) ─────────────────────────────────
        $employees = $this->seedEmployees($depts, $positions, $admin);

        // ── 500 Ürün ─────────────────────────────────────────────────────
        $products = $this->seedProducts($units, $categories, $warehouses);

        // ── 10 Tedarikçi ──────────────────────────────────────────────────
        $suppliers = Supplier::factory()->count(10)->create();

        // ── 20 Müşteri ────────────────────────────────────────────────────
        $customers = Customer::factory()->count(20)->create();

        // ── 200 Fatura ────────────────────────────────────────────────────
        $this->seedInvoices($customers, $products, $admin);

        // ── 12 Aylık Bordro (2025 yılı) ──────────────────────────────────
        $this->seedPayroll($employees, $admin);

        // ── Açılış Muhasebe Bakiyeleri ────────────────────────────────────
        $this->seedOpeningBalances($admin);

        // ── 2026 Bordro Parametreleri ─────────────────────────────────────
        $this->seedPayrollParameter();

        $this->command?->info('✓ ERP demo verisi yüklendi (50 çalışan, 500 ürün, 200 fatura, 12 aylık bordro).');
    }

    private function ensureAdmin(): User
    {
        $admin = User::where('email', 'admin@erp.test')->first()
            ?? User::factory()->create([
                'name'      => 'ERP Admin',
                'email'     => 'admin@erp.test',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]);

        if (! $admin->hasRole('erp_admin')) {
            $admin->assignRole('erp_admin');
        }

        return $admin;
    }

    private function seedUnits(): \Illuminate\Support\Collection
    {
        $defs = [
            ['name' => 'Adet',    'abbreviation' => 'pcs'],
            ['name' => 'Kilogram','abbreviation' => 'kg'],
            ['name' => 'Litre',   'abbreviation' => 'L'],
            ['name' => 'Metre',   'abbreviation' => 'm'],
            ['name' => 'Kutu',    'abbreviation' => 'box'],
        ];

        return collect($defs)->map(fn ($d) => Unit::firstOrCreate(['abbreviation' => $d['abbreviation']], $d));
    }

    private function seedCategories(): \Illuminate\Support\Collection
    {
        $cats = [
            'Elektronik', 'Mekanik', 'Yazılım', 'Ofis Malzeme',
            'Sarf Malzeme', 'Mobilya', 'Güvenlik', 'Temizlik',
            'Gıda', 'Tekstil',
        ];

        return collect($cats)->map(fn ($name) => ProductCategory::firstOrCreate(
            ['slug' => \Illuminate\Support\Str::slug($name)],
            ['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'is_active' => true]
        ));
    }

    private function seedWarehouses(): \Illuminate\Support\Collection
    {
        $defs = [
            ['name' => 'İstanbul Merkez', 'code' => 'IST', 'is_default' => true],
            ['name' => 'Ankara Depo',     'code' => 'ANK', 'is_default' => false],
            ['name' => 'İzmir Depo',      'code' => 'IZM', 'is_default' => false],
        ];

        return collect($defs)->map(fn ($d) => Warehouse::firstOrCreate(
            ['code' => $d['code']],
            array_merge($d, ['is_active' => true])
        ));
    }

    private function seedDepartmentsAndPositions(): array
    {
        $deptDefs = [
            ['name' => 'Bilgi Teknolojileri', 'code' => 'BT'],
            ['name' => 'İnsan Kaynakları',    'code' => 'IK'],
            ['name' => 'Muhasebe & Finans',   'code' => 'MF'],
            ['name' => 'Satış & Pazarlama',   'code' => 'SP'],
            ['name' => 'Üretim',              'code' => 'UR'],
            ['name' => 'Lojistik',            'code' => 'LJ'],
        ];

        $depts = collect($deptDefs)->map(fn ($d) => Department::firstOrCreate(
            ['code' => $d['code']],
            array_merge($d, ['is_active' => true])
        ));

        $posDefs = [
            ['name' => 'Yazılım Geliştirici', 'level' => 'mid',     'dept' => 'BT'],
            ['name' => 'Kıdemli Geliştirici', 'level' => 'senior',  'dept' => 'BT'],
            ['name' => 'IT Müdürü',           'level' => 'manager', 'dept' => 'BT'],
            ['name' => 'İK Uzmanı',           'level' => 'mid',     'dept' => 'IK'],
            ['name' => 'İK Müdürü',           'level' => 'manager', 'dept' => 'IK'],
            ['name' => 'Muhasebeci',          'level' => 'mid',     'dept' => 'MF'],
            ['name' => 'Mali Müşavir',        'level' => 'senior',  'dept' => 'MF'],
            ['name' => 'Satış Temsilcisi',    'level' => 'mid',     'dept' => 'SP'],
            ['name' => 'Satış Müdürü',        'level' => 'manager', 'dept' => 'SP'],
            ['name' => 'Üretim Operatörü',    'level' => 'junior',  'dept' => 'UR'],
            ['name' => 'Üretim Müdürü',       'level' => 'manager', 'dept' => 'UR'],
            ['name' => 'Depo Görevlisi',      'level' => 'junior',  'dept' => 'LJ'],
        ];

        $positions = collect($posDefs)->map(function ($p) use ($depts) {
            $dept = $depts->firstWhere('code', $p['dept']);
            return Position::firstOrCreate(
                ['name' => $p['name'], 'department_id' => $dept->id],
                ['name' => $p['name'], 'department_id' => $dept->id, 'level' => $p['level'], 'is_active' => true]
            );
        });

        return [$depts, $positions];
    }

    private function seedEmployees(\Illuminate\Support\Collection $depts, \Illuminate\Support\Collection $positions, User $admin): array
    {
        $employees = [];
        $existing  = Employee::withTrashed()->count();

        for ($i = 0; $i < 50; $i++) {
            $dept = $depts->random();
            $pos  = $positions->where('department_id', $dept->id)->first() ?? $positions->first();

            $hireDate = Carbon::now()->subDays(rand(30, 730)); // son 2 yıl içinde

            $employees[] = Employee::create([
                'employee_number' => 'DEMO-'.str_pad(++$existing, 5, '0', STR_PAD_LEFT),
                'first_name'      => fake()->firstName(),
                'last_name'       => fake()->lastName(),
                'email'           => 'emp'.($existing).'@erp.demo',
                'hire_date'       => $hireDate->format('Y-m-d'),
                'employment_type' => 'full_time',
                'status'          => 'active',
                'department_id'   => $dept->id,
                'position_id'     => $pos->id,
            ]);
        }

        return $employees;
    }

    private function seedProducts(\Illuminate\Support\Collection $units, \Illuminate\Support\Collection $categories, \Illuminate\Support\Collection $warehouses): array
    {
        $products   = [];
        $unit       = $units->first();
        $existing   = Product::withTrashed()->count();

        for ($i = 0; $i < 500; $i++) {
            $purchasePrice = rand(10, 1000);
            $product = Product::create([
                'sku'            => 'DEMO-'.str_pad(++$existing, 6, '0', STR_PAD_LEFT),
                'name'           => fake()->words(rand(2, 4), true),
                'category_id'    => $categories->random()->id,
                'unit_id'        => $unit->id,
                'purchase_price' => $purchasePrice,
                'sale_price'     => round($purchasePrice * rand(120, 200) / 100, 2),
                'tax_rate'       => 20.00,
                'type'           => 'product',
                'track_stock'    => true,
                'reorder_point'  => rand(5, 50),
                'is_active'      => true,
            ]);

            // Her ürüne her depoda stok ekle
            foreach ($warehouses as $warehouse) {
                StockLevel::create([
                    'product_id'        => $product->id,
                    'warehouse_id'      => $warehouse->id,
                    'quantity'          => rand(0, 500),
                    'reserved_quantity' => 0,
                ]);
            }

            $products[] = $product;
        }

        return $products;
    }

    private function seedInvoices(\Illuminate\Support\Collection $customers, array $products, User $admin): void
    {
        $statuses = ['paid', 'paid', 'paid', 'paid', 'draft', 'sent', 'partial', 'overdue', 'overdue'];
        $existing = Invoice::withTrashed()->count();

        for ($i = 0; $i < 200; $i++) {
            $status   = $statuses[array_rand($statuses)];
            $customer = $customers->random();
            $issueDate = Carbon::now()->subDays(rand(0, 730));
            $dueDate   = $issueDate->copy()->addDays(rand(15, 60));

            $subtotal  = rand(500, 50000);
            $taxAmount = round($subtotal * 0.20, 2);
            $total     = $subtotal + $taxAmount;
            $paidAmount = match ($status) {
                'paid'    => $total,
                'partial' => round($total * rand(30, 70) / 100, 2),
                default   => 0,
            };

            $invoice = Invoice::create([
                'invoice_number'   => 'INV-DEMO-'.str_pad(++$existing, 6, '0', STR_PAD_LEFT),
                'type'             => 'sale',
                'invoiceable_type' => 'erp_customer',
                'invoiceable_id'   => $customer->id,
                'status'           => $status,
                'issue_date'       => $issueDate,
                'due_date'         => $dueDate,
                'subtotal'         => $subtotal,
                'tax_amount'       => $taxAmount,
                'total'            => $total,
                'paid_amount'      => $paidAmount,
                'created_by'       => $admin->id,
            ]);

            // Fatura kalemi
            $product = $products[array_rand($products)];
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'product_id'  => $product->id,
                'description' => $product->name,
                'quantity'    => 1,
                'unit_price'  => $subtotal,
                'tax_rate'    => 20,
                'discount_rate'=> 0,
                'line_total'  => $subtotal,
            ]);

            // Paid faturalara ödeme kaydı
            if ($paidAmount > 0) {
                Payment::create([
                    'invoice_id'   => $invoice->id,
                    'amount'       => $paidAmount,
                    'payment_date' => $dueDate->copy()->subDays(rand(0, 5)),
                    'method'       => 'bank_transfer',
                    'created_by'   => $admin->id,
                ]);
            }
        }
    }

    private function seedPayroll(array $employees, User $admin): void
    {
        $existingRun = PayrollRun::where('year', 2025)->exists();
        if ($existingRun) {
            return;
        }

        for ($month = 1; $month <= 12; $month++) {
            $run = PayrollRun::create([
                'year'             => 2025,
                'month'            => $month,
                'status'           => 'paid',
                'pay_date'         => Carbon::create(2025, $month)->endOfMonth()->format('Y-m-d'),
                'total_gross'      => 0,
                'total_deductions' => 0,
                'total_net'        => 0,
                'created_by'       => $admin->id,
            ]);

            $totalGross = 0;
            $totalNet   = 0;

            foreach (array_slice($employees, 0, 20) as $employee) {
                $gross = rand(22000, 80000);
                $sgk   = round($gross * 0.14, 2);
                $unemp = round($gross * 0.01, 2);
                $tax   = round(($gross - $sgk - $unemp) * 0.15, 2);
                $stamp = round($gross * 0.00759, 2);
                $net   = round($gross - $sgk - $unemp - $tax - $stamp + 500, 2);

                Payslip::create([
                    'payroll_run_id'  => $run->id,
                    'employee_id'     => $employee->id,
                    'basic_salary'    => $gross,
                    'gross_salary'    => $gross,
                    'total_deductions'=> $sgk + $unemp + $tax + $stamp,
                    'net_salary'      => $net,
                    'status'          => 'paid',
                    'breakdown'       => [
                        'sgk_worker'          => $sgk,
                        'unemployment_worker' => $unemp,
                        'income_tax'          => $tax,
                        'stamp_tax'           => $stamp,
                        'agi'                 => 500,
                        'net'                 => $net,
                    ],
                ]);

                $totalGross += $gross;
                $totalNet   += $net;
            }

            $run->update([
                'total_gross'      => $totalGross,
                'total_net'        => $totalNet,
                'total_deductions' => $totalGross - $totalNet,
            ]);
        }
    }

    private function seedOpeningBalances(User $admin): void
    {
        $accounts = Account::whereIn('code', ['102', '120', '320', '500', '570'])->get();

        if ($accounts->isEmpty()) {
            return;
        }

        if (JournalEntry::where('description', 'Açılış Bakiyesi (Demo)')->exists()) {
            return;
        }

        $entry = JournalEntry::create([
            'entry_number' => 'YEV-DEMO-ACILIS',
            'entry_date'   => Carbon::create(2026, 1, 1),
            'type'         => 'adjustment',
            'description'  => 'Açılış Bakiyesi (Demo)',
            'source_type'  => 'adjustment',
            'source_id'    => 0,
            'status'       => 'posted',
            'created_by'   => $admin->id,
        ]);

        $openingData = [
            '102' => ['debit' => 250000, 'credit' => 0],    // Bankalar
            '120' => ['debit' => 180000, 'credit' => 0],    // Alıcılar
            '320' => ['debit' => 0,      'credit' => 90000], // Satıcılar
            '500' => ['debit' => 0,      'credit' => 250000], // Sermaye
            '570' => ['debit' => 0,      'credit' => 90000], // Geçmiş Yıl Kârları
        ];

        foreach ($openingData as $code => $amounts) {
            $account = $accounts->firstWhere('code', $code);
            if (! $account) {
                continue;
            }

            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $account->id,
                'debit'            => $amounts['debit'],
                'credit'           => $amounts['credit'],
                'description'      => "Açılış bakiyesi — {$account->name}",
            ]);
        }
    }

    private function seedPayrollParameter(): void
    {
        PayrollParameter::firstOrCreate(['year' => 2026], [
            'year'                           => 2026,
            'minimum_wage'                   => 22104.97,
            'sgk_worker_rate'                => 0.14,
            'sgk_employer_rate'              => 0.155,
            'unemployment_worker_rate'       => 0.01,
            'unemployment_employer_rate'     => 0.02,
            'stamp_tax_rate'                 => 0.00759,
            'income_tax_brackets'            => [
                ['limit' => 110000,  'rate' => 0.15],
                ['limit' => 230000,  'rate' => 0.20],
                ['limit' => 580000,  'rate' => 0.27],
                ['limit' => 3000000, 'rate' => 0.35],
                ['limit' => null,    'rate' => 0.40],
            ],
            'agi_single'                     => 500.00,
            'agi_married_spouse_not_working' => 750.00,
        ]);

        PayrollParameter::firstOrCreate(['year' => 2025], [
            'year'                           => 2025,
            'minimum_wage'                   => 17002.12,
            'sgk_worker_rate'                => 0.14,
            'sgk_employer_rate'              => 0.155,
            'unemployment_worker_rate'       => 0.01,
            'unemployment_employer_rate'     => 0.02,
            'stamp_tax_rate'                 => 0.00759,
            'income_tax_brackets'            => [
                ['limit' => 110000,  'rate' => 0.15],
                ['limit' => 230000,  'rate' => 0.20],
                ['limit' => 580000,  'rate' => 0.27],
                ['limit' => 3000000, 'rate' => 0.35],
                ['limit' => null,    'rate' => 0.40],
            ],
            'agi_single'                     => 416.66,
            'agi_married_spouse_not_working' => 625.00,
        ]);
    }
}
