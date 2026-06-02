# ERP Mimari Ağacı — Modüller, Türler ve Klasör Yapısı

> Bu belge ileriki geliştirmelerde "hangi dosyayı nereye koyacağım, hangi sınıfı değiştireceğim" sorularına yanıt verir. Her bölüm gerçek dosya yollarını içerir.

---

## Genel Klasör Haritası

```
erp/
├── app/
│   └── Erp/                          ← Tüm ERP kodu buraya girer
│       ├── Database/
│       │   └── Seeders/              ← Modüle özgü seed dosyaları
│       ├── Http/
│       │   ├── Controllers/
│       │   │   ├── Admin/            ← Web (browser) controller'ları
│       │   │   └── Api/              ← REST API controller'ları
│       │   ├── Middleware/           ← Auth ve erişim middleware'leri
│       │   ├── Requests/             ← Form doğrulama (Store/Update)
│       │   └── Resources/            ← API JSON dönüşüm sınıfları
│       ├── Jobs/                     ← Kuyruğa alınan arka plan işleri
│       ├── Models/                   ← Eloquent modelleri
│       ├── Notifications/            ← Laravel Notification sınıfları
│       ├── Policies/                 ← Gate/Policy yetki sınıfları
│       ├── Services/                 ← İş mantığı servisleri (modüle göre alt klasör)
│       └── Support/                  ← Yardımcı sınıflar (Formatter vb.)
│
├── config/
│   └── erp.php                       ← ERP yapılandırması (izinler, roller, rotalar)
│
├── database/
│   ├── factories/
│   │   └── Erp/                      ← Model factory'leri
│   ├── migrations/                   ← Tüm ERP migration'ları (erp_ prefix)
│   └── seeders/
│       ├── ErpSeeder.php             ← Temel demo verisi
│       └── ErpDemoSeeder.php         ← Büyük demo verisi (500 ürün, 50 çalışan)
│
├── docs/                             ← Bu klasör
├── resources/views/erp/
│   ├── admin/                        ← Modüle göre view klasörleri
│   └── layouts/app.blade.php         ← Tüm ERP view'larının temel layout'u
│
├── routes/
│   ├── erp-web.php                   ← Admin panel rotaları
│   └── erp-api.php                   ← REST API rotaları
│
└── tests/Feature/Erp/                ← Tüm ERP testleri
```

---

## Modül Kataloğu

### Modül Türleri

| Tür | Açıklama |
|-----|---------|
| **Core** | Altyapı — silindi/değiştirildi, her şey çöker |
| **Business** | Ana iş modülleri — bağımsız çalışabilir ama birbirine bağlı |
| **Support** | Yardımcı modüller — diğer modülleri tamamlar |
| **Integration** | Dış sistemlerle köprü |

---

## 1. HR Modülü
**Tür:** Business | **Bağımlılık:** Yok (ilk modül)

### Dosyalar

```
Models/
  Employee.php          → erp_employees tablosu; tüm modüller bu modele FK tutar
  Department.php        → erp_departments (ağaç yapısı: parent_id)
  Position.php          → erp_positions (department_id → FK)
  EmployeeSalary.php    → erp_employee_salaries (maaş geçmişi)
  EmployeeDocument.php  → erp_employee_documents

Http/Controllers/Admin/
  EmployeesController.php
  DepartmentsController.php
  PositionsController.php

Http/Requests/
  StoreEmployeeRequest.php / UpdateEmployeeRequest.php
  StoreDepartmentRequest.php / UpdateDepartmentRequest.php
  StorePositionRequest.php / UpdatePositionRequest.php
  StoreEmployeeSalaryRequest.php

Policies/
  EmployeePolicy.php    → erp.employees.*
  DepartmentPolicy.php  → erp.departments.*
  PositionPolicy.php    → erp.positions.*

resources/views/erp/admin/
  employees/            → index, create, edit, show
  departments/          → index, create, edit
  positions/            → index, create, edit

Tests/
  ErpHrModuleTest.php
  ErpAuthorizationTest.php  (HR yetki testleri dahil)
```

