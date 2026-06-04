<?php

namespace Database\Seeders;

use App\Erp\Models\AssetCategory;
use App\Erp\Models\Customer;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\Expense;
use App\Erp\Models\Invoice;
use App\Erp\Models\InvoiceItem;
use App\Erp\Models\Payment;
use App\Erp\Models\Position;
use App\Erp\Models\Product;
use App\Erp\Models\ProductCategory;
use App\Erp\Models\Project;
use App\Erp\Models\ProjectTask;
use App\Erp\Models\PurchaseOrder;
use App\Erp\Models\PurchaseOrderItem;
use App\Erp\Models\SalesOrder;
use App\Erp\Models\SalesOrderItem;
use App\Erp\Models\StockLevel;
use App\Erp\Models\StockMovement;
use App\Erp\Models\Supplier;
use App\Erp\Models\Unit;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Finance\InvoiceService;
use App\Erp\Services\Procurement\PurchaseOrderService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ErpSeeder extends Seeder
{
    public function run(): void
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

        // ── Birimler ──────────────────────────────────────────────────────
        $units = collect([
            ['name' => 'Adet', 'abbreviation' => 'pcs'],
            ['name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['name' => 'Litre', 'abbreviation' => 'L'],
            ['name' => 'Metre', 'abbreviation' => 'm'],
        ])->map(fn ($u) => Unit::firstOrCreate(['abbreviation' => $u['abbreviation']], $u));

        // ── Ürün Kategorileri ─────────────────────────────────────────────
        $cats = collect([
            ['name' => 'Elektronik',   'slug' => 'elektronik'],
            ['name' => 'Ofis Malzeme', 'slug' => 'ofis-malzeme'],
            ['name' => 'Sarf Malzeme', 'slug' => 'sarf-malzeme'],
        ])->map(fn ($c) => ProductCategory::firstOrCreate(['slug' => $c['slug']], $c));

        // ── Depolar ───────────────────────────────────────────────────────
        $mainWarehouse = Warehouse::firstOrCreate(['code' => 'MRK'], [
            'name'       => 'Merkez Depo',
            'code'       => 'MRK',
            'is_default' => true,
            'is_active'  => true,
        ]);

        $secondWarehouse = Warehouse::firstOrCreate(['code' => 'ANK'], [
            'name'       => 'Ankara Depo',
            'code'       => 'ANK',
            'is_default' => false,
            'is_active'  => true,
        ]);

        // ── Departmanlar ──────────────────────────────────────────────────
        $depts = collect([
            ['name' => 'Bilgi Teknolojileri', 'code' => 'BT'],
            ['name' => 'İnsan Kaynakları',    'code' => 'IK'],
            ['name' => 'Muhasebe & Finans',   'code' => 'MF'],
        ])->map(fn ($d) => Department::firstOrCreate(['code' => $d['code']], array_merge($d, ['is_active' => true])));

        // ── Pozisyonlar ───────────────────────────────────────────────────
        $positions = collect([
            ['name' => 'Yazılım Geliştirici', 'department_id' => $depts[0]->id, 'level' => 'mid'],
            ['name' => 'Kıdemli Geliştirici', 'department_id' => $depts[0]->id, 'level' => 'senior'],
            ['name' => 'İK Uzmanı',           'department_id' => $depts[1]->id, 'level' => 'mid'],
            ['name' => 'İK Müdürü',           'department_id' => $depts[1]->id, 'level' => 'manager'],
            ['name' => 'Muhasebeci',           'department_id' => $depts[2]->id, 'level' => 'mid'],
        ])->map(fn ($p) => Position::firstOrCreate(['name' => $p['name']], array_merge($p, ['is_active' => true])));

        // ── Çalışanlar ────────────────────────────────────────────────────
        $employees = [];
        $employeeData = [
            ['Ahmet', 'Kaya',    'ahmet.kaya@erp.test',    $depts[0]->id, $positions[0]->id],
            ['Mehmet', 'Demir',  'mehmet.demir@erp.test',  $depts[0]->id, $positions[1]->id],
            ['Ayşe', 'Çelik',   'ayse.celik@erp.test',    $depts[1]->id, $positions[2]->id],
            ['Fatma', 'Şahin',  'fatma.sahin@erp.test',   $depts[1]->id, $positions[3]->id],
            ['Ali', 'Yıldız',   'ali.yildiz@erp.test',    $depts[2]->id, $positions[4]->id],
            ['Zeynep', 'Arslan','zeynep.arslan@erp.test',  $depts[0]->id, $positions[0]->id],
            ['Mustafa', 'Koç',  'mustafa.koc@erp.test',   $depts[2]->id, $positions[4]->id],
            ['Elif', 'Yılmaz',  'elif.yilmaz@erp.test',   $depts[1]->id, $positions[2]->id],
            ['Hasan', 'Aydın',  'hasan.aydin@erp.test',   $depts[0]->id, $positions[1]->id],
            ['Aysel', 'Taş',    'aysel.tas@erp.test',     $depts[2]->id, $positions[4]->id],
        ];

        foreach ($employeeData as $i => [$first, $last, $email, $deptId, $posId]) {
            $employees[] = Employee::firstOrCreate(['email' => $email], [
                'employee_number' => 'EMP-'.str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'first_name'      => $first,
                'last_name'       => $last,
                'email'           => $email,
                'department_id'   => $deptId,
                'position_id'     => $posId,
                'hire_date'       => Carbon::now()->subMonths(rand(3, 36)),
                'employment_type' => 'full_time',
                'status'          => 'active',
            ]);
        }

        // ── Ürünler ───────────────────────────────────────────────────────
        $productDefs = [
            ['SKU-00001', 'Laptop 15"',            $cats[0]->id, $units[0]->id, 15000, 22000],
            ['SKU-00002', 'Kablosuz Mouse',         $cats[0]->id, $units[0]->id, 250,   400],
            ['SKU-00003', 'Mekanik Klavye',         $cats[0]->id, $units[0]->id, 800,  1200],
            ['SKU-00004', 'Monitör 27"',            $cats[0]->id, $units[0]->id, 5000,  7500],
            ['SKU-00005', 'USB-C Hub',              $cats[0]->id, $units[0]->id, 300,   500],
            ['SKU-00006', 'A4 Fotokopi Kağıdı 500', $cats[1]->id, $units[0]->id, 80,   120],
            ['SKU-00007', 'Tükenmez Kalem (12li)',  $cats[1]->id, $units[0]->id, 25,    40],
            ['SKU-00008', 'Zımba Makinesi',         $cats[1]->id, $units[0]->id, 120,  180],
            ['SKU-00009', 'Yapışkanlı Not (100lü)', $cats[1]->id, $units[0]->id, 15,    25],
            ['SKU-00010', 'Dosya (50li Paket)',     $cats[1]->id, $units[0]->id, 60,    90],
            ['SKU-00011', 'Temizlik Bezi (10lu)',   $cats[2]->id, $units[0]->id, 30,    50],
            ['SKU-00012', 'Deterjan 5L',            $cats[2]->id, $units[1]->id, 45,    70],
            ['SKU-00013', 'Çay 1kg',                $cats[2]->id, $units[1]->id, 80,   120],
            ['SKU-00014', 'Su Bardağı (6lı)',       $cats[2]->id, $units[0]->id, 35,    55],
            ['SKU-00015', 'Ethernet Kablosu 10m',   $cats[0]->id, $units[3]->id, 60,    90],
            ['SKU-00016', 'HDMI Kablo 2m',          $cats[0]->id, $units[0]->id, 80,   130],
            ['SKU-00017', 'Yazıcı Toner',           $cats[0]->id, $units[0]->id, 400,  600],
            ['SKU-00018', 'Güç Uzatma Kablosu',     $cats[0]->id, $units[0]->id, 150,  220],
            ['SKU-00019', 'Laptop Çantası',         $cats[1]->id, $units[0]->id, 350,  500],
            ['SKU-00020', 'Isıtıcı Kupa',           $cats[2]->id, $units[0]->id, 45,    70],
        ];

        $products = [];
        foreach ($productDefs as [$sku, $name, $catId, $unitId, $purchase, $sale]) {
            $products[] = Product::firstOrCreate(['sku' => $sku], [
                'sku'            => $sku,
                'name'           => $name,
                'category_id'    => $catId,
                'unit_id'        => $unitId,
                'purchase_price' => $purchase,
                'sale_price'     => $sale,
                'tax_rate'       => 20,
                'type'           => 'product',
                'track_stock'    => true,
                'reorder_point'  => 5,
                'is_active'      => true,
            ]);
        }

        // Merkez depoda başlangıç stokları
        foreach (array_slice($products, 0, 15) as $i => $product) {
            StockLevel::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $mainWarehouse->id],
                ['quantity' => rand(20, 200), 'reserved_quantity' => 0]
            );
            StockMovement::create([
                'product_id'   => $product->id,
                'warehouse_id' => $mainWarehouse->id,
                'type'         => 'in',
                'quantity'     => rand(20, 200),
                'notes'        => 'Açılış stoku',
                'created_by'   => $admin->id,
            ]);
        }

        // Ankara deposunda bazı ürünler
        foreach (array_slice($products, 0, 5) as $product) {
            StockLevel::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $secondWarehouse->id],
                ['quantity' => rand(5, 50), 'reserved_quantity' => 0]
            );
        }

        // ── Tedarikçiler ──────────────────────────────────────────────────
        $suppliers = [];
        $supplierData = [
            ['TechSupply A.Ş.',    'info@techsupply.com',    '0212-111-0001'],
            ['Ofis Dünyası Ltd.',  'satis@ofisdunyasi.com',  '0312-222-0002'],
            ['Delta Elektronik',   'siparis@delta.com',      '0232-333-0003'],
            ['Güven Kırtasiye',    'guven@kirtasiye.com',    '0216-444-0004'],
            ['Metro Tedarik A.Ş.','metro@tedarik.com',      '0212-555-0005'],
        ];

        foreach ($supplierData as [$name, $email, $phone]) {
            $suppliers[] = Supplier::firstOrCreate(['email' => $email], [
                'name'               => $name,
                'email'              => $email,
                'phone'              => $phone,
                'payment_terms_days' => 30,
                'status'             => 'active',
            ]);
        }

        // ── Satın Alma Siparişleri ────────────────────────────────────────
        $poService = app(PurchaseOrderService::class);

        // PO 1 — taslak
        $po1 = PurchaseOrder::firstOrCreate(['po_number' => 'PO-2026-00001'], [
            'po_number'    => 'PO-2026-00001',
            'supplier_id'  => $suppliers[0]->id,
            'warehouse_id' => $mainWarehouse->id,
            'status'       => 'draft',
            'order_date'   => Carbon::now()->subDays(10),
            'expected_date'=> Carbon::now()->addDays(5),
            'currency'     => 'TRY',
            'created_by'   => $admin->id,
        ]);

        PurchaseOrderItem::firstOrCreate(['purchase_order_id' => $po1->id, 'product_id' => $products[0]->id], [
            'purchase_order_id' => $po1->id,
            'product_id'        => $products[0]->id,
            'quantity'          => 5,
            'received_quantity' => 0,
            'unit_price'        => 14000,
            'tax_rate'          => 20,
            'discount_rate'     => 0,
            'line_total'        => 5 * 14000 * 1.2,
        ]);

        // PO 2 — gönderildi
        $po2 = PurchaseOrder::firstOrCreate(['po_number' => 'PO-2026-00002'], [
            'po_number'    => 'PO-2026-00002',
            'supplier_id'  => $suppliers[1]->id,
            'warehouse_id' => $mainWarehouse->id,
            'status'       => 'sent',
            'order_date'   => Carbon::now()->subDays(5),
            'expected_date'=> Carbon::now()->addDays(10),
            'currency'     => 'TRY',
            'created_by'   => $admin->id,
        ]);

        // PO 3 — teslim alındı
        PurchaseOrder::firstOrCreate(['po_number' => 'PO-2026-00003'], [
            'po_number'     => 'PO-2026-00003',
            'supplier_id'   => $suppliers[2]->id,
            'warehouse_id'  => $mainWarehouse->id,
            'status'        => 'received',
            'order_date'    => Carbon::now()->subDays(20),
            'received_date' => Carbon::now()->subDays(15),
            'currency'      => 'TRY',
            'subtotal'      => 2000,
            'tax_amount'    => 400,
            'total'         => 2400,
            'created_by'    => $admin->id,
        ]);

        // ── Müşteriler ────────────────────────────────────────────────────
        $customers = [];
        $customerData = [
            ['ABC Teknoloji A.Ş.',    'muhasebe@abctech.com',   '0212-100-0010'],
            ['XYZ Yazılım Ltd.',      'finans@xyz.com',         '0212-200-0020'],
            ['Deniz Holding',         'satin@denizhol.com',     '0216-300-0030'],
            ['Kaya İnşaat A.Ş.',      'kaya@insaat.com',        '0232-400-0040'],
            ['Güneş Enerji Ltd.',     'gunes@enerji.com',       '0312-500-0050'],
            ['Mavi Mühendislik',      'mavi@muhendislik.com',   '0212-600-0060'],
            ['Demir Makine A.Ş.',     'demir@makine.com',       '0262-700-0070'],
            ['Altın Gıda Ltd.',       'altin@gida.com',         '0224-800-0080'],
            ['Yıldız Tekstil A.Ş.',  'yildiz@tekstil.com',    '0212-900-0090'],
            ['Bora Turizm Ltd.',      'bora@turizm.com',        '0212-010-0100'],
        ];

        foreach ($customerData as [$name, $email, $phone]) {
            $customers[] = Customer::firstOrCreate(['email' => $email], [
                'name'               => $name,
                'email'              => $email,
                'phone'              => $phone,
                'payment_terms_days' => 30,
                'credit_limit'       => rand(50000, 500000),
                'status'             => 'active',
            ]);
        }

        // ── Satış Siparişleri ─────────────────────────────────────────────
        foreach (array_slice($customers, 0, 5) as $i => $customer) {
            $so = SalesOrder::firstOrCreate(['so_number' => 'SO-2026-'.str_pad($i + 1, 5, '0', STR_PAD_LEFT)], [
                'so_number'    => 'SO-2026-'.str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'customer_id'  => $customer->id,
                'warehouse_id' => $mainWarehouse->id,
                'order_date'   => Carbon::now()->subDays(rand(1, 30)),
                'status'       => $i < 2 ? 'confirmed' : 'draft',
                'subtotal'     => 5000,
                'tax_amount'   => 1000,
                'total'        => 6000,
                'created_by'   => $admin->id,
            ]);

            SalesOrderItem::firstOrCreate(
                ['sales_order_id' => $so->id, 'product_id' => $products[$i]->id],
                [
                    'sales_order_id' => $so->id,
                    'product_id'     => $products[$i]->id,
                    'quantity'       => rand(1, 5),
                    'unit_price'     => $products[$i]->sale_price,
                    'tax_rate'       => 20,
                    'discount_rate'  => 0,
                    'line_total'     => $products[$i]->sale_price * 1.2,
                ]
            );
        }

        // ── Faturalar ─────────────────────────────────────────────────────
        $invoiceService = app(InvoiceService::class);

        $invoiceDefs = [
            ['INV-2026-00001', 'paid',    30, 30,  1000, 200, 1200, 1200],
            ['INV-2026-00002', 'paid',    60, 15,  2500, 500, 3000, 3000],
            ['INV-2026-00003', 'draft',   10, 20,  800,  160, 960,  0   ],
            ['INV-2026-00004', 'draft',   5,  30,  1500, 300, 1800, 0   ],
            ['INV-2026-00005', 'overdue', 90, -10, 3000, 600, 3600, 0   ],
        ];

        foreach ($invoiceDefs as $i => [$num, $status, $daysAgo, $dueDiff, $sub, $tax, $total, $paid]) {
            $invoice = Invoice::firstOrCreate(['invoice_number' => $num], [
                'invoice_number'   => $num,
                'type'             => 'sale',
                'invoiceable_type' => 'erp_customer',
                'invoiceable_id'   => $customers[$i]->id,
                'status'           => $status,
                'issue_date'       => Carbon::now()->subDays($daysAgo),
                'due_date'         => Carbon::now()->subDays($daysAgo)->addDays(30 + $dueDiff),
                'subtotal'         => $sub,
                'tax_amount'       => $tax,
                'total'            => $total,
                'paid_amount'      => $paid,
                'created_by'       => $admin->id,
            ]);

            InvoiceItem::firstOrCreate(['invoice_id' => $invoice->id], [
                'invoice_id'  => $invoice->id,
                'description' => $products[$i]->name,
                'quantity'    => 1,
                'unit_price'  => $sub,
                'tax_rate'    => 20,
                'line_total'  => $total,
            ]);

            if ($paid > 0) {
                Payment::firstOrCreate(['invoice_id' => $invoice->id], [
                    'invoice_id'   => $invoice->id,
                    'amount'       => $paid,
                    'payment_date' => Carbon::now()->subDays($daysAgo - 5),
                    'method'       => 'bank_transfer',
                    'created_by'   => $admin->id,
                ]);
            }
        }

        // ── Projeler ──────────────────────────────────────────────────────
        $projectDefs = [
            ['ERP Entegrasyonu',    'ERP-INT',  'active',   $customers[0]->id, $employees[0]->id, 50000],
            ['Web Sitesi Yenileme', 'WEB-YEN',  'planning', $customers[1]->id, $employees[1]->id, 30000],
        ];

        foreach ($projectDefs as [$name, $code, $status, $custId, $mgrId, $budget]) {
            $project = Project::firstOrCreate(['code' => $code], [
                'name'        => $name,
                'code'        => $code,
                'status'      => $status,
                'customer_id' => $custId,
                'manager_id'  => $mgrId,
                'start_date'  => Carbon::now()->subDays(30),
                'end_date'    => Carbon::now()->addMonths(3),
                'budget'      => $budget,
                'spent'       => 0,
            ]);

            $tasks = [
                ['Analiz ve Planlama', 'done',        'high',   10],
                ['Geliştirme',         'in_progress', 'urgent', 40],
                ['Test ve Yayın',      'todo',        'medium', 20],
            ];

            foreach ($tasks as [$taskName, $taskStatus, $priority, $estimatedHours]) {
                ProjectTask::firstOrCreate(
                    ['project_id' => $project->id, 'name' => $taskName],
                    [
                        'project_id'       => $project->id,
                        'name'             => $taskName,
                        'status'           => $taskStatus,
                        'priority'         => $priority,
                        'assignee_id'      => $employees[rand(0, 4)]->id,
                        'estimated_hours'  => $estimatedHours,
                    ]
                );
            }
        }

        // ── Giderler ─────────────────────────────────────────────────────
        $expenseDefs = [
            ['Ofis Kirası Haziran',     'rent',       15000, 'bank_transfer'],
            ['İnternet Faturası',       'utilities',  800,   'bank_transfer'],
            ['Taksi & Seyahat',         'travel',     450,   'cash'],
            ['Ofis Malzeme Alımı',      'office',     1200,  'credit_card'],
            ['Çalışan Eğitimi',         'office',     3500,  'bank_transfer'],
        ];

        foreach ($expenseDefs as [$title, $cat, $amount, $method]) {
            Expense::firstOrCreate(['title' => $title], [
                'title'          => $title,
                'category'       => $cat,
                'amount'         => $amount,
                'expense_date'   => Carbon::now()->subDays(rand(1, 30)),
                'payment_method' => $method,
                'created_by'     => $admin->id,
            ]);
        }

        // ── Sabit Kıymet Kategorisi ───────────────────────────────────────
        AssetCategory::firstOrCreate(['name' => 'Bilgisayar & Ekipman'], [
            'name'               => 'Bilgisayar & Ekipman',
            'useful_life_years'  => 5,
            'depreciation_rate'  => 20,
        ]);

        $this->command?->info('✓ ERP demo verisi başarıyla yüklendi.');
    }
}
