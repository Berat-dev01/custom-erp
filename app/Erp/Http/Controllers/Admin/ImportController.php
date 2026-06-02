<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Customer;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\Position;
use App\Erp\Models\Product;
use App\Erp\Models\ProductCategory;
use App\Erp\Models\StockLevel;
use App\Erp\Models\Supplier;
use App\Erp\Models\Unit;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Export\ExcelExportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Reader\XLSX\Options as ReaderOptions;

class ImportController extends Controller
{
    public function __construct(private ExcelExportService $excel) {}

    public function index()
    {
        Gate::authorize('erp.settings.manage');

        return view('erp::admin.import.index');
    }

    // ── Şablon İndirme ────────────────────────────────────────────────

    public function templateEmployees()
    {
        Gate::authorize('erp.employees.create');

        return $this->excel->download('calisanlar-sablon.xlsx',
            ['employee_number', 'first_name', 'last_name', 'email', 'hire_date (YYYY-MM-DD)', 'employment_type (full_time/part_time/contract/intern)', 'department_name', 'position_name'],
            [['EMP-00001', 'Ali', 'Yılmaz', 'ali@example.com', '2024-01-01', 'full_time', 'IT', 'Developer']]
        );
    }

    public function templateProducts()
    {
        Gate::authorize('erp.products.create');

        return $this->excel->download('urunler-sablon.xlsx',
            ['sku', 'name', 'category_name', 'unit_abbreviation', 'purchase_price', 'sale_price', 'tax_rate', 'reorder_point'],
            [['SKU-001', 'Örnek Ürün', 'Elektronik', 'pcs', '100.00', '150.00', '20', '5']]
        );
    }

    public function templateCustomers()
    {
        Gate::authorize('erp.customers.create');

        return $this->excel->download('musteriler-sablon.xlsx',
            ['name', 'email', 'phone', 'tax_number', 'address', 'payment_terms_days'],
            [['ABC Ltd.', 'info@abc.com', '0212 555 0000', '1234567890', 'İstanbul', '30']]
        );
    }

    public function templateSuppliers()
    {
        Gate::authorize('erp.suppliers.create');

        return $this->excel->download('tedarikciler-sablon.xlsx',
            ['name', 'email', 'phone', 'tax_number', 'address', 'payment_terms_days'],
            [['XYZ Tedarik A.Ş.', 'satis@xyz.com', '0216 444 0000', '9876543210', 'Ankara', '30']]
        );
    }

    public function templateStockLevels()
    {
        Gate::authorize('erp.stock_movements.create');

        return $this->excel->download('stok-seviyeleri-sablon.xlsx',
            ['sku', 'warehouse_code', 'quantity'],
            [['SKU-001', 'MRK', '100']]
        );
    }

    // ── İçe Aktarma ───────────────────────────────────────────────────

    public function importEmployees(Request $request)
    {
        Gate::authorize('erp.employees.create');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120']]);

        $rows   = $this->readXlsx($request->file('file'));
        $result = ['imported' => 0, 'errors' => []];
        $counter = Employee::withTrashed()->count();

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            [$empNo, $firstName, $lastName, $email, $hireDate, $empType, $deptName, $posName] = array_pad(array_values($row), 8, null);

            $v = Validator::make([
                'email'           => $email,
                'hire_date'       => $hireDate,
                'employment_type' => $empType,
            ], [
                'email'           => ['required', 'email'],
                'hire_date'       => ['required', 'date'],
                'employment_type' => ['required', 'in:full_time,part_time,contract,intern'],
            ]);

            if ($v->fails()) {
                $result['errors'][] = "Satır {$rowNum}: ".implode(', ', $v->errors()->all());
                continue;
            }

            if (Employee::where('email', $email)->exists()) {
                $result['errors'][] = "Satır {$rowNum}: {$email} zaten mevcut.";
                continue;
            }

            $dept = $deptName ? Department::firstOrCreate(['name' => $deptName], ['name' => $deptName, 'is_active' => true]) : null;
            $pos  = ($posName && $dept) ? Position::firstOrCreate(
                ['name' => $posName, 'department_id' => $dept->id],
                ['name' => $posName, 'department_id' => $dept->id, 'level' => 'mid', 'is_active' => true]
            ) : null;

            Employee::create([
                'employee_number' => $empNo ?: 'EMP-'.str_pad(++$counter, 5, '0', STR_PAD_LEFT),
                'first_name'      => $firstName,
                'last_name'       => $lastName,
                'email'           => $email,
                'hire_date'       => $hireDate,
                'employment_type' => $empType,
                'status'          => 'active',
                'department_id'   => $dept?->id,
                'position_id'     => $pos?->id,
            ]);

            $result['imported']++;
        }