**Yeni özellik eklerken:** Model → Migration → Request → Controller → View → Policy → Route (`erp-web.php`) → Navigation (`ErpNavigation.php`) sırasını takip et.

---

## 2. İzin & Devam Modülü
**Tür:** Business | **Bağımlılık:** HR

```
Models/
  LeaveType.php         → erp_leave_types
  LeaveBalance.php      → erp_leave_balances (çalışan × tip × yıl)
  LeaveRequest.php      → erp_leave_requests
  Attendance.php        → erp_attendance (günlük devam çizelgesi)
  PublicHoliday.php     → erp_public_holidays

Services/HR/
  LeaveService.php      → İzin bakiyesi hesabı, onay/ret, iş günü sayımı

Http/Controllers/Admin/
  LeaveRequestsController.php
  AttendanceController.php

resources/views/erp/admin/
  leave-requests/       → index, create
  attendance/           → index

Tests/
  ErpLeaveTest.php
```

---

## 3. Stok (Inventory) Modülü
**Tür:** Business | **Bağımlılık:** Yok

```
Models/
  Product.php           → erp_products (SKU, fiyat, stok takip bayrağı)
  ProductCategory.php   → erp_product_categories (ağaç yapısı)
  Unit.php              → erp_units (Adet, kg, L, m...)
  Warehouse.php         → erp_warehouses
  StockLevel.php        → erp_stock_levels (product × warehouse)
  StockMovement.php     → erp_stock_movements (in/out/transfer/adjustment)

Services/Inventory/
  StockService.php      → recordMovement(), availableStock(), checkReorderPoints()

Http/Controllers/Admin/
  ProductsController.php
  WarehousesController.php
  StockMovementsController.php

Http/Controllers/Api/
  ProductApiController.php
  StockMovementApiController.php

Http/Resources/
  ProductResource.php
  StockLevelResource.php

Policies/
  ProductPolicy.php     → erp.products.*
  WarehousePolicy.php   → erp.warehouses.*

resources/views/erp/admin/
  products/             → index, create, edit, show
  warehouses/           → index, create, edit
  stock-movements/      → index, create

Tests/
  ErpInventoryModuleTest.php
```

---

## 4. Satın Alma (Procurement) Modülü
**Tür:** Business | **Bağımlılık:** Stok

```
Models/
  Supplier.php          → erp_suppliers
  PurchaseOrder.php     → erp_purchase_orders (PO-YYYY-NNNNN)
  PurchaseOrderItem.php → erp_purchase_order_items

Services/Procurement/
  PurchaseOrderService.php  → generatePoNumber(), receiveItems() → StockService çağırır

Http/Controllers/Admin/
  SuppliersController.php
  PurchaseOrdersController.php

Http/Controllers/Api/
  PurchaseOrderApiController.php

Http/Resources/
  PurchaseOrderResource.php

Policies/
  SupplierPolicy.php    → erp.suppliers.*
  PurchaseOrderPolicy.php → erp.purchase_orders.*

resources/views/erp/admin/
  suppliers/            → index, create, edit
  purchase-orders/      → index, create, show, receive

Tests/
  (ErpInventoryModuleTest içinde teslimat testleri)
```

---

## 5. Satış Modülü
**Tür:** Business | **Bağımlılık:** Stok, Finans

```
Models/
  Customer.php          → erp_customers (crm_contact_id nullable FK ile CRM entegrasyona hazır)
  SalesOrder.php        → erp_sales_orders (SO-YYYY-NNNNN)
  SalesOrderItem.php    → erp_sales_order_items

Services/Sales/
  SalesOrderService.php → generateSoNumber(), confirmOrder() (stok rezerve), deliverOrder() (stok düş), createInvoice()

Http/Controllers/Admin/
  CustomersController.php
  SalesOrdersController.php

Http/Controllers/Api/
  SalesOrderApiController.php

Http/Resources/
  SalesOrderResource.php

Policies/
  CustomerPolicy.php    → erp.customers.*
  SalesOrderPolicy.php  → erp.sales_orders.*

resources/views/erp/admin/
  customers/            → index, create, edit
  sales-orders/         → index, create, show

Tests/
  ErpSalesModuleTest.php
```

