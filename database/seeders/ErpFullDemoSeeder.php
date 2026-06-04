<?php

namespace Database\Seeders;

use App\Erp\Models\Asset;
use App\Erp\Models\AssetCategory;
use App\Erp\Models\Attendance;
use App\Erp\Models\Customer;
use App\Erp\Models\Employee;
use App\Erp\Models\Expense;
use App\Erp\Models\LeaveRequest;
use App\Erp\Models\LeaveType;
use App\Erp\Models\Product;
use App\Erp\Models\Project;
use App\Erp\Models\ProjectTask;
use App\Erp\Models\PurchaseOrder;
use App\Erp\Models\PurchaseOrderItem;
use App\Erp\Models\SalesOrder;
use App\Erp\Models\SalesOrderItem;
use App\Erp\Models\Supplier;
use App\Erp\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Tüm modülleri dolduran demo seeder.
 * Var olan kayıtların üzerine yazmaz — idempotent.
 */
class ErpFullDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@erp.test')->firstOrFail();
        $employees = Employee::where('status', 'active')->get();
        $warehouse = Warehouse::where('is_default', true)->first() ?? Warehouse::first();
        $suppliers = Supplier::all();
        $customers = Customer::all();
        $products  = Product::where('is_active', true)->get();

        if ($employees->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('Önce ErpDemoSeeder veya ErpSeeder çalıştırın.');
            return;
        }

        $this->seedAttendance($employees);
        $this->seedLeaveTypes($employees, $admin);
        $this->seedProjects($customers, $employees, $admin);
        $this->seedAssets($employees, $warehouse);
        $this->seedExpenses($admin);
        $this->seedPurchaseOrders($suppliers, $products, $warehouse, $admin);
        $this->seedSalesOrders($customers, $products, $warehouse, $admin);

        $this->command?->info('✓ ERP tam demo verisi yüklendi.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DEVAM (ATTENDANCE)
    // ──────────────────────────────────────────────────────────────────────────

    private function seedAttendance(\Illuminate\Support\Collection $employees): void
    {
        if (Attendance::exists()) {
            $this->command?->line('  Attendance: mevcut kayıtlar korundu.');
            return;
        }

        $statuses = ['present', 'present', 'present', 'present', 'present', 'present',
                     'absent', 'on_leave', 'half_day'];
        $today = Carbon::today();

        foreach ($employees->take(30) as $employee) {
            $start = Carbon::today()->subDays(90);
            $date  = $start->copy();

            while ($date->lte($today)) {
                $dow = $date->dayOfWeek;
                if ($dow === 0 || $dow === 6) {
                    $date->addDay();
                    continue;
                }

                $status = $statuses[array_rand($statuses)];
                $checkIn = $checkOut = $workHours = null;

                if (in_array($status, ['present', 'half_day'])) {
                    $inH  = rand(8, 9);
                    $inM  = rand(0, 30);
                    $outH = $status === 'half_day' ? rand(12, 13) : rand(17, 18);
                    $outM = rand(0, 59);
                    $checkIn   = sprintf('%02d:%02d', $inH, $inM);
                    $checkOut  = sprintf('%02d:%02d', $outH, $outM);
                    $workHours = round(($outH * 60 + $outM - $inH * 60 - $inM) / 60, 2);
                }

                Attendance::create([
                    'employee_id'    => $employee->id,
                    'date'           => $date->format('Y-m-d'),
                    'status'         => $status,
                    'check_in'       => $checkIn,
                    'check_out'      => $checkOut,
                    'work_hours'     => $workHours,
                    'overtime_hours' => ($workHours && $workHours > 8) ? round($workHours - 8, 2) : 0,
                ]);

                $date->addDay();
            }
        }

        $this->command?->line('  Attendance: 90 günlük kayıt oluşturuldu.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // İZİN TÜRLERİ + TALEPLERİ
    // ──────────────────────────────────────────────────────────────────────────

    private function seedLeaveTypes(\Illuminate\Support\Collection $employees, User $admin): void
    {
        $typeDefs = [
            ['name' => 'Yıllık İzin',       'days_per_year' => 14, 'is_paid' => true,  'requires_approval' => true,  'carry_over' => true,  'max_carry_over_days' => 5],
            ['name' => 'Hastalık İzni',      'days_per_year' => 10, 'is_paid' => true,  'requires_approval' => false, 'carry_over' => false, 'max_carry_over_days' => 0],
            ['name' => 'Evlilik İzni',       'days_per_year' => 3,  'is_paid' => true,  'requires_approval' => true,  'carry_over' => false, 'max_carry_over_days' => 0],
            ['name' => 'Ücretsiz İzin',      'days_per_year' => 0,  'is_paid' => false, 'requires_approval' => true,  'carry_over' => false, 'max_carry_over_days' => 0],
            ['name' => 'Babalık İzni',       'days_per_year' => 5,  'is_paid' => true,  'requires_approval' => true,  'carry_over' => false, 'max_carry_over_days' => 0],
        ];

        $types = collect($typeDefs)->map(fn ($d) => LeaveType::firstOrCreate(['name' => $d['name']], array_merge($d, ['is_active' => true])));

        if (LeaveRequest::exists()) {
            $this->command?->line('  LeaveRequests: mevcut kayıtlar korundu.');
            return;
        }

        $statuses = ['approved', 'approved', 'approved', 'pending', 'rejected'];
        $manager  = $employees->first();

        foreach ($employees->take(20) as $employee) {
            $count = rand(1, 4);
            for ($i = 0; $i < $count; $i++) {
                $type      = $types->random();
                $days      = rand(1, min(5, $type->days_per_year ?: 5));
                $startDate = Carbon::today()->subDays(rand(0, 120))->addDays(rand(-30, 60));
                $endDate   = $startDate->copy()->addDays($days - 1);
                $status    = $statuses[array_rand($statuses)];

                LeaveRequest::create([
                    'employee_id'   => $employee->id,
                    'leave_type_id' => $type->id,
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'days'          => $days,
                    'reason'        => 'İzin talebi.',
                    'status'        => $status,
                    'approved_by'   => $status === 'approved' ? $manager->id : null,
                    'approved_at'   => $status === 'approved' ? now() : null,
                ]);
            }
        }

        $this->command?->line('  LeaveRequests: oluşturuldu.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PROJELER + GÖREVLER
    // ──────────────────────────────────────────────────────────────────────────

    private function seedProjects(\Illuminate\Support\Collection $customers, \Illuminate\Support\Collection $employees, User $admin): void
    {
        if (Project::exists()) {
            $this->command?->line('  Projects: mevcut kayıtlar korundu.');
            return;
        }

        $projectDefs = [
            ['name' => 'ERP Entegrasyon Projesi',     'code' => 'PRJ-001', 'status' => 'active',    'budget' => 450000],
            ['name' => 'E-Ticaret Platform Yenileme', 'code' => 'PRJ-002', 'status' => 'active',    'budget' => 280000],
            ['name' => 'Müşteri Portalı',             'code' => 'PRJ-003', 'status' => 'completed', 'budget' => 120000],
            ['name' => 'Mobil Uygulama v2',           'code' => 'PRJ-004', 'status' => 'active',    'budget' => 350000],
            ['name' => 'Veri Göç Projesi',            'code' => 'PRJ-005', 'status' => 'on_hold',   'budget' => 90000],
            ['name' => 'Güvenlik Denetimi',           'code' => 'PRJ-006', 'status' => 'planning',  'budget' => 75000],
            ['name' => 'Altyapı Modernizasyonu',      'code' => 'PRJ-007', 'status' => 'active',    'budget' => 600000],
            ['name' => 'CRM Entegrasyonu',            'code' => 'PRJ-008', 'status' => 'completed', 'budget' => 160000],
        ];

        $taskNames = [
            'Analiz ve Tasarım', 'Gereksinim Toplantısı', 'Prototip Geliştirme',
            'Backend API Geliştirme', 'Frontend Geliştirme', 'Test ve QA',
            'Kullanıcı Kabul Testi', 'Deployment', 'Dokümantasyon', 'Eğitim',
        ];

        foreach ($projectDefs as $def) {
            $manager  = $employees->random();
            $customer = $customers->isNotEmpty() ? $customers->random() : null;

            $project = Project::create([
                'name'        => $def['name'],
                'code'        => $def['code'],
                'status'      => $def['status'],
                'customer_id' => $customer?->id,
                'manager_id'  => $manager->id,
                'start_date'  => Carbon::today()->subDays(rand(30, 180)),
                'end_date'    => Carbon::today()->addDays(rand(30, 180)),
                'budget'      => $def['budget'],
                'spent'       => round($def['budget'] * rand(10, 70) / 100, 2),
            ]);

            $taskCount = rand(4, 8);
            shuffle($taskNames);
            $taskStatuses = ['todo', 'in_progress', 'review', 'done'];
            $priorities   = ['low', 'medium', 'medium', 'high', 'urgent'];

            for ($t = 0; $t < $taskCount; $t++) {
                ProjectTask::create([
                    'project_id'      => $project->id,
                    'name'            => $taskNames[$t % count($taskNames)],
                    'status'          => $taskStatuses[array_rand($taskStatuses)],
                    'priority'        => $priorities[array_rand($priorities)],
                    'assignee_id'     => $employees->random()->id,
                    'due_date'        => Carbon::today()->addDays(rand(-10, 60)),
                    'estimated_hours' => rand(8, 80),
                ]);
            }
        }

        $this->command?->line('  Projects + Tasks: oluşturuldu.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // VARLIKLAR (ASSETS)
    // ──────────────────────────────────────────────────────────────────────────

    private function seedAssets(\Illuminate\Support\Collection $employees, ?Warehouse $warehouse): void
    {
        if (Asset::exists()) {
            $this->command?->line('  Assets: mevcut kayıtlar korundu.');
            return;
        }

        $catDefs = [
            ['name' => 'Bilgisayar & Ekipman', 'useful_life_years' => 4,  'depreciation_rate' => 25],
            ['name' => 'Araç & Taşıt',         'useful_life_years' => 5,  'depreciation_rate' => 20],
            ['name' => 'Mobilya & Demirbaş',   'useful_life_years' => 10, 'depreciation_rate' => 10],
            ['name' => 'Yazılım Lisansı',       'useful_life_years' => 3,  'depreciation_rate' => 33],
        ];

        $categories = collect($catDefs)->map(fn ($d) => AssetCategory::firstOrCreate(['name' => $d['name']], $d));

        $assetDefs = [
            ['name' => 'MacBook Pro 16" (2024)',   'cat' => 'Bilgisayar & Ekipman', 'price' => 95000,  'qty' => 8],
            ['name' => 'Dell OptiPlex Masaüstü',   'cat' => 'Bilgisayar & Ekipman', 'price' => 45000,  'qty' => 12],
            ['name' => 'HP LaserJet Yazıcı',       'cat' => 'Bilgisayar & Ekipman', 'price' => 18000,  'qty' => 4],
            ['name' => 'Ford Transit Minibüs',     'cat' => 'Araç & Taşıt',         'price' => 950000, 'qty' => 2],
            ['name' => 'Renault Megane Sedan',     'cat' => 'Araç & Taşıt',         'price' => 750000, 'qty' => 3],
            ['name' => 'Ofis Masa Sandalye Seti',  'cat' => 'Mobilya & Demirbaş',   'price' => 12000,  'qty' => 20],
            ['name' => 'Toplantı Masası',          'cat' => 'Mobilya & Demirbaş',   'price' => 35000,  'qty' => 3],
            ['name' => 'Microsoft 365 Lisans',     'cat' => 'Yazılım Lisansı',       'price' => 8500,   'qty' => 15],
            ['name' => 'Adobe Creative Cloud',     'cat' => 'Yazılım Lisansı',       'price' => 12000,  'qty' => 5],
        ];

        $counter = 1;
        foreach ($assetDefs as $def) {
            $cat = $categories->firstWhere('name', $def['cat']);
            for ($i = 0; $i < $def['qty']; $i++) {
                $purchaseDate  = Carbon::today()->subDays(rand(30, 730));
                $purchasePrice = $def['price'] * (1 + rand(-5, 5) / 100);
                $ageYears      = $purchaseDate->diffInYears(Carbon::today());
                $currentValue  = max($purchasePrice * pow(1 - $cat->depreciation_rate / 100, $ageYears), $purchasePrice * 0.1);

                Asset::create([
                    'name'           => $def['name'].($def['qty'] > 1 ? ' #'.str_pad($i + 1, 2, '0', STR_PAD_LEFT) : ''),
                    'asset_code'     => 'AST-'.str_pad($counter++, 5, '0', STR_PAD_LEFT),
                    'category_id'    => $cat->id,
                    'assigned_to'    => rand(0, 3) > 0 ? $employees->random()->id : null,
                    'location_id'    => $warehouse?->id,
                    'purchase_date'  => $purchaseDate,
                    'purchase_price' => round($purchasePrice, 2),
                    'current_value'  => round($currentValue, 2),
                    'status'         => rand(0, 10) > 1 ? 'active' : 'in_repair',
                ]);
            }
        }

        $this->command?->line('  Assets: oluşturuldu.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GİDERLER
    // ──────────────────────────────────────────────────────────────────────────

    private function seedExpenses(User $admin): void
    {
        if (Expense::exists()) {
            $this->command?->line('  Expenses: mevcut kayıtlar korundu.');
            return;
        }

        $expenseDefs = [
            ['title' => 'Ofis Kira Ödemesi',            'category' => 'rent',      'amount' => 85000,  'method' => 'bank_transfer'],
            ['title' => 'Elektrik & Doğalgaz',           'category' => 'utilities', 'amount' => 12500,  'method' => 'bank_transfer'],
            ['title' => 'İnternet & Telefon',            'category' => 'utilities', 'amount' => 4800,   'method' => 'bank_transfer'],
            ['title' => 'Temizlik Hizmeti',              'category' => 'office',    'amount' => 7200,   'method' => 'bank_transfer'],
            ['title' => 'Kırtasiye & Ofis Malzemesi',   'category' => 'office',    'amount' => 3500,   'method' => 'credit_card'],
            ['title' => 'Personel Yemek Kartı',         'category' => 'other',     'amount' => 45000,  'method' => 'bank_transfer'],
            ['title' => 'Seyahat - İstanbul Ankara',    'category' => 'travel',    'amount' => 2800,   'method' => 'credit_card'],
            ['title' => 'Fuar & Etkinlik Katılımı',     'category' => 'marketing', 'amount' => 18000,  'method' => 'bank_transfer'],
            ['title' => 'Dijital Reklam Harcaması',     'category' => 'marketing', 'amount' => 32000,  'method' => 'credit_card'],
            ['title' => 'Muhasebe Danışmanlığı',        'category' => 'other',     'amount' => 9500,   'method' => 'bank_transfer'],
            ['title' => 'Sunucu Barındırma (Cloud)',    'category' => 'other',     'amount' => 14200,  'method' => 'credit_card'],
            ['title' => 'Sigorta Primleri',             'category' => 'other',     'amount' => 28000,  'method' => 'bank_transfer'],
        ];

        for ($month = 1; $month <= 6; $month++) {
            foreach ($expenseDefs as $def) {
                $variation = rand(-8, 8);
                Expense::create([
                    'title'          => $def['title'],
                    'category'       => $def['category'],
                    'amount'         => round($def['amount'] * (1 + $variation / 100), 2),
                    'expense_date'   => Carbon::create(2026, $month, rand(1, 28)),
                    'payment_method' => $def['method'],
                    'created_by'     => $admin->id,
                ]);
            }
        }

        $this->command?->line('  Expenses: 6 aylık gider kaydı oluşturuldu.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SATIN ALMA SİPARİŞLERİ
    // ──────────────────────────────────────────────────────────────────────────

    private function seedPurchaseOrders(
        \Illuminate\Support\Collection $suppliers,
        \Illuminate\Support\Collection $products,
        ?Warehouse $warehouse,
        User $admin
    ): void {
        if (PurchaseOrder::exists()) {
            $this->command?->line('  PurchaseOrders: mevcut kayıtlar korundu.');
            return;
        }

        if ($suppliers->isEmpty() || ! $warehouse) {
            return;
        }

        $statuses = ['received', 'received', 'sent', 'partial', 'draft', 'cancelled'];
        $counter  = 1;

        for ($i = 0; $i < 60; $i++) {
            $supplier   = $suppliers->random();
            $status     = $statuses[array_rand($statuses)];
            $orderDate  = Carbon::today()->subDays(rand(0, 365));
            $itemCount  = rand(1, 5);
            $subtotal   = 0;

            $po = PurchaseOrder::create([
                'po_number'     => 'PO-'.str_pad($counter++, 5, '0', STR_PAD_LEFT),
                'supplier_id'   => $supplier->id,
                'warehouse_id'  => $warehouse->id,
                'status'        => $status,
                'order_date'    => $orderDate,
                'expected_date' => $orderDate->copy()->addDays(rand(7, 30)),
                'received_date' => in_array($status, ['received', 'partial']) ? $orderDate->copy()->addDays(rand(5, 20)) : null,
                'subtotal'      => 0,
                'tax_amount'    => 0,
                'total'         => 0,
                'currency'      => 'TRY',
                'created_by'    => $admin->id,
            ]);

            for ($j = 0; $j < $itemCount; $j++) {
                $product  = $products->random();
                $qty      = rand(5, 100);
                $price    = $product->purchase_price * (1 + rand(-5, 10) / 100);
                $lineTotal = round($qty * $price, 2);
                $subtotal += $lineTotal;

                PurchaseOrderItem::create([
                    'purchase_order_id'   => $po->id,
                    'product_id'          => $product->id,
                    'quantity'            => $qty,
                    'received_quantity'   => in_array($status, ['received']) ? $qty : (in_array($status, ['partial']) ? rand(1, $qty - 1) : 0),
                    'unit_price'          => round($price, 2),
                    'tax_rate'            => 20,
                    'discount_rate'       => 0,
                    'line_total'          => $lineTotal,
                ]);
            }

            $tax   = round($subtotal * 0.20, 2);
            $po->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal + $tax]);
        }

        $this->command?->line('  PurchaseOrders: 60 sipariş oluşturuldu.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SATIŞ SİPARİŞLERİ
    // ──────────────────────────────────────────────────────────────────────────

    private function seedSalesOrders(
        \Illuminate\Support\Collection $customers,
        \Illuminate\Support\Collection $products,
        ?Warehouse $warehouse,
        User $admin
    ): void {
        if (SalesOrder::exists()) {
            $this->command?->line('  SalesOrders: mevcut kayıtlar korundu.');
            return;
        }

        if ($customers->isEmpty() || ! $warehouse) {
            return;
        }

        $statuses = ['delivered', 'delivered', 'shipped', 'confirmed', 'draft', 'cancelled'];
        $counter  = 1;

        for ($i = 0; $i < 80; $i++) {
            $customer  = $customers->random();
            $status    = $statuses[array_rand($statuses)];
            $orderDate = Carbon::today()->subDays(rand(0, 365));
            $itemCount = rand(1, 6);
            $subtotal  = 0;

            $so = SalesOrder::create([
                'so_number'               => 'SO-'.str_pad($counter++, 5, '0', STR_PAD_LEFT),
                'customer_id'             => $customer->id,
                'warehouse_id'            => $warehouse->id,
                'status'                  => $status,
                'order_date'              => $orderDate,
                'requested_delivery_date' => $orderDate->copy()->addDays(rand(3, 21)),
                'actual_delivery_date'    => in_array($status, ['delivered']) ? $orderDate->copy()->addDays(rand(2, 15)) : null,
                'subtotal'                => 0,
                'discount_amount'         => 0,
                'tax_amount'              => 0,
                'total'                   => 0,
                'created_by'              => $admin->id,
            ]);

            for ($j = 0; $j < $itemCount; $j++) {
                $product   = $products->random();
                $qty       = rand(1, 50);
                $price     = $product->sale_price;
                $lineTotal = round($qty * $price, 2);
                $subtotal += $lineTotal;

                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id'     => $product->id,
                    'quantity'       => $qty,
                    'unit_price'     => $price,
                    'tax_rate'       => 20,
                    'discount_rate'  => rand(0, 1) ? rand(0, 15) : 0,
                    'line_total'     => $lineTotal,
                ]);
            }

            $tax = round($subtotal * 0.20, 2);
            $so->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal + $tax]);
        }

        $this->command?->line('  SalesOrders: 80 sipariş oluşturuldu.');
    }
}