        return back()->with('import_result', $result);
    }

    public function importProducts(Request $request)
    {
        Gate::authorize('erp.products.create');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120']]);

        $rows   = $this->readXlsx($request->file('file'));
        $result = ['imported' => 0, 'errors' => []];
        $counter = Product::withTrashed()->count();

        foreach ($rows as $i => $row) {
            $rowNum  = $i + 2;
            [$sku, $name, $catName, $unitAbbr, $purchasePrice, $salePrice, $taxRate, $reorderPoint] = array_pad(array_values($row), 8, null);

            if (! $sku || ! $name) {
                $result['errors'][] = "Satır {$rowNum}: sku ve name zorunlu.";
                continue;
            }

            if (Product::where('sku', $sku)->exists()) {
                $result['errors'][] = "Satır {$rowNum}: {$sku} zaten mevcut.";
                continue;
            }

            $cat  = $catName ? ProductCategory::firstOrCreate(['name' => $catName], ['name' => $catName, 'slug' => \Illuminate\Support\Str::slug($catName), 'is_active' => true]) : null;
            $unit = $unitAbbr ? Unit::firstOrCreate(['abbreviation' => $unitAbbr], ['name' => $unitAbbr, 'abbreviation' => $unitAbbr]) : Unit::first();

            if (! $unit) {
                $result['errors'][] = "Satır {$rowNum}: birim bulunamadı ({$unitAbbr}).";
                continue;
            }

            Product::create([
                'sku'            => $sku,
                'name'           => $name,
                'category_id'    => $cat?->id,
                'unit_id'        => $unit->id,
                'purchase_price' => (float) str_replace(',', '.', (string) $purchasePrice),
                'sale_price'     => (float) str_replace(',', '.', (string) $salePrice),
                'tax_rate'       => (float) ($taxRate ?? 20),
                'reorder_point'  => (float) ($reorderPoint ?? 0),
                'type'           => 'product',
                'track_stock'    => true,
                'is_active'      => true,
            ]);

            $result['imported']++;
        }

        return back()->with('import_result', $result);
    }

    public function importCustomers(Request $request)
    {
        Gate::authorize('erp.customers.create');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120']]);

        $rows   = $this->readXlsx($request->file('file'));
        $result = ['imported' => 0, 'errors' => []];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            [$name, $email, $phone, $taxNumber, $address, $paymentTerms] = array_pad(array_values($row), 6, null);

            if (! $name) {
                $result['errors'][] = "Satır {$rowNum}: name zorunlu.";
                continue;
            }

            if ($email && Customer::where('email', $email)->exists()) {
                $result['errors'][] = "Satır {$rowNum}: {$email} zaten mevcut.";
                continue;
            }

            Customer::create([
                'name'               => $name,
                'email'              => $email ?: null,
                'phone'              => $phone ?: null,
                'tax_number'         => $taxNumber ?: null,
                'address'            => $address ?: null,
                'payment_terms_days' => (int) ($paymentTerms ?: 30),
                'status'             => 'active',
            ]);

            $result['imported']++;
        }

        return back()->with('import_result', $result);
    }

    public function importSuppliers(Request $request)
    {
        Gate::authorize('erp.suppliers.create');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120']]);

        $rows   = $this->readXlsx($request->file('file'));
        $result = ['imported' => 0, 'errors' => []];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            [$name, $email, $phone, $taxNumber, $address, $paymentTerms] = array_pad(array_values($row), 6, null);

            if (! $name) {
                $result['errors'][] = "Satır {$rowNum}: name zorunlu.";
                continue;
            }

            Supplier::create([
                'name'               => $name,
                'email'              => $email ?: null,
                'phone'              => $phone ?: null,
                'tax_number'         => $taxNumber ?: null,
                'address'            => $address ?: null,
                'payment_terms_days' => (int) ($paymentTerms ?: 30),
                'status'             => 'active',
            ]);

            $result['imported']++;
        }

        return back()->with('import_result', $result);
    }

    public function importStockLevels(Request $request)
    {
        Gate::authorize('erp.stock_movements.create');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120']]);

        $rows   = $this->readXlsx($request->file('file'));
        $result = ['imported' => 0, 'errors' => []];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            [$sku, $warehouseCode, $quantity] = array_pad(array_values($row), 3, null);

            $product   = Product::where('sku', $sku)->first();
            $warehouse = Warehouse::where('code', $warehouseCode)->first();

            if (! $product) {
                $result['errors'][] = "Satır {$rowNum}: ürün bulunamadı ({$sku}).";
                continue;
            }

            if (! $warehouse) {
                $result['errors'][] = "Satır {$rowNum}: depo bulunamadı ({$warehouseCode}).";
                continue;
            }

            StockLevel::updateOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                ['quantity' => (float) $quantity, 'reserved_quantity' => 0]
            );

            $result['imported']++;
        }

        return back()->with('import_result', $result);
    }

    private function readXlsx(\Illuminate\Http\UploadedFile $file): array
    {
        $options = new ReaderOptions();
        $reader  = new Reader($options);
        $reader->open($file->getRealPath());

        $rows    = [];
        $headers = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $idx => $row) {
                $cells = array_map(fn ($c) => $c->getValue(), $row->getCells());

                if ($idx === 1) {
                    $headers = $cells;
                    continue;
                }

                if (! array_filter($cells, fn ($v) => $v !== null && $v !== '')) {
                    continue;
                }

                $rows[] = array_combine(
                    array_slice($headers, 0, count($cells)),
                    $cells
                );
            }
            break;
        }

        $reader->close();

        return $rows;
    }
}