---

## 6. Finans Modülü (Fatura & Ödeme)
**Tür:** Business | **Bağımlılık:** Satış, Satın Alma, Muhasebe

```
Models/
  Invoice.php           → erp_invoices (morphs: invoiceable → Customer veya Supplier)
  InvoiceItem.php       → erp_invoice_items
  Payment.php           → erp_payments
  Expense.php           → erp_expenses

Services/Finance/
  InvoiceService.php    → generateInvoiceNumber(), recordPayment(), markOverdueInvoices(), generatePdf()
  ExpenseService.php    → thisMonth(), totals()

Http/Controllers/Admin/
  InvoicesController.php   (+ sendEfatura, cancelEfatura action'ları)
  PaymentsController.php
  ExpensesController.php

Http/Controllers/Api/
  InvoiceApiController.php

Http/Resources/
  InvoiceResource.php

Policies/
  InvoicePolicy.php     → erp.invoices.*
  ExpensePolicy.php     → erp.expenses.*

resources/views/erp/admin/
  invoices/             → index, create, show
  expenses/             → index, create, edit

Tests/
  ErpFinanceModuleTest.php
```

---

## 7. Bordro (Payroll) Modülü
**Tür:** Business | **Bağımlılık:** HR, Muhasebe

```
Models/
  PayrollParameter.php  → erp_payroll_parameters (yıl bazlı yasal oranlar)
  PayrollRun.php        → erp_payroll_runs (yıl × ay)
  Payslip.php           → erp_payslips (çalışan başına bordro detayı, JSON breakdown)
  EmployeeSalary.php    → erp_employee_salaries (maaş geçmişi — HR'da tanımlı)

Services/Payroll/
  PayrollService.php            → processPayrollRun(), calculatePayslip(), generatePayslipPdf()
  TurkishPayrollCalculator.php  → SGK, gelir vergisi, damga vergisi, AGİ hesabı

Http/Controllers/Admin/
  PayrollRunsController.php
  PayslipsController.php

resources/views/erp/admin/
  payroll-runs/         → index, create, show
  payslips/             → show (PDF dahil)

Tests/
  ErpPayrollLegalTest.php
```

**Yeni yıl parametresi eklemek:** `erp_payroll_parameters` tablosuna yeni satır veya `PayrollParametersSeeder`'ı güncelle.

---

## 8. Projeler Modülü
**Tür:** Business | **Bağımlılık:** HR, Satış

```
Models/
  Project.php           → erp_projects
  ProjectTask.php       → erp_project_tasks
  TimeEntry.php         → erp_time_entries

Http/Controllers/Admin/
  ProjectsController.php
  ProjectTasksController.php

Policies/
  ProjectPolicy.php     → erp.projects.*

resources/views/erp/admin/
  projects/             → index, create, edit, show (kanban dahil)

```

---

## 9. Sabit Kıymetler (Assets) Modülü
**Tür:** Business | **Bağımlılık:** Muhasebe

```
Models/
  AssetCategory.php     → erp_asset_categories (amortisman oranı burada)
  Asset.php             → erp_assets
  DepreciationEntry.php → erp_depreciation_entries

Services/Assets/
  DepreciationService.php  → runMonthlyDepreciation(), depreciateAsset()

Http/Controllers/Admin/
  AssetsController.php

Policies/
  AssetPolicy.php       → erp.assets.*

resources/views/erp/admin/
  assets/               → index, create, edit, show
```

---

## 10. Muhasebe Modülü
**Tür:** Core | **Bağımlılık:** Yok (ama tüm finansal modüller buna bağlı)

> ⚠️ Bu modül değiştirilirken dikkatli ol — Finans, Bordro, Sabit Kıymet, Satın Alma otomatik yevmiye fişi buradan üretiyor.

```
Models/
  Account.php           → erp_accounts (Tek Düzen Hesap Planı: 100-800 serisi)
  JournalEntry.php      → erp_journal_entries (YEV-YYYY-NNNNN)
  JournalLine.php       → erp_journal_lines (borç/alacak satırları, min 2 satır/fiş)

Services/Accounting/
  AccountingService.php → postSaleInvoice(), postPaymentReceived(),
                          postPurchaseInvoice(), postPayroll(),
                          postDepreciation(), trialBalance(),
                          balanceSheet(), incomeStatement()

Http/Controllers/Admin/
  AccountsController.php       → hesap planı listesi + hesap defteri
  JournalEntriesController.php → fiş listesi, manuel fiş girişi

resources/views/erp/admin/
  accounts/             → index, show (hesap defteri)
  journal-entries/      → index, create, show

Database/Seeders/
  ChartOfAccountsSeeder.php    → Türkiye Tek Düzen Hesap Planı başlangıç verileri

Tests/
  ErpAccountingModuleTest.php
```

**Yeni finansal işlem tipi eklerken:** `AccountingService`'e yeni `post*()` metodu ekle → ilgili servisten çağır → `JournalEntry::type` enum'una değer ekle.

---

## 11. Kasa & Banka Modülü
**Tür:** Business | **Bağımlılık:** Muhasebe

```
Models/
  BankAccount.php       → erp_bank_accounts (account_id → erp_accounts FK)
  BankTransaction.php   → erp_bank_transactions (morphs: source)
  Check.php             → erp_checks (çek/senet, morphs: party)

Services/Bank/
  BankService.php       → currentBalance(), transfer(), importStatement(), reconcile()

Http/Controllers/Admin/
  BankAccountsController.php
  ChecksController.php

resources/views/erp/admin/
  bank-accounts/        → index, show (hareket + transfer + mutabakat)
  checks/               → index, create
```

---

## 12. e-Fatura Modülü
**Tür:** Integration | **Bağımlılık:** Finans

```
Services/EFatura/
  EFaturaDriver.php     → interface: sendInvoice(), cancelInvoice(), checkStatus(), isRegistered()
  EFaturaResult.php     → DTO: uuid, ettn, status
  EFaturaService.php    → processInvoice(), cancelInvoice(), checkStatus() — driver'ı yönetir
  Drivers/
    NullDriver.php      → Test/devre dışı modu (API çağrısı yapmaz)
    UyumsoftDriver.php  → Uyumsoft entegratör implementasyonu

Jobs/
  SendEFaturaJob.php         → Fatura onayında kuyruğa girer
  CheckEFaturaStatusJob.php  → Pending faturaların durumunu kontrol eder (5 dk'da bir)

Tests/
  ErpEFaturaTest.php
```

**Yeni entegratör eklemek:** `Drivers/` altına yeni sınıf yaz → `EFaturaDriver` interface'ini implement et → `config/erp.php efatura.driver` değerini ekle → `EFaturaService` constructor'ına match ekle.

---

## 13. Çoklu Para Birimi Modülü
**Tür:** Support | **Bağımlılık:** Finans, Muhasebe

```
Models/
  Currency.php          → erp_currencies
  ExchangeRate.php      → erp_exchange_rates (from × to × tarih)

Services/Currency/
  CurrencyService.php   → convert(), toFunctionalCurrency(), getRate(),
                          fetchTcmbRates() (TCMB XML), saveManualRate()

Http/Controllers/Admin/
  CurrenciesController.php

Database/Seeders/
  CurrencySeeder.php    → TRY, USD, EUR, GBP başlangıç verileri

resources/views/erp/admin/
  currencies/           → index (kur listesi + manuel ekleme)

Tests/
  ErpCurrencyTest.php
```

---

## 14. Üretim (Manufacturing) Modülü
**Tür:** Business | **Bağımlılık:** Stok

```
Models/
  Bom.php               → erp_boms (Ürün Ağacı — Bill of Materials)
  BomComponent.php      → erp_bom_components (bom × hammadde × miktar)
  WorkOrder.php         → erp_work_orders (WO-YYYY-NNNNN)
  WorkOrderConsumption.php → erp_work_order_consumptions (planlı vs gerçek tüketim)

Services/Manufacturing/
  ManufacturingService.php  → generateWoNumber(), releaseWorkOrder() (hammadde rezerve),
                              completeWorkOrder() (stok hareketleri), calculateBomCost()

Http/Controllers/Admin/
  BomsController.php
  WorkOrdersController.php

resources/views/erp/admin/
  boms/                 → index, create, show
  work-orders/          → index, create, show

Tests/
  ErpManufacturingTest.php
```

---

## 15. Bildirim Modülü
**Tür:** Support | **Bağımlılık:** Tüm modüller

```
Notifications/
  LeaveRequestNotification.php    → İzin talebi oluşturuldu/onaylandı/reddedildi
  LowStockNotification.php        → Stok reorder_point altına düştü
  OverdueInvoiceNotification.php  → Vadesi geçmiş fatura

Services/Notification/
  NotificationService.php   → sendOverdueInvoiceAlerts(), sendLowStockAlerts(),
                              sendCheckDueDateAlerts()

Models/
  (erp_notification_preferences tablosu → kullanıcı başına in-app/email tercihi)
```

**Yeni bildirim tipi eklemek:** `Notifications/` altına sınıf yaz → `NotificationService`'e metod ekle → Scheduler'a ekle (`ErpServiceProvider::boot`) → `erp_notification_preferences` enum'una değer ekle.

---

## 16. Rol & Yetki Modülü
**Tür:** Core | **Bağımlılık:** Yok

> ⚠️ Bu modülde yapılan değişiklikler tüm izin kontrollerini etkiler.

```
Services/Authorization/
  ErpPermissionCatalog.php  → permissions(), roles(), permissionsForRole() — config/erp.php okur
  ErpAuthorization.php      → can() — Spatie üzerinden izin kontrolü

Policies/
  ErpPolicy.php             → abstract base: can() metodu (tüm policy'ler bunu extend eder)
  [Model]Policy.php         → Her model için ayrı policy

Http/Controllers/Admin/
  RolesController.php       → Rol CRUD + kullanıcıya rol atama

Database/Seeders/
  ErpPermissionSeeder.php   → config/erp.php'den tüm izin ve rolleri Spatie'ye seed eder

config/erp.php              → 'permissions' ve 'roles' anahtarları altında tüm tanımlar

Tests/
  ErpRolePermissionTest.php
  ErpAuthorizationTest.php
```

**Yeni izin eklemek:**
1. `config/erp.php → permissions` dizisine ekle
2. İlgili role de ekle (ya `erp_admin` için `['*']` zaten kapsar)
3. Policy'ye metod ekle
4. Controller'da `Gate::authorize('erp.xxx.yyy')` çağır
5. `make artisan CMD="db:seed --class=ErpPermissionSeeder"` ile sync et

---

## 17. API Modülü
**Tür:** Integration | **Bağımlılık:** Tüm modüller

```
Http/Controllers/Api/
  EmployeeApiController.php
  ProductApiController.php
  InvoiceApiController.php
  SalesOrderApiController.php
  PurchaseOrderApiController.php
  StockMovementApiController.php

Http/Resources/
  EmployeeResource.php      → JSON çıktı formatı
  ProductResource.php
  InvoiceResource.php
  SalesOrderResource.php
  PurchaseOrderResource.php
  StockLevelResource.php

Http/Middleware/
  AuthenticateErpApi.php    → Bearer token doğrulama (sadece token, session fallback YOK)

Models/
  ErpApiToken.php           → erp_api_tokens tablosu

Http/Controllers/Admin/
  ApiTokensController.php   → Token oluştur/sil (admin arayüzü)

routes/erp-api.php          → Tüm /api/erp/* rotaları

Tests/
  ErpApiTest.php
```

---

## 18. Veri Aktarım & Onboarding Modülü
**Tür:** Support | **Bağımlılık:** Tüm modüller

```
Http/Controllers/Admin/
  ImportController.php  → Excel/CSV import: çalışanlar, ürünler, müşteriler,
                          tedarikçiler, stok seviyeleri
  SetupController.php   → Kurulum sihirbazı (2 adım)

Models/
  ErpSetting.php        → erp_settings (setup_completed, şirket bilgileri, fatura prefix)

Services/Export/
  ExcelExportService.php    → OpenSpout tabanlı Excel üretimi

Http/Controllers/Admin/
  ExportController.php  → employees, products, invoices, salesOrders, trialBalance, payrollSummary

resources/views/erp/admin/
  import/               → index (şablon indirme + dosya yükleme kartları)
  setup/                → index (adım göstergeli sihirbaz)
```

---

## 19. Raporlama Modülü
**Tür:** Support | **Bağımlılık:** Tüm modüller

```
Http/Controllers/Admin/
  ReportsController.php → revenueReport(), inventoryReport(), hrReport(),
                          agingReport(), trialBalance(), balanceSheet(),
                          incomeStatement(), taxReport()

resources/views/erp/admin/
  reports/              → index, revenue, inventory, hr, aging,
                          trial-balance, balance-sheet, income-statement, tax-report
```

---

## Altyapı Sınıfları (Modül Dışı)

```
app/Providers/
  ErpServiceProvider.php    → Boot: morph map, middleware alias, policy kayıt,
                              Gate tanımları, view composer, route yükleme, scheduler

app/Erp/Http/Middleware/
  EnsureErpAccess.php       → Oturum kontrolü + is_active kontrolü
  AuthenticateErpApi.php    → Bearer token doğrulama

app/Erp/Support/
  ErpFormatter.php          → Para birimi, tarih formatlama (view'larda $erpFormat olarak)

app/Erp/Database/Seeders/
  ErpPermissionSeeder.php   → İzin/rol seed
  ChartOfAccountsSeeder.php → Hesap planı seed
  CurrencySeeder.php        → Para birimi seed
  PayrollParametersSeeder.php → Bordro yasal parametreleri
  PublicHolidaySeeder.php   → Türkiye resmi tatilleri

resources/views/erp/layouts/
  app.blade.php             → admin-panel::layouts.app'ı extend eder,
                              sidebar navigation @push ile enjekte edilir
```

---

## Migration Adlandırma Kuralı

```
YYYY_MM_DD_{sira}_{islem}_{tablo_adi}.php

Sıra numaraları modüllere göre:
  000xxx → Temel (settings, api_tokens)
  100xxx → HR
  130xxxx → Muhasebe
  140xxxx → Banka
  160xxxx → Bordro parametreleri
  170xxxx → İzin & Devam
  200xxx → Stok
  300xxx → Satın Alma
  400xxx → Finans
  500xxx → Satış
  600xxx → Bordro
  700xxx → Projeler
  800xxx → Sabit Kıymetler
```

---

## Yeni Modül Eklenirken Kontrol Listesi

```
[ ] database/migrations/  → tablo migration'ı (erp_ prefix)
[ ] app/Erp/Models/       → Eloquent model ($guarded, casts, ilişkiler)
[ ] database/factories/Erp/ → Factory (testler için)
[ ] app/Erp/Policies/     → Policy (ErpPolicy extend)
[ ] app/Erp/Http/Requests/ → StoreXxxRequest, UpdateXxxRequest
[ ] app/Erp/Http/Controllers/Admin/ → Web controller
[ ] resources/views/erp/admin/{module}/ → index, create, edit, show
[ ] config/erp.php        → permissions dizisine izinleri ekle
[ ] config/erp.php        → rollere izinleri ekle (erp_admin ['*'] yeterli)
[ ] app/Erp/Services/     → İş mantığı servisi
[ ] ErpServiceProvider.php → morphMap ve Gate::policy kaydı
[ ] ErpNavigation.php     → Sidebar linki
[ ] routes/erp-web.php    → Route tanımları
[ ] app/Erp/Database/Seeders/ErpPermissionSeeder → (otomatik, config okunur)
[ ] tests/Feature/Erp/    → Feature test dosyası
```
