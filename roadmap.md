# ERP Roadmap — Claude Code Uygulama Kılavuzu

> **Bu belgeyi ilk okuyan Claude Code session'ı:** Aşağıdaki "Uygulayıcı Referansı" bölümünü baştan sona oku. CRM projesinin gerçek kod yapısından çıkarılan bu detaylar olmadan fazları doğru uygulayamazsın.

---

## Uygulayıcı Referansı (Kritik — Atla Geçme)

Bu bölüm, mevcut CRM projesinin (`/Users/zyix/Desktop/repo/crm`) gerçek implementasyonundan alınan teknik kararları açıklar. ERP bunların hepsini birebir takip edecek.

### Composer Bağımlılıkları (kesin versiyon)

```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "sanalkopru/admin-panel": "^1.0",
        "spatie/laravel-permission": "^7.3",
        "dompdf/dompdf": "^3.1",
        "openspout/openspout": "^5.3",
        "openai-php/laravel": "^0.19.1"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.18",
        "phpunit/phpunit": "^11.5",
        "nunomaduro/collision": "^8.6"
    }
}
```

`admin-panel` paketi hem local path hem VCS olarak tanımlanmalı:
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/sanalkopru/admin-panel",
            "options": { "symlink": true }
        },
        {
            "type": "vcs",
            "url": "https://github.com/ZyixQQ/admin-panel"
        }
    ]
}
```

---

### Authentication Guard

Admin-panel `admin` guard kullanır. Laravel'in default `web` guard ile karışmaz.

`config/auth.php`'ye guard ve provider ekle:
```php
'guards' => [
    'admin' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],
```

**Önemli:** `admin-panel` paketi kendi login sayfasını sağlar. ERP route'larında `admin.auth` middleware'i kullanılır (paketten gelir), ERP'ye özgü `erp.access` middleware ise buna ek olarak `is_active` kontrolü yapar — tıpkı CRM'deki `EnsureCrmAccess` gibi.

---

### ServiceProvider Yapısı

**Konum:** `app/Providers/ErpServiceProvider.php` — `app/Erp/Providers/` DEĞİL.

`bootstrap/providers.php`'ye kaydet:
```php
return [
    App\Providers\ErpServiceProvider::class,
];
```

ServiceProvider'ın `boot()` metodu şunları yapmalı (CRM'deki `CrmServiceProvider` referans al):

```php
public function boot(): void
{
    // 1. Morph map (morphMany/morphTo ilişkileri için şart)
    Relation::morphMap([
        'erp_employee'    => \App\Erp\Models\Employee::class,
        'erp_invoice'     => \App\Erp\Models\Invoice::class,
        // ... diğer modeller
    ]);

    // 2. View namespace
    $this->loadViewsFrom(resource_path('views/erp'), 'erp');

    // 3. Translation namespace
    $this->loadTranslationsFrom(lang_path('erp'), 'erp');

    // 4. Middleware alias kayıt
    $this->app['router']->aliasMiddleware('erp.access', EnsureErpAccess::class);
    $this->app['router']->aliasMiddleware('erp.api.auth', AuthenticateErpApi::class);

    // 5. Policy kayıt
    Gate::policy(Employee::class, EmployeePolicy::class);
    // ...

    // 6. Gate tanımları (her izin için)
    foreach ($catalog->permissions() as $permission) {
        Gate::define(
            $permission,
            fn (?Authenticatable $user = null) => app(ErpAuthorization::class)->can($user, $permission)
        );
    }

    // 7. View composer — tüm erp::* view'larına navigation enjekte et
    View::composer('erp::*', function ($view): void {
        $view->with('erpNavigation', app(ErpNavigation::class)->items(request()));
        $view->with('erpNavigationGroups', app(ErpNavigation::class)->groups(request()));
        $view->with('erpFormat', app(ErpFormatter::class));
    });

    // 8. Routes
    Route::group([], base_path('routes/erp-web.php'));
    Route::middleware('api')->prefix('api')->group(base_path('routes/erp-api.php'));

    // 9. Scheduler
    $this->app->booted(function () {
        app(Schedule::class)
            ->call(fn () => app(InvoiceService::class)->markOverdueInvoices())
            ->dailyAt('01:00');
    });
}
```

---

### View Layout Zinciri

```
admin-panel::layouts.app          ← admin-panel paketi, sidebar/navbar sağlar
    ↑ extends
erp::layouts.app                  ← ERP kendi layout'unu yazar, sidebar nav'ı @push ile ekler
    ↑ extends
erp::admin.employees.index        ← her ERP view bunu kullanır
```

`resources/views/erp/layouts/app.blade.php`:
```blade
@extends('admin-panel::layouts.app')

@push('styles')
    @vite('resources/css/erp.css')
@endpush

@push('sidebar-nav')
    @if(!empty($erpNavigationGroups ?? []))
        <div class="sidebar-section-label">{{ __('ERP') }}</div>
        @foreach(($erpNavigationGroups ?? []) as $group)
            @php
                $visibleItems = collect($group['items'])
                    ->filter(fn ($item) => \Illuminate\Support\Facades\Gate::allows($item['permission']))
                    ->values();
            @endphp
            @if($visibleItems->isNotEmpty())
                <x-admin-panel::sidebar-dropdown
                    :label="$group['label']"
                    :icon="$group['icon']"
                    :open="$group['active']"
                    storage-key="sidebar-dropdown-erp-{{ str($group['label'])->slug() }}"
                >
                    @foreach($visibleItems as $item)
                        <x-admin-panel::sidebar-item
                            :route="$item['route']"
                            :label="$item['label']"
                            :active="$item['active']"
                        />
                    @endforeach
                </x-admin-panel::sidebar-dropdown>
            @endif
        @endforeach
    @endif
@endpush
```

Her ERP view şununla başlar:
```blade
@extends('erp::layouts.app')

@section('title', __('Employees'))
@section('page-title', __('Employees'))

@section('content')
    {{-- içerik --}}
@endsection
```

---

### Admin-Panel Blade Componentleri

Kullanılabilir tüm componentler (`<x-admin-panel::X>`):

| Component | Kullanım |
|-----------|---------|
| `table` | `:headers="$headers"` prop'u alır, `@foreach` ile satır doldur |
| `button` | `size`, `variant` (primary/ghost/outline/danger), `icon` (lucide adı), `href`, `type` |
| `bulk-actions` | `form="form-id"`, `checkbox-selector=".my-checkbox"`, `label="employees"` |
| `badge` | `variant` (primary/success/warning/danger/info/secondary) |
| `card` | Sayfa kartı, içeriği `$slot`'tan alır |
| `stat-card` | `label`, `value`, `icon`, `trend`, `trend-direction` |
| `select` | `name`, `label`, `options` (array), `selected`, `placeholder` |
| `input` | `name`, `label`, `type`, `value`, `placeholder` |
| `textarea` | `name`, `label`, `rows`, `value` |
| `modal` | `id` ile tanımla, JS ile `adminOpenModal('id')` ile aç |
| `filter-shell` | Filtre paneli wrapper'ı |
| `listing` | Liste sayfaları için wrapper (filtre + tablo birleşimi) |
| `pagination` | `$paginator` değişkenini otomatik yakalar |
| `alert` | `type` (success/warning/error/info), `dismissible` |

---

### Permission Sistemi

**Paket:** `spatie/laravel-permission` ^7.3

CRM'deki gibi **iki katmanlı** yapı:
1. `config/erp.php` içinde izin ve rol listesi tanımlanır
2. `ErpPermissionCatalog` servisi bunu okur
3. `ErpAuthorization` servisi `spatie` üzerinden kontrol yapar
4. `ErpPolicy` base class her policy'nin kullandığı `can()` metodunu sağlar

```php
// app/Erp/Policies/ErpPolicy.php (abstract)
abstract class ErpPolicy
{
    protected function can(Authenticatable $user, string $permission): bool
    {
        return app(ErpAuthorization::class)->can($user, $permission);
    }
}

// app/Erp/Policies/EmployeePolicy.php
class EmployeePolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.employees.view'); }
    public function view(Authenticatable $user, Employee $employee): bool { return $this->can($user, 'erp.employees.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.employees.create'); }
    public function update(Authenticatable $user, Employee $employee): bool { return $this->can($user, 'erp.employees.update'); }
    public function delete(Authenticatable $user, Employee $employee): bool { return $this->can($user, 'erp.employees.delete'); }
}
```

`config/erp.php`'deki izin tanımı formatı (CRM'i referans al):
```php
'permissions' => [
    'enabled' => env('ERP_PERMISSIONS_ENABLED', true),
    'guard'   => env('ERP_PERMISSIONS_GUARD', 'web'),
    'permissions' => [
        'erp.dashboard.view',
        'erp.employees.view',
        'erp.employees.create',
        // ...
    ],
    'roles' => [
        'erp_admin'   => ['name' => 'erp_admin', 'permissions' => ['*']],
        'erp_hr'      => ['name' => 'erp_hr', 'permissions' => ['erp.employees.*', 'erp.payroll.*']],
        'erp_finance' => ['name' => 'erp_finance', 'permissions' => ['erp.invoices.*', 'erp.payments.*']],
        'erp_viewer'  => ['name' => 'erp_viewer', 'permissions' => ['erp.*.view']],
    ],
],
```

**Dikkat:** `spatie/laravel-permission` guard'ı `web` olarak ayarla — admin guard değil. CRM aynı şekilde yapıyor.

---

### Model Kuralları

```php
// Her model şu yapıyı takip eder:
class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;        // softDeletes() migration'da zorunlu

    protected $guarded = ['id'];   // $fillable değil, $guarded kullanılır

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',        // Carbon cast
            'is_active' => 'boolean',
        ];
    }

    // Factory her modelde olmalı
    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }
}
```

Factory'ler `app/Erp/Database/Factories/` altında olacak.

---

### API Authentication — Kritik Kural

API middleware (`AuthenticateErpApi`) şu kuralı MUTLAKA uygular:

```php
// YANLIŞ — CRM'de güvenlik açığına yol açan pattern (tekrarlama):
$user = $this->resolveBearerUser($request) ?? $request->user('admin') ?? $request->user('web');

// DOĞRU — sadece bearer token:
$user = $this->resolveBearerUser($request);
if (! $user) {
    return response()->json(['message' => 'Unauthenticated.'], 401);
}
```

Token `last_used_at` güncellemesi 60 saniye debounce ile:
```php
if (! $token->last_used_at || $token->last_used_at->diffInSeconds(now()) > 60) {
    $token->forceFill(['last_used_at' => now()])->save();
}
```

---

### Güvenlik Kuralları (CRM'deki açıkların ERP'de tekrarlanmaması)

Bunlar CRM'de güvenlik denetiminde bulunan gerçek açıklar. ERP'yi yazarken her birini ilk seferinde doğru yap:

| Kural | Yanlış | Doğru |
|-------|--------|-------|
| **Alt-kaynak oluşturma** | Controller'da sadece parent'ın `view` izni | Parent `view` + kaynak `create` izni ikisi birden |
| **FormRequest::authorize()** | `return (bool) $this->user()` | `Gate::allows("erp.{$module}.operation")` |
| **Bulk delete bellek** | `->get()->each(fn => delete())` | `->chunkById(200, fn => ...)` + `max:500` validation |
| **Export limit** | Limitsiz `->get()` | `->limit(10000)->get()` |
| **selectRaw** | `"DATE_FORMAT({$column}, ...)"` | Whitelist kontrolü + sabit column listesi |
| **Dosya upload** | Sadece `mimes:` kuralı | `mimes:` + `getimagesize()` closure kuralı |
| **AI hataları** | `catch (Throwable) { return failure(); }` | `Log::error(...)` + failure döndür |
| **Ortak filter silme** | Sadece view izni kontrol | Sahiplik VEYA manage izni kontrol |

---

### Route Yapısı

**Web rotaları** (`routes/erp-web.php`):
```php
Route::middleware(config('erp.routes.middleware', ['web']))
    ->group(function () {
        Route::prefix(config('erp.routes.admin_prefix', 'admin/erp'))
            ->name('erp.')
            ->middleware(['erp.access', 'throttle:240,1'])   // throttle unutulmamalı
            ->group(function () {
                // AI endpoint'leri ayrı throttle grubunda:
                Route::middleware('throttle:erp-ai')->group(function () { ... });
            });
    });
```

**API rotaları** (`routes/erp-api.php`):
```php
Route::prefix('erp')
    ->name('erp.api.')
    ->group(function () {
        Route::middleware(['erp.api.auth', 'throttle:erp-api'])->group(function () {
            // endpoint'ler
        });
    });
```

---

### Test Yapısı

Test dosyaları `tests/Feature/Erp/` altında. Her modül için:

```php
class ErpEmployeeModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('erp_admin');
        $this->viewer = User::factory()->create()->assignRole('erp_viewer');
    }

    // Her test dosyasında bulunması gerekenler:
    // 1. Happy path (başarılı senaryo)
    // 2. Viewer 403 alıyor (yetki testi)
    // 3. Validasyon hatası (422)
    // 4. Bulk delete max:500 limiti
}
```

`ErpSecurityBoundaryTest` adında bir test dosyası ekle — tüm izin sınırlarını tek yerde toplar (CRM'de `CrmSecurityBoundaryTest.php` referans al).

---

### CRM Projesi Referans Dosyaları

Aşağıdaki CRM dosyalarını oku, ERP'deki karşılığını aynı pattern ile yaz:

| ERP Dosyası | CRM Referansı |
|-------------|---------------|
| `app/Providers/ErpServiceProvider.php` | `app/Providers/CrmServiceProvider.php` |
| `app/Erp/Policies/ErpPolicy.php` | `app/Crm/Policies/CrmPolicy.php` |
| `app/Erp/Services/Authorization/ErpAuthorization.php` | `app/Crm/Services/Authorization/CrmAuthorization.php` |
| `app/Erp/Services/Authorization/ErpPermissionCatalog.php` | `app/Crm/Services/Authorization/PermissionCatalog.php` |
| `app/Erp/Services/Navigation/ErpNavigation.php` | `app/Crm/Services/Navigation/CrmNavigation.php` |
| `app/Erp/Http/Middleware/EnsureErpAccess.php` | `app/Crm/Http/Middleware/EnsureCrmAccess.php` |
| `app/Erp/Http/Middleware/AuthenticateErpApi.php` | `app/Crm/Http/Middleware/AuthenticateCrmApi.php` |
| `app/Erp/Database/Seeders/ErpPermissionSeeder.php` | `app/Crm/Database/Seeders/CrmPermissionSeeder.php` |
| `resources/views/erp/layouts/app.blade.php` | `resources/views/crm/layouts/app.blade.php` |
| `config/erp.php` | `config/crm.php` |

CRM projesi `/Users/zyix/Desktop/repo/crm` altında duruyor. Referans almak için her an okuyabilirsin.

---

## Genel Bilgi

Bu belge, `sanalkopru/crm` projesinden ilham alarak sıfırdan yazılacak ERP (Enterprise Resource Planning) yazılımının adım adım uygulama planıdır. Her faz Claude Code tarafından okunup uygulanmak üzere yazılmıştır.

### Stack (CRM ile birebir aynı)
- **Framework:** Laravel 12
- **Admin UI:** `sanalkopru/admin-panel` paketi (aynı layout, component, guard yapısı)
- **Dev ortam:** Docker + Makefile (`make up`, `make artisan`, `make composer`)
- **Veritabanı:** MySQL 8
- **Cache/Queue:** Redis
- **PDF:** DomPDF (`dompdf/dompdf`)
- **Excel/CSV:** OpenSpout (`openspout/openspout`)
- **Test:** PHPUnit ^11.5

### Klasör Yapısı (CRM'deki gibi)
```
app/Erp/
├── Http/
│   ├── Controllers/Admin/   (web admin controllers)
│   ├── Controllers/Api/     (api controllers)
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/           (api resources)
├── Models/
├── Services/
├── Policies/
├── Actions/
├── Jobs/
├── Mail/
├── Observers/
└── Providers/ErpServiceProvider.php

routes/
├── erp-web.php
└── erp-api.php

resources/views/erp/admin/
config/erp.php
database/migrations/  (erp_ prefix)
tests/Feature/Erp/
```

### Modüller
1. **HR** — Çalışanlar, departmanlar, pozisyonlar
2. **Payroll** — Maaş bordrosu, kesintiler, ödemeler
3. **Inventory** — Ürünler, kategoriler, depolar, stok hareketleri
4. **Procurement** — Tedarikçiler, satın alma siparişleri
5. **Sales** — Satış siparişleri, müşteriler (CRM entegrasyonu hazır)
6. **Finance** — Faturalar, ödemeler, gider takibi
7. **Projects** — Projeler, görevler, zaman takibi
8. **Assets** — Sabit kıymetler, amortisman
9. **Reporting** — Dashboard, raporlar
10. **API** — Token korumalı REST API

---

## FAZ 1 — Proje İskeleti ve Altyapı

### 1.1 Laravel Projesi Oluştur

```bash
composer create-project laravel/laravel erp
cd erp
```

`composer.json`'a ekle (CRM'deki gibi private repo):
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ZyixQQ/admin-panel"
        }
    ]
}
```

```bash
composer require sanalkopru/admin-panel
```

### 1.2 Docker + Makefile

`docker-compose.yml` oluştur (CRM'deki ile aynı yapı):
- `app` servisi: PHP 8.3-FPM
- `nginx` servisi: port 8082 (CRM 8081 kullandığı için çakışmaması için)
- `mysql` servisi: ayrı veritabanı `erp`
- `redis` servisi

`Makefile` oluştur:
```makefile
up:
	docker compose up -d

down:
	docker compose down

artisan:
	docker compose exec app php artisan $(CMD)

composer:
	docker compose exec app composer $(CMD)

test:
	docker compose exec app php artisan test

fresh:
	docker compose exec app php artisan migrate:fresh --seed
```

### 1.3 ErpServiceProvider

`app/Erp/Providers/ErpServiceProvider.php` oluştur:

```php
<?php

namespace App\Erp\Providers;

use Illuminate\Support\ServiceProvider;

class ErpServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations'));
        $this->loadRoutesFrom(base_path('routes/erp-web.php'));
        $this->loadRoutesFrom(base_path('routes/erp-api.php'));
        $this->loadViewsFrom(resource_path('views/erp'), 'erp');
    }
}
```

`config/app.php` providers dizisine ekle:
```php
App\Erp\Providers\ErpServiceProvider::class,
```

### 1.4 config/erp.php

```php
<?php
return [
    'currency' => env('ERP_CURRENCY', 'TRY'),
    'currency_symbol' => env('ERP_CURRENCY_SYMBOL', '₺'),
    'company_name' => env('ERP_COMPANY_NAME', 'Company'),
    'ai_enabled' => env('ERP_AI_ENABLED', false),
    'ai_driver' => env('ERP_AI_DRIVER', 'null'),
    'tax_rate' => env('ERP_TAX_RATE', 20),
];
```

### 1.5 Route Dosyaları

`routes/erp-web.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/erp')
    ->name('erp.')
    ->group(function () {
        Route::get('/', fn() => redirect()->route('erp.dashboard'))->name('home');
        Route::get('/dashboard', [App\Erp\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        // Modül rotaları her fazda buraya eklenecek
    });
```

`routes/erp-api.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', App\Erp\Http\Middleware\AuthenticateErpApi::class])
    ->prefix('api/erp')
    ->name('erp.api.')
    ->group(function () {
        // API rotaları her fazda buraya eklenecek
    });
```

### 1.6 AuthenticateErpApi Middleware (CRM'dekinin kopyası)

`app/Erp/Http/Middleware/AuthenticateErpApi.php` oluştur:
- Token: `Authorization: Bearer {token}` header'ı
- Token `erp_api_tokens` tablosuna bakıyor
- `last_used_at` güncelleniyor ama 60 saniye debounce ile (CRM'deki açığı düzelt)
- Session fallback YOK (CRM #3 güvenlik açığını buraya taşıma)

### 1.7 .env.example

```env
APP_NAME=ERP
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8082

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=erp
DB_USERNAME=erp
DB_PASSWORD=secret

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis

ERP_CURRENCY=TRY
ERP_CURRENCY_SYMBOL=₺
ERP_COMPANY_NAME="My Company"
ERP_TAX_RATE=20
ERP_AI_ENABLED=false
ERP_AI_DRIVER=null
```

### 1.8 Temel Migration: Ayarlar

`database/migrations/xxxx_create_erp_settings_table.php`:
```php
Schema::create('erp_settings', function (Blueprint $table) {
    $table->id();
    $table->string('company_name')->default('');
    $table->string('company_email')->nullable();
    $table->string('company_phone')->nullable();
    $table->string('company_address')->nullable();
    $table->string('logo_path')->nullable();
    $table->string('currency', 10)->default('TRY');
    $table->string('currency_symbol', 5)->default('₺');
    $table->decimal('default_tax_rate', 5, 2)->default(20.00);
    $table->string('invoice_prefix', 20)->default('INV');
    $table->unsignedInteger('invoice_next_number')->default(1);
    $table->timestamps();
});
```

### 1.9 Dashboard Controller + View (Placeholder)

`app/Erp/Http/Controllers/Admin/DashboardController.php` — index metodu, boş bir view döndürür.

`resources/views/erp/admin/dashboard/index.blade.php` — admin-panel layout kullanarak basit bir hoş geldiniz sayfası.

**Faz 1 Tamamlanma Kriteri:** `make up && make fresh` çalışıyor, `http://localhost:8082/admin/erp/dashboard` açılıyor.

---

## FAZ 2 — HR Modülü (Çalışanlar)

### 2.1 Migrations

```php
// erp_departments
Schema::create('erp_departments', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('code', 20)->nullable();
    $table->foreignId('parent_id')->nullable()->constrained('erp_departments')->nullOnDelete();
    $table->foreignId('manager_id')->nullable()->constrained('erp_employees')->nullOnDelete();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});

// erp_positions
Schema::create('erp_positions', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->foreignId('department_id')->constrained('erp_departments');
    $table->enum('level', ['intern', 'junior', 'mid', 'senior', 'lead', 'manager', 'director', 'executive']);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// erp_employees
Schema::create('erp_employees', function (Blueprint $table) {
    $table->id();
    $table->string('employee_number')->unique();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('email')->unique();
    $table->string('phone')->nullable();
    $table->string('national_id', 11)->nullable();
    $table->date('birth_date')->nullable();
    $table->enum('gender', ['male', 'female', 'other'])->nullable();
    $table->foreignId('department_id')->nullable()->constrained('erp_departments')->nullOnDelete();
    $table->foreignId('position_id')->nullable()->constrained('erp_positions')->nullOnDelete();
    $table->foreignId('manager_id')->nullable()->constrained('erp_employees')->nullOnDelete();
    $table->date('hire_date');
    $table->date('termination_date')->nullable();
    $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern']);
    $table->enum('status', ['active', 'on_leave', 'terminated'])->default('active');
    $table->string('photo_path')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

// erp_employee_documents
Schema::create('erp_employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('erp_employees')->cascadeOnDelete();
    $table->string('name');
    $table->string('type'); // contract, id_copy, certificate, other
    $table->string('file_path');
    $table->date('expiry_date')->nullable();
    $table->timestamps();
});
```

### 2.2 Models

- `app/Erp/Models/Department.php` — `$fillable`, `parent()`, `children()`, `manager()`, `employees()` ilişkileri
- `app/Erp/Models/Position.php` — `$fillable`, `department()`, `employees()` ilişkileri  
- `app/Erp/Models/Employee.php` — `$fillable`, `department()`, `position()`, `manager()`, `subordinates()`, `documents()` ilişkileri, `getFullNameAttribute()` accessor

### 2.3 Policies

`app/Erp/Policies/EmployeePolicy.php`:
- `viewAny`, `view`, `create`, `update`, `delete` metodları
- Gate'e `erp.employees.*` şeklinde kaydet

### 2.4 Controllers

`app/Erp/Http/Controllers/Admin/EmployeesController.php`:
- `index()` — paginate(20), filtreler: department_id, status, search (ad/soyad/email/employee_number)
- `create()` / `store()` — `StoreEmployeeRequest`
- `show()` — Employee + documents + payslips (ileride)
- `edit()` / `update()` — `UpdateEmployeeRequest`
- `destroy()` — soft delete

`app/Erp/Http/Controllers/Admin/DepartmentsController.php`:
- `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`

`app/Erp/Http/Controllers/Admin/PositionsController.php`:
- `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`

### 2.5 Form Requests

`StoreEmployeeRequest` / `UpdateEmployeeRequest`:
- `first_name`, `last_name`: required|string|max:100
- `email`: required|email|unique:erp_employees,email
- `hire_date`: required|date
- `employment_type`: required|in:full_time,part_time,contract,intern
- `employee_number`: auto-generate yoksa, `EMP-` prefix + zero-padded ID

### 2.6 Views (admin-panel componentleri kullanarak)

`resources/views/erp/admin/employees/`:
- `index.blade.php` — tablo + filtreler + bulk actions
- `create.blade.php` / `edit.blade.php` — form
- `show.blade.php` — profil kartı, tab: Genel Bilgiler | Belgeler | Maaş Geçmişi

`resources/views/erp/admin/departments/`:
- `index.blade.php` — ağaç yapısı (parent/child) veya düz liste

### 2.7 Routes (erp-web.php'ye ekle)

```php
Route::resource('employees', EmployeesController::class);
Route::resource('departments', DepartmentsController::class);
Route::resource('positions', PositionsController::class);
```

### 2.8 Sidebar Link

Admin-panel navigation'a HR bölümü ekle: Employees, Departments, Positions.

**Faz 2 Tamamlanma Kriteri:** Çalışan eklenebilir, listelenebilir, düzenlenebilir, softdelete çalışıyor.

---

## FAZ 3 — Inventory Modülü (Stok)

### 3.1 Migrations

```php
// erp_product_categories
Schema::create('erp_product_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->foreignId('parent_id')->nullable()->constrained('erp_product_categories')->nullOnDelete();
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// erp_units
Schema::create('erp_units', function (Blueprint $table) {
    $table->id();
    $table->string('name');       // Adet, Kg, Litre, Metre...
    $table->string('abbreviation', 10); // pcs, kg, L, m
    $table->timestamps();
});

// erp_products
Schema::create('erp_products', function (Blueprint $table) {
    $table->id();
    $table->string('sku')->unique();
    $table->string('name');
    $table->string('barcode')->nullable()->unique();
    $table->foreignId('category_id')->nullable()->constrained('erp_product_categories')->nullOnDelete();
    $table->foreignId('unit_id')->constrained('erp_units');
    $table->text('description')->nullable();
    $table->decimal('purchase_price', 12, 2)->default(0);
    $table->decimal('sale_price', 12, 2)->default(0);
    $table->decimal('tax_rate', 5, 2)->default(20.00);
    $table->enum('type', ['product', 'service', 'consumable'])->default('product');
    $table->boolean('track_stock')->default(true);
    $table->decimal('reorder_point', 10, 3)->default(0);
    $table->string('photo_path')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});

// erp_warehouses
Schema::create('erp_warehouses', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('code', 20)->unique();
    $table->string('address')->nullable();
    $table->boolean('is_default')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// erp_stock_levels (ürün x depo)
Schema::create('erp_stock_levels', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('erp_products')->cascadeOnDelete();
    $table->foreignId('warehouse_id')->constrained('erp_warehouses')->cascadeOnDelete();
    $table->decimal('quantity', 12, 3)->default(0);
    $table->decimal('reserved_quantity', 12, 3)->default(0);
    $table->timestamps();
    $table->unique(['product_id', 'warehouse_id']);
});

// erp_stock_movements
Schema::create('erp_stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('erp_products');
    $table->foreignId('warehouse_id')->constrained('erp_warehouses');
    $table->enum('type', ['in', 'out', 'transfer', 'adjustment']);
    $table->decimal('quantity', 12, 3);
    $table->decimal('unit_cost', 12, 2)->nullable();
    $table->string('reference_type')->nullable(); // purchase_order, sales_order, adjustment
    $table->unsignedBigInteger('reference_id')->nullable();
    $table->string('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->index(['product_id', 'warehouse_id', 'created_at']);
});
```

### 3.2 Models

- `Product.php` — `category()`, `unit()`, `stockLevels()`, `movements()`, `availableQuantity($warehouseId)` metodu
- `ProductCategory.php` — `parent()`, `children()`, `products()`
- `Warehouse.php` — `stockLevels()`, `movements()`
- `StockLevel.php` — `product()`, `warehouse()`
- `StockMovement.php` — `product()`, `warehouse()`, `reference()` (morph)

### 3.3 StockService

`app/Erp/Services/Inventory/StockService.php`:
```php
// Stok hareketi kaydet ve stock_levels tablosunu güncelle
public function recordMovement(array $data): StockMovement

// Stok seviyesi düşükse reorder uyarısı oluştur
public function checkReorderPoints(): void

// Ürünün belirli depodaki kullanılabilir stok miktarı
public function availableStock(int $productId, int $warehouseId): float
```

### 3.4 Controllers

`ProductsController` — tam CRUD, stok seviyesi özeti göster
`WarehousesController` — tam CRUD
`StockMovementsController` — index (filtreli), store (manuel giriş/çıkış/düzeltme)

### 3.5 Views

`products/index.blade.php` — SKU, adı, kategori, toplam stok, satış fiyatı, durum
`products/show.blade.php` — Ürün detayı + depo bazlı stok seviyeleri + hareket geçmişi
`stock-movements/index.blade.php` — Hareketler listesi, filtre: tarih, ürün, depo, tip

**Faz 3 Tamamlanma Kriteri:** Ürün oluşturulabiliyor, stok hareketi girilebiliyor, stock_levels tablosu doğru güncelleniyor.

---

## FAZ 4 — Procurement Modülü (Satın Alma)

### 4.1 Migrations

```php
// erp_suppliers
Schema::create('erp_suppliers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('code', 20)->nullable()->unique();
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->string('tax_number')->nullable();
    $table->string('address')->nullable();
    $table->string('contact_person')->nullable();
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->decimal('credit_limit', 12, 2)->default(0);
    $table->integer('payment_terms_days')->default(30);
    $table->timestamps();
    $table->softDeletes();
});

// erp_purchase_orders
Schema::create('erp_purchase_orders', function (Blueprint $table) {
    $table->id();
    $table->string('po_number')->unique();
    $table->foreignId('supplier_id')->constrained('erp_suppliers');
    $table->foreignId('warehouse_id')->constrained('erp_warehouses');
    $table->enum('status', ['draft', 'sent', 'partial', 'received', 'cancelled'])->default('draft');
    $table->date('order_date');
    $table->date('expected_date')->nullable();
    $table->date('received_date')->nullable();
    $table->decimal('subtotal', 12, 2)->default(0);
    $table->decimal('tax_amount', 12, 2)->default(0);
    $table->decimal('total', 12, 2)->default(0);
    $table->string('currency', 3)->default('TRY');
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});

// erp_purchase_order_items
Schema::create('erp_purchase_order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_order_id')->constrained('erp_purchase_orders')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('erp_products');
    $table->decimal('quantity', 12, 3);
    $table->decimal('received_quantity', 12, 3)->default(0);
    $table->decimal('unit_price', 12, 2);
    $table->decimal('tax_rate', 5, 2)->default(20);
    $table->decimal('discount_rate', 5, 2)->default(0);
    $table->decimal('line_total', 12, 2);
    $table->timestamps();
});
```

### 4.2 PurchaseOrderService

```php
// PO numarası üret: PO-2026-00001
public function generatePoNumber(): string

// PO onaylandığında durumu 'sent' yap
public function approvePurchaseOrder(PurchaseOrder $po): void

// Mal teslimatı al: received_quantity güncelle, StockService::recordMovement çağır
public function receiveItems(PurchaseOrder $po, array $receivedItems): void

// Satırların toplamını, KDV ve genel toplamı hesapla
public function recalculateTotals(PurchaseOrder $po): void
```

### 4.3 Controllers

`SuppliersController` — tam CRUD
`PurchaseOrdersController`:
- `index()` — filtreler: status, supplier, tarih aralığı
- `create()` / `store()` — header + dynamic item rows (JS ile satır ekle/çıkar, CRM quotes'taki gibi)
- `show()` — PO detayı + kalemler + teslimat durumu
- `edit()` / `update()` — sadece draft statüsünde
- `destroy()` — sadece draft
- `receive(PurchaseOrder $po)` GET — teslimat formu
- `storeReceiving(PurchaseOrder $po)` POST — `PurchaseOrderService::receiveItems()`

### 4.4 Views

`purchase-orders/index.blade.php` — PO listesi
`purchase-orders/create.blade.php` — PO formu (dinamik kalemler, CRM quote form gibi)
`purchase-orders/show.blade.php` — Detay + PDF download
`purchase-orders/receive.blade.php` — Teslimat formu

**Faz 4 Tamamlanma Kriteri:** PO oluşturulabiliyor, onaylanabiliyor, mal teslim alındığında stok otomatik artıyor.

---

## FAZ 5 — Finance Modülü (Fatura & Ödeme)

### 5.1 Migrations

```php
// erp_invoices (satış faturası)
Schema::create('erp_invoices', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_number')->unique();
    $table->enum('type', ['sale', 'purchase', 'credit_note'])->default('sale');
    $table->morphs('invoiceable'); // customer (contact/company) veya supplier
    $table->enum('status', ['draft', 'sent', 'partial', 'paid', 'overdue', 'cancelled'])->default('draft');
    $table->date('issue_date');
    $table->date('due_date');
    $table->decimal('subtotal', 12, 2)->default(0);
    $table->decimal('discount_amount', 12, 2)->default(0);
    $table->decimal('tax_amount', 12, 2)->default(0);
    $table->decimal('total', 12, 2)->default(0);
    $table->decimal('paid_amount', 12, 2)->default(0);
    $table->string('currency', 3)->default('TRY');
    $table->string('reference')->nullable(); // satış siparişi no, PO no
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});

// erp_invoice_items
Schema::create('erp_invoice_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id')->constrained('erp_invoices')->cascadeOnDelete();
    $table->foreignId('product_id')->nullable()->constrained('erp_products')->nullOnDelete();
    $table->string('description');
    $table->decimal('quantity', 12, 3)->default(1);
    $table->decimal('unit_price', 12, 2);
    $table->decimal('tax_rate', 5, 2)->default(20);
    $table->decimal('discount_rate', 5, 2)->default(0);
    $table->decimal('line_total', 12, 2);
    $table->timestamps();
});

// erp_payments
Schema::create('erp_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id')->constrained('erp_invoices');
    $table->decimal('amount', 12, 2);
    $table->date('payment_date');
    $table->enum('method', ['cash', 'bank_transfer', 'credit_card', 'check', 'other']);
    $table->string('reference')->nullable(); // dekont no, çek no
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});

// erp_expenses
Schema::create('erp_expenses', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->enum('category', ['office', 'travel', 'utilities', 'salary', 'rent', 'marketing', 'other']);
    $table->decimal('amount', 12, 2);
    $table->date('expense_date');
    $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'other']);
    $table->string('receipt_path')->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});
```

### 5.2 InvoiceService

```php
// Fatura numarası üret: INV-2026-00001
public function generateInvoiceNumber(): string

// Ödeme kaydet, paid_amount güncelle, status hesapla (partial/paid/overdue)
public function recordPayment(Invoice $invoice, array $data): Payment

// Vadesi geçmiş faturaları overdue olarak işaretle (scheduler'dan çağrılır)
public function markOverdueInvoices(): int

// Toplam hesapla
public function recalculateTotals(Invoice $invoice): void

// PDF üret (DomPDF, CRM'deki QuotePdfService'e benzer)
public function generatePdf(Invoice $invoice): string
```

### 5.3 Controllers

`InvoicesController` — tam CRUD, ödeme kaydetme, PDF download, email gönderme
`PaymentsController` — index (tüm ödemeler), show
`ExpensesController` — tam CRUD + dosya ekleme

### 5.4 Scheduler (routes/console.php veya Kernel.php)

```php
// Her gece vadesi geçmiş faturaları kontrol et
Schedule::call(fn() => app(InvoiceService::class)->markOverdueInvoices())
    ->dailyAt('01:00')
    ->name('erp:mark-overdue-invoices');
```

### 5.5 Views

`invoices/index.blade.php` — durum badge'leri, ödeme özeti
`invoices/show.blade.php` — fatura detayı + ödeme geçmişi + ödeme ekleme modalı + PDF
`expenses/index.blade.php` — gider listesi, kategori filtresi

**Faz 5 Tamamlanma Kriteri:** Fatura oluşturulabiliyor, ödeme kaydedilebiliyor, PDF indirilebiliyor.

---

## FAZ 6 — Sales Modülü (Satış Siparişleri)

### 6.1 Migrations

```php
// erp_customers (contacts/companies'den bağımsız, ileride CRM entegrasyonu)
Schema::create('erp_customers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->string('tax_number')->nullable();
    $table->string('address')->nullable();
    $table->string('contact_person')->nullable();
    $table->integer('payment_terms_days')->default(30);
    $table->decimal('credit_limit', 12, 2)->default(0);
    $table->enum('status', ['active', 'inactive'])->default('active');
    // CRM entegrasyonu için nullable FK (opsiyonel)
    $table->unsignedBigInteger('crm_contact_id')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

// erp_sales_orders
Schema::create('erp_sales_orders', function (Blueprint $table) {
    $table->id();
    $table->string('so_number')->unique();
    $table->foreignId('customer_id')->constrained('erp_customers');
    $table->foreignId('warehouse_id')->constrained('erp_warehouses');
    $table->enum('status', ['draft', 'confirmed', 'picking', 'shipped', 'delivered', 'cancelled'])->default('draft');
    $table->date('order_date');
    $table->date('requested_delivery_date')->nullable();
    $table->date('actual_delivery_date')->nullable();
    $table->decimal('subtotal', 12, 2)->default(0);
    $table->decimal('discount_amount', 12, 2)->default(0);
    $table->decimal('tax_amount', 12, 2)->default(0);
    $table->decimal('total', 12, 2)->default(0);
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});

// erp_sales_order_items
Schema::create('erp_sales_order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sales_order_id')->constrained('erp_sales_orders')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('erp_products');
    $table->decimal('quantity', 12, 3);
    $table->decimal('unit_price', 12, 2);
    $table->decimal('tax_rate', 5, 2)->default(20);
    $table->decimal('discount_rate', 5, 2)->default(0);
    $table->decimal('line_total', 12, 2);
    $table->timestamps();
});
```

### 6.2 SalesOrderService

```php
// SO numarası: SO-2026-00001
public function generateSoNumber(): string

// SO onaylandığında stok rezervasyonu yap (reserved_quantity artır)
public function confirmOrder(SalesOrder $order): void

// SO teslim edildiğinde stok düş (StockService::recordMovement 'out')
public function deliverOrder(SalesOrder $order): void

// SO'dan otomatik fatura oluştur
public function createInvoice(SalesOrder $order): Invoice
```

### 6.3 Controllers

`CustomersController` — tam CRUD
`SalesOrdersController` — tam CRUD + onay + teslimat + fatura oluştur

**Faz 6 Tamamlanma Kriteri:** SO oluşturulabiliyor, onaylanınca stok rezerve ediliyor, teslimde stok düşüyor.

---

## FAZ 7 — Payroll Modülü (Bordro)

### 7.1 Migrations

```php
// erp_salary_structures (maaş yapıları)
Schema::create('erp_salary_structures', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // "Mavi Yaka", "Beyaz Yaka Kıdemli"
    $table->text('description')->nullable();
    $table->timestamps();
});

// erp_salary_components
Schema::create('erp_salary_components', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->enum('type', ['earning', 'deduction']); // kazanç veya kesinti
    $table->enum('calculation', ['fixed', 'percentage']); // sabit tutar veya yüzde
    $table->decimal('amount', 12, 2)->default(0);     // sabit tutar
    $table->decimal('percentage', 5, 2)->default(0);  // yüzde ise
    $table->string('base')->nullable(); // yüzde neyin üzerinden: basic_salary, gross
    $table->boolean('is_taxable')->default(true);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// erp_employee_salaries
Schema::create('erp_employee_salaries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('erp_employees')->cascadeOnDelete();
    $table->decimal('basic_salary', 12, 2);
    $table->string('currency', 3)->default('TRY');
    $table->date('effective_from');
    $table->date('effective_to')->nullable();
    $table->timestamps();
});

// erp_payroll_runs (aylık bordro çalıştırma)
Schema::create('erp_payroll_runs', function (Blueprint $table) {
    $table->id();
    $table->integer('year');
    $table->integer('month'); // 1-12
    $table->enum('status', ['draft', 'processed', 'approved', 'paid'])->default('draft');
    $table->date('pay_date')->nullable();
    $table->decimal('total_gross', 12, 2)->default(0);
    $table->decimal('total_deductions', 12, 2)->default(0);
    $table->decimal('total_net', 12, 2)->default(0);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->unique(['year', 'month']);
});

// erp_payslips (çalışan başına bordo detayı)
Schema::create('erp_payslips', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payroll_run_id')->constrained('erp_payroll_runs')->cascadeOnDelete();
    $table->foreignId('employee_id')->constrained('erp_employees');
    $table->decimal('basic_salary', 12, 2);
    $table->decimal('gross_salary', 12, 2);
    $table->decimal('total_deductions', 12, 2);
    $table->decimal('net_salary', 12, 2);
    $table->json('breakdown')->nullable(); // kazanç/kesinti detayları
    $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
    $table->timestamps();
    $table->unique(['payroll_run_id', 'employee_id']);
});
```

### 7.2 PayrollService

```php
// Belirtilen ay için tüm aktif çalışanların bordrosunu hesapla
public function processPayrollRun(int $year, int $month): PayrollRun

// Tek çalışan için bordro hesapla
public function calculatePayslip(Employee $employee, PayrollRun $run): Payslip

// Bordro onaylandığında çalışan başına ödeme kaydı oluştur
public function approveAndPay(PayrollRun $run, Carbon $payDate): void

// Bordo PDF
public function generatePayslipPdf(Payslip $payslip): string
```

### 7.3 Controllers

`PayrollRunsController` — index, create (ay/yıl seç), process (bordroyu hesapla), show (özet + çalışanlar), approve
`PayslipsController` — show (çalışan detayı), pdf download

**Faz 7 Tamamlanma Kriteri:** Aylık bordro çalıştırılabiliyor, her çalışan için PDF bordo indirilebiliyor.

---

## FAZ 8 — Projects Modülü

### 8.1 Migrations

```php
// erp_projects
Schema::create('erp_projects', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('code', 20)->unique();
    $table->text('description')->nullable();
    $table->foreignId('customer_id')->nullable()->constrained('erp_customers')->nullOnDelete();
    $table->foreignId('manager_id')->nullable()->constrained('erp_employees')->nullOnDelete();
    $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();
    $table->decimal('budget', 12, 2)->default(0);
    $table->decimal('spent', 12, 2)->default(0);
    $table->timestamps();
    $table->softDeletes();
});

// erp_project_tasks
Schema::create('erp_project_tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained('erp_projects')->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->foreignId('assignee_id')->nullable()->constrained('erp_employees')->nullOnDelete();
    $table->enum('status', ['todo', 'in_progress', 'review', 'done'])->default('todo');
    $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
    $table->date('due_date')->nullable();
    $table->date('completed_at')->nullable();
    $table->integer('estimated_hours')->default(0);
    $table->timestamps();
    $table->softDeletes();
});

// erp_time_entries
Schema::create('erp_time_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained('erp_projects');
    $table->foreignId('task_id')->nullable()->constrained('erp_project_tasks')->nullOnDelete();
    $table->foreignId('employee_id')->constrained('erp_employees');
    $table->date('date');
    $table->decimal('hours', 5, 2);
    $table->text('description')->nullable();
    $table->boolean('billable')->default(true);
    $table->timestamps();
});
```

### 8.2 Controllers

`ProjectsController` — tam CRUD, dashboard (ilerleme, bütçe, zaman)
`ProjectTasksController` — nested under project, kanban view (CRM deals kanban gibi)
`TimeEntriesController` — giriş, listeleme, proje bazlı özet

**Faz 8 Tamamlanma Kriteri:** Proje oluşturulabiliyor, task'lar kanban'da yönetilebiliyor, zaman girişi yapılabiliyor.

---

## FAZ 9 — Assets Modülü (Sabit Kıymetler)

### 9.1 Migrations

```php
// erp_asset_categories
Schema::create('erp_asset_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->integer('useful_life_years')->default(5);
    $table->decimal('depreciation_rate', 5, 2)->default(20); // yıllık %
    $table->timestamps();
});

// erp_assets
Schema::create('erp_assets', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('asset_code')->unique();
    $table->string('serial_number')->nullable();
    $table->foreignId('category_id')->constrained('erp_asset_categories');
    $table->foreignId('assigned_to')->nullable()->constrained('erp_employees')->nullOnDelete();
    $table->foreignId('location_id')->nullable()->constrained('erp_warehouses')->nullOnDelete();
    $table->date('purchase_date');
    $table->decimal('purchase_price', 12, 2);
    $table->decimal('current_value', 12, 2);
    $table->date('disposal_date')->nullable();
    $table->enum('status', ['active', 'in_repair', 'disposed'])->default('active');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

// erp_depreciation_entries
Schema::create('erp_depreciation_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('asset_id')->constrained('erp_assets')->cascadeOnDelete();
    $table->integer('year');
    $table->integer('month');
    $table->decimal('amount', 12, 2);
    $table->decimal('book_value_after', 12, 2);
    $table->timestamps();
    $table->unique(['asset_id', 'year', 'month']);
});
```

### 9.2 DepreciationService

```php
// Aylık amortisman hesapla ve kaydet (scheduler'dan çağrılır)
public function runMonthlyDepreciation(): void

// Tekil varlık için amortisman hesapla
public function depreciateAsset(Asset $asset, int $year, int $month): DepreciationEntry
```

**Faz 9 Tamamlanma Kriteri:** Varlık eklenebiliyor, aylık amortisman otomatik hesaplanıyor.

---

## FAZ 10 — Dashboard & Raporlama

### 10.1 Dashboard

`DashboardController::index()` — tek seferde tüm widget verilerini hazırla:

```php
return view('erp.admin.dashboard.index', [
    'revenue_this_month'    => InvoiceService::revenueThisMonth(),
    'revenue_last_month'    => InvoiceService::revenueLastMonth(),
    'outstanding_invoices'  => InvoiceService::outstandingTotal(),
    'overdue_invoices'      => InvoiceService::overdueTotal(),
    'expenses_this_month'   => ExpenseService::thisMonth(),
    'open_purchase_orders'  => PurchaseOrder::where('status', '!=', 'received')->count(),
    'low_stock_products'    => StockService::lowStockCount(),
    'active_employees'      => Employee::where('status', 'active')->count(),
    'active_projects'       => Project::where('status', 'active')->count(),
    'recent_invoices'       => Invoice::latest()->limit(5)->get(),
]);
```

### 10.2 Rapor Sayfaları

`ReportsController`:
- `revenueReport()` — aylık gelir/gider grafiği (Chart.js)
- `inventoryReport()` — stok değeri özeti, düşük stok listesi
- `hrReport()` — departman bazlı headcount, işe giriş/çıkış özeti
- `agingReport()` — vadesi geçmiş fatura yaşlandırma raporu (0-30, 31-60, 61-90, 90+ gün)

**Faz 10 Tamamlanma Kriteri:** Dashboard açılıyor, tüm widgetlar doğru veri gösteriyor.

---

## FAZ 11 — API Katmanı

### 11.1 API Token Yönetimi

`erp_api_tokens` tablosu oluştur (CRM'deki gibi):
```php
Schema::create('erp_api_tokens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->string('name');
    $table->string('token', 64)->unique();
    $table->json('abilities')->nullable();
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});
```

### 11.2 API Endpoints

```
GET    /api/erp/employees
GET    /api/erp/employees/{id}
GET    /api/erp/products
GET    /api/erp/products/{id}
GET    /api/erp/products/{id}/stock
POST   /api/erp/stock-movements
GET    /api/erp/invoices
GET    /api/erp/invoices/{id}
POST   /api/erp/invoices/{id}/payments
GET    /api/erp/sales-orders
POST   /api/erp/sales-orders
GET    /api/erp/purchase-orders
```

Her endpoint için `app/Erp/Http/Resources/` altında JSON Resource sınıfı oluştur.

### 11.3 API Rate Limiting

`routes/erp-api.php`'ye `throttle:60,1` middleware ekle.

**Faz 11 Tamamlanma Kriteri:** API token oluşturulabiliyor, endpoint'ler token ile erişilebiliyor.

---

## FAZ 12 — Güvenlik, Test ve Production Hazırlığı

### 12.1 Güvenlik (CRM'deki açıkların tekrarlanmaması)

Uygulanacak kurallar:
- Her controller'ın her metodunda `Gate::authorize()` çağrısı
- FormRequest::authorize() modül bazlı izin kontrolü yapıyor
- API middleware'de session fallback YOK
- Bulk işlemlerde `max:500` limiti, `chunkById(200)` kullanımı
- Export'ta 10.000 satır limiti
- Dosya yüklemelerinde GD/Intervention ile yeniden kodlama
- AI exception'ları `Log::error` ile kaydediliyor
- `token last_used_at` 60 saniye debounce ile güncelleniyor

### 12.2 Policy Yapısı

Her model için policy oluştur, `Gate::policy()` ile kaydet:
- `erp.employees.viewAny`, `erp.employees.view`, `erp.employees.create`, `erp.employees.update`, `erp.employees.delete`
- (tüm modüller için aynı pattern)

Roller: `erp_admin`, `erp_hr`, `erp_finance`, `erp_inventory`, `erp_viewer`

### 12.3 Tests

`tests/Feature/Erp/` altında oluşturulacak test dosyaları:

```
ErpAuthorizationTest.php      — rol bazlı yetki testleri
ErpHrModuleTest.php           — employee CRUD
ErpInventoryModuleTest.php    — stok hareketi + seviye kontrolü
ErpProcurementModuleTest.php  — PO + teslimat + stok artışı
ErpFinanceModuleTest.php      — fatura + ödeme + overdue logic
ErpSalesModuleTest.php        — SO + rezervasyon + teslimat
ErpApiTest.php                — token auth + endpoint testleri
```

Her test dosyası en az şunları kapsamalı:
- Happy path (başarılı senaryo)
- Yetki sınırı (yetkisiz kullanıcı 403 alıyor)
- Validasyon (gerekli alan eksik → 422)

```bash
make test
```

### 12.4 Seeder

`database/seeders/ErpSeeder.php`:
- 1 admin kullanıcısı
- 3 departman, 5 pozisyon, 10 çalışan
- 20 ürün (3 kategori, 2 depo)
- 5 tedarikçi, 3 PO (1 received)
- 10 müşteri, 5 satış siparişi
- 5 fatura (2 paid, 2 draft, 1 overdue)
- 2 proje (3'er task)

### 12.5 Production Checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] `php artisan config:cache`, `route:cache`, `view:cache`
- [ ] Queue worker: Supervisor config (`erp-worker`)
- [ ] Scheduler: cron `* * * * * php /path/to/artisan schedule:run`
- [ ] `php artisan storage:link`
- [ ] Nginx config: `try_files $uri $uri/ /index.php?$query_string`
- [ ] MySQL slow query log açık
- [ ] Redis persistence açık (`appendonly yes`)
- [ ] SSL/TLS zorunlu, HSTS header
- [ ] Log rotation: daily, 30 gün
- [ ] Backup: günlük MySQL dump + storage backup

**Faz 12 Tamamlanma Kriteri:** `make test` yeşil, production checklist tamamlandı.

---

## FAZ 13 — Muhasebe / Genel Muhasebe (Zorunlu — ERP'nin Kalbi)

### 13.1 Neden Zorunlu

Faturalar ve ödemeler tek başına muhasebe değildir. Gerçek ERP'de her finansal işlem otomatik olarak çift taraflı muhasebe kaydı (yevmiye) oluşturur. Bilanço ve gelir tablosu buradan üretilir.

### 13.2 Migrations

```php
// erp_accounts (Hesap Planı — Türkiye Tek Düzen Hesap Planı)
Schema::create('erp_accounts', function (Blueprint $table) {
    $table->id();
    $table->string('code', 20)->unique();     // 100, 120, 320, 600...
    $table->string('name');                   // Kasa, Alıcılar, Satıcılar...
    $table->enum('type', [
        'asset',        // Varlık (1xx)
        'liability',    // Yükümlülük (3xx-4xx)
        'equity',       // Özkaynak (5xx)
        'revenue',      // Gelir (6xx)
        'expense',      // Gider (7xx-8xx)
    ]);
    $table->enum('normal_balance', ['debit', 'credit']); // hangi tarafta artar
    $table->foreignId('parent_id')->nullable()->constrained('erp_accounts')->nullOnDelete();
    $table->boolean('is_active')->default(true);
    $table->boolean('allow_manual_entry')->default(true);
    $table->timestamps();
    $table->index(['code', 'type']);
});

// erp_journal_entries (Yevmiye Fişleri)
Schema::create('erp_journal_entries', function (Blueprint $table) {
    $table->id();
    $table->string('entry_number')->unique();  // YEV-2026-00001
    $table->date('entry_date');
    $table->enum('type', ['manual', 'invoice', 'payment', 'payroll', 'depreciation', 'adjustment']);
    $table->string('description');
    $table->string('reference')->nullable();   // fatura no, PO no vb.
    $table->morphs('source');                  // invoice, payment, payslip...
    $table->enum('status', ['draft', 'posted'])->default('draft');
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->index(['entry_date', 'status']);
});

// erp_journal_lines (Yevmiye Kalemleri — her fiş için en az 2 satır)
Schema::create('erp_journal_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('journal_entry_id')->constrained('erp_journal_entries')->cascadeOnDelete();
    $table->foreignId('account_id')->constrained('erp_accounts');
    $table->decimal('debit', 15, 2)->default(0);
    $table->decimal('credit', 15, 2)->default(0);
    $table->string('description')->nullable();
    $table->timestamps();
    $table->index(['account_id', 'journal_entry_id']);
});
```

### 13.3 Varsayılan Hesap Planı Seeder

Türkiye Tek Düzen Hesap Planı temel hesaplarını seed et:
- `100` Kasa, `102` Bankalar, `120` Alıcılar, `159` Verilen Sipariş Avansları
- `191` İndirilecek KDV, `391` Hesaplanan KDV
- `320` Satıcılar, `360` Ödenecek Vergiler
- `500` Sermaye, `570` Geçmiş Yıl Kârları
- `600` Yurt İçi Satışlar, `620` Satılan Mamul Maliyeti
- `700-760` Gider hesapları

### 13.4 AccountingService

```php
// Her finansal olayda otomatik yevmiye fişi oluştur
public function postJournalEntry(array $data): JournalEntry

// Fatura kesildiğinde: 120 Alıcılar (Borç) / 600 Satışlar + 391 KDV (Alacak)
public function postSaleInvoice(Invoice $invoice): JournalEntry

// Ödeme alındığında: 102 Bankalar (Borç) / 120 Alıcılar (Alacak)
public function postPaymentReceived(Payment $payment): JournalEntry

// Satın alma faturasında: 153 Ticari Mallar + 191 KDV (Borç) / 320 Satıcılar (Alacak)
public function postPurchaseInvoice(PurchaseOrder $po): JournalEntry

// Bordro: 770 Genel Yönetim Giderleri (Borç) / 335 Personele Borçlar (Alacak)
public function postPayroll(PayrollRun $run): JournalEntry

// Amortisman: 770 Giderler (Borç) / 257 Birikmiş Amortismanlar (Alacak)
public function postDepreciation(DepreciationEntry $entry): JournalEntry

// Hesap bazında bakiye hesapla (dönem veya genel)
public function accountBalance(int $accountId, ?Carbon $from, ?Carbon $to): float

// Mizan (trial balance) — tüm hesapların borç/alacak toplamları
public function trialBalance(Carbon $from, Carbon $to): Collection

// Bilanço (balance sheet)
public function balanceSheet(Carbon $date): array

// Gelir Tablosu (P&L)
public function incomeStatement(Carbon $from, Carbon $to): array
```

### 13.5 Entegrasyon Noktaları

Şu servislere otomatik yevmiye çağrısı ekle:
- `InvoiceService::recordPayment()` → `AccountingService::postPaymentReceived()`
- `InvoiceService` create/approve → `AccountingService::postSaleInvoice()`
- `PurchaseOrderService::receiveItems()` → `AccountingService::postPurchaseInvoice()`
- `PayrollService::approveAndPay()` → `AccountingService::postPayroll()`
- `DepreciationService::depreciateAsset()` → `AccountingService::postDepreciation()`

### 13.6 Controllers & Views

`JournalEntriesController`:
- `index()` — fiş listesi, filtreler: tarih, tip, hesap
- `create()` / `store()` — manuel fiş girişi (dinamik satırlar, borç=alacak kontrolü)
- `show()` — fiş detayı

`AccountsController`:
- `index()` — hesap planı (ağaç yapısı + bakiyeler)
- `show()` — hesap defteri (kart): tüm hareketler

`ReportsController` (finans raporları):
- `trialBalance()` — mizan raporu, Excel export
- `balanceSheet()` — bilanço
- `incomeStatement()` — gelir tablosu
- `taxReport()` — KDV raporu (alış/satış KDV karşılaştırması)

**Faz 13 Tamamlanma Kriteri:** Fatura kesilince otomatik yevmiye oluşuyor, mizan borç=alacak dengeleniyor, bilanço görüntülenebiliyor.

---

## FAZ 14 — Kasa & Banka Yönetimi

### 14.1 Migrations

```php
// erp_bank_accounts
Schema::create('erp_bank_accounts', function (Blueprint $table) {
    $table->id();
    $table->string('name');                    // "İş Bankası TL", "Garanti USD"
    $table->string('bank_name');
    $table->string('iban', 26)->nullable();
    $table->string('account_number')->nullable();
    $table->string('branch')->nullable();
    $table->string('currency', 3)->default('TRY');
    $table->decimal('opening_balance', 15, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('account_id')->constrained('erp_accounts'); // muhasebe hesabı (102.xx)
    $table->timestamps();
});

// erp_bank_transactions
Schema::create('erp_bank_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bank_account_id')->constrained('erp_bank_accounts');
    $table->enum('type', ['deposit', 'withdrawal', 'transfer']);
    $table->decimal('amount', 15, 2);
    $table->date('transaction_date');
    $table->string('description')->nullable();
    $table->string('reference')->nullable();
    $table->boolean('is_reconciled')->default(false);
    $table->morphs('source');                  // invoice payment, payroll...
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});

// erp_checks (Çek/Senet Takibi)
Schema::create('erp_checks', function (Blueprint $table) {
    $table->id();
    $table->enum('type', ['received', 'issued']);
    $table->string('check_number');
    $table->string('bank_name');
    $table->decimal('amount', 15, 2);
    $table->date('issue_date');
    $table->date('due_date');
    $table->enum('status', ['portfolio', 'sent_to_bank', 'cashed', 'bounced', 'cancelled'])->default('portfolio');
    $table->morphs('party');                   // customer, supplier
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### 14.2 BankService

```php
// Hesap bakiyesi (opening_balance + hareketler)
public function currentBalance(BankAccount $account): float

// İki banka hesabı arasında transfer
public function transfer(BankAccount $from, BankAccount $to, float $amount, Carbon $date): void

// Banka ekstresini import et (CSV/OFX) ve mevcut kayıtlarla eşleştir
public function importStatement(BankAccount $account, UploadedFile $file): array

// Mutabakat — işaretsiz işlemleri işaretle
public function reconcile(BankAccount $account, array $transactionIds): void
```

### 14.3 Views

`bank-accounts/index.blade.php` — hesap listesi + güncel bakiyeler
`bank-accounts/show.blade.php` — hesap defteri, transfer butonu, mutabakat
`checks/index.blade.php` — çek/senet portföyü, vade takvimi

**Faz 14 Tamamlanma Kriteri:** Banka hesabı oluşturulabiliyor, para transferi yapılabiliyor, bakiye doğru hesaplanıyor.

---

## FAZ 15 — e-Fatura & e-Arşiv (Yasal Zorunluluk — Türkiye)

### 15.1 Neden Zorunlu

Türkiye'de belirli ciro eşiğini (2025 itibarıyla 3 Milyon TL) aşan firmalar GİB'e kayıtlı entegratör üzerinden e-Fatura kesmek zorunda. Bu modül olmadan yazılım büyük müşterilere yasal olarak kullanılamaz.

### 15.2 Entegratör Seçimi

Doğrudan GİB entegrasyonu yerine akredite özel entegratör API'si kullanılacak. Önerilen: **Logo e-Fatura**, **Uyumsoft** veya **Parasut** API.

Entegratör seçimi config üzerinden yapılandırılabilir olacak:
```php
// config/erp.php
'efatura' => [
    'enabled'    => env('ERP_EFATURA_ENABLED', false),
    'driver'     => env('ERP_EFATURA_DRIVER', 'uyumsoft'), // uyumsoft | logo | parasut
    'api_url'    => env('ERP_EFATURA_API_URL'),
    'username'   => env('ERP_EFATURA_USERNAME'),
    'password'   => env('ERP_EFATURA_PASSWORD'),
    'vkn'        => env('ERP_EFATURA_VKN'),        // şirket VKN
    'test_mode'  => env('ERP_EFATURA_TEST', true),
],
```

### 15.3 EFaturaService (Driver tabanlı — CRM'deki AI gibi)

```php
interface EFaturaDriver {
    public function sendInvoice(Invoice $invoice): EFaturaResult;
    public function cancelInvoice(string $uuid): bool;
    public function checkStatus(string $uuid): string;
    public function downloadPdf(string $uuid): string;
    public function isRegistered(string $vkn): bool; // müşteri e-fatura mükellefi mi?
}

class UyumsoftDriver implements EFaturaDriver { ... }
class LogoDriver implements EFaturaDriver { ... }
class NullDriver implements EFaturaDriver { ... } // test/devre dışı
```

### 15.4 Migrations (Invoice tablosuna ek alanlar)

```php
// erp_invoices tablosuna eklenecek alanlar (migration):
$table->string('efatura_uuid')->nullable()->unique();
$table->string('efatura_ettn')->nullable();
$table->enum('efatura_status', ['none', 'pending', 'sent', 'accepted', 'rejected', 'cancelled'])->default('none');
$table->timestamp('efatura_sent_at')->nullable();
$table->enum('efatura_type', ['efatura', 'earshiv'])->nullable(); // e-Fatura mı e-Arşiv mi
$table->string('efatura_pdf_path')->nullable();
```

### 15.5 Jobs (Kuyruklanmış Gönderim)

`SendEFaturaJob` — fatura onaylandığında kuyruğa eklenir, entegratöre gönderir, durumu günceller.
`CheckEFaturaStatusJob` — `pending` durumundaki faturaların GİB yanıtını kontrol eder (her 5 dakikada).

### 15.6 Akış

1. Fatura oluşturulur → status: `draft`
2. Fatura onaylanır → müşteri VKN sorgulanır (`isRegistered()`)
   - Mükelefse: `efatura_type = efatura`, `SendEFaturaJob` kuyruğa girer
   - Değilse: `efatura_type = earshiv`, e-Arşiv olarak gönderilir
3. Job çalışır → entegratöre gönderilir → `uuid` ve `ettn` kaydedilir
4. `CheckEFaturaStatusJob` durumu takip eder → `accepted` veya `rejected` olarak günceller
5. PDF entegratörden çekilir, storage'a kaydedilir

### 15.7 UI Entegrasyonu

`invoices/show.blade.php`'ye ekle:
- e-Fatura durumu badge'i (Gönderildi / Kabul Edildi / Reddedildi)
- "e-Fatura Gönder" butonu (sadece onaylı faturalarda)
- "İptal Et" butonu (sadece kabul edilmiş faturalarda, yasal iptal süresi içinde)
- e-Fatura PDF indirme linki

**Faz 15 Tamamlanma Kriteri:** Test modunda e-Fatura gönderilebiliyor, UUID kaydediliyor, durum takip ediliyor.

---

## FAZ 16 — Bordro Tamamlama (SGK + Vergi Hesaplamaları)

### 16.1 Türkiye Spesifik Hesaplamalar

Mevcut Payroll modülü (Faz 7) temel çatıyı kurdu. Bu faz gerçek Türkiye yasal hesaplamalarını ekler.

### 16.2 Yasal Parametreler (Her Yıl Güncellenebilir)

```php
// erp_payroll_parameters (her yıl için ayrı kayıt)
Schema::create('erp_payroll_parameters', function (Blueprint $table) {
    $table->id();
    $table->integer('year');
    $table->decimal('minimum_wage', 10, 2);             // Asgari ücret (brüt)
    $table->decimal('sgk_worker_rate', 5, 4);           // İşçi SGK oranı (0.14)
    $table->decimal('sgk_employer_rate', 5, 4);         // İşveren SGK oranı (0.155)
    $table->decimal('unemployment_worker_rate', 5, 4);  // İşçi işsizlik (0.01)
    $table->decimal('unemployment_employer_rate', 5, 4);// İşveren işsizlik (0.02)
    $table->decimal('stamp_tax_rate', 6, 5);            // Damga vergisi oranı (0.00759)
    $table->json('income_tax_brackets');                // Gelir vergisi dilimleri (JSON)
    $table->decimal('agi_single', 10, 2);               // AGİ bekar
    $table->decimal('agi_married_spouse_not_working', 10, 2);
    $table->timestamps();
    $table->unique(['year']);
});
```

### 16.3 TurkishPayrollCalculator

```php
class TurkishPayrollCalculator
{
    // Brüt maaştan net maaşı hesapla
    // Döndürür: brut, sgk_isci, issizlik_isci, gelir_vergisi_matrahi,
    //           kumulatif_gelir_vergisi, damga_vergisi, net, agi, odeme
    public function calculate(
        float $grossSalary,
        int $year,
        int $month,
        float $cumulativeGrossYTD,  // yıl başından beri kümülatif brüt (vergi dilimi için)
        string $maritalStatus = 'single',
        int $dependentChildren = 0
    ): array

    // İşveren maliyetini hesapla (brüt + işveren SGK + işveren işsizlik)
    public function employerCost(float $grossSalary, int $year): float
}
```

### 16.4 Güncellenen PayrollService

`processPayrollRun()` artık `TurkishPayrollCalculator`'ı kullanıyor:
- Kümülatif yıl bazlı gelir vergisi dilimi doğru hesaplanıyor
- Her çalışan için SGK matrahı, gelir vergisi, damga vergisi ayrı gösteriliyor
- Payslip `breakdown` JSON alanı tüm hesaplamaları saklıyor

### 16.5 SGK Bildirge Export

`PayrollRunsController::exportSgkBildirgesi(PayrollRun $run)`:
- SGK e-bildirge formatına uygun CSV/XML üret
- Çalışan bazında: TCKN, prime esas kazanç, gün sayısı, belge türü

### 16.6 Payslip View Güncellemesi

`payslips/show.blade.php` ve PDF şablonu:
```
Brüt Maaş:                12.000,00 ₺
(-) SGK İşçi Payı (%14):  -1.680,00 ₺
(-) İşsizlik İşçi (%1):     -120,00 ₺
  = Gelir Vergisi Matrahı: 10.200,00 ₺
(-) Gelir Vergisi:         -1.530,00 ₺
(-) Damga Vergisi:            -91,08 ₺
(+) AGİ:                     +500,00 ₺
  = NET ÖDEME:              9.078,92 ₺

İşveren Maliyeti:         14.340,00 ₺
```

**Faz 16 Tamamlanma Kriteri:** 3 farklı maaş seviyesi için hesaplama muhasebe kitaplarıyla eşleşiyor. SGK bildirge dosyası üretiliyor.

---

## FAZ 17 — İzin & Devam Yönetimi

### 17.1 Migrations

```php
// erp_leave_types
Schema::create('erp_leave_types', function (Blueprint $table) {
    $table->id();
    $table->string('name');                    // Yıllık İzin, Hastalık, Mazeret...
    $table->integer('days_per_year')->default(0); // 0 = sınırsız/farklı hesap
    $table->boolean('requires_approval')->default(true);
    $table->boolean('is_paid')->default(true);
    $table->boolean('carry_over')->default(false); // ertesi yıla devir
    $table->integer('max_carry_over_days')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// erp_leave_balances (yıllık hak takibi)
Schema::create('erp_leave_balances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('erp_employees')->cascadeOnDelete();
    $table->foreignId('leave_type_id')->constrained('erp_leave_types');
    $table->integer('year');
    $table->decimal('entitled_days', 5, 1);   // hak kazanılan gün
    $table->decimal('used_days', 5, 1)->default(0);
    $table->decimal('carried_over_days', 5, 1)->default(0);
    $table->timestamps();
    $table->unique(['employee_id', 'leave_type_id', 'year']);
});

// erp_leave_requests
Schema::create('erp_leave_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('erp_employees');
    $table->foreignId('leave_type_id')->constrained('erp_leave_types');
    $table->date('start_date');
    $table->date('end_date');
    $table->decimal('days', 5, 1);            // iş günü (hafta sonu/tatil hariç)
    $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
    $table->text('reason')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('erp_employees')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();
});

// erp_attendance (Devam Çizelgesi)
Schema::create('erp_attendance', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('erp_employees');
    $table->date('date');
    $table->time('check_in')->nullable();
    $table->time('check_out')->nullable();
    $table->decimal('work_hours', 4, 2)->nullable();
    $table->decimal('overtime_hours', 4, 2)->default(0);
    $table->enum('status', ['present', 'absent', 'on_leave', 'holiday', 'half_day'])->default('present');
    $table->timestamps();
    $table->unique(['employee_id', 'date']);
});
```

### 17.2 LeaveService

```php
// Çalışanın belirli yıl için izin bakiyesini hesapla (kıdem dahil)
// Türk iş hukuku: <1 yıl=14gün, 1-5 yıl=14gün, 5-15 yıl=20gün, >15 yıl=26gün
public function calculateEntitlement(Employee $employee, int $year): float

// Talep onaylandığında bakiyeden düş
public function approveLeaveRequest(LeaveRequest $request, Employee $approver): void

// Tarih aralığındaki iş günü sayısını hesapla (resmi tatiller hariç)
public function calculateWorkDays(Carbon $start, Carbon $end): float

// Yeni yıl başında devir eden izinleri aktar
public function carryOverBalances(int $fromYear, int $toYear): void
```

### 17.3 Resmi Tatil Tablosu

```php
// erp_public_holidays
Schema::create('erp_public_holidays', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->date('date');
    $table->boolean('is_recurring')->default(true); // her yıl tekrar mı?
    $table->timestamps();
});
```

Seeder'a Türkiye resmi tatillerini ekle (1 Ocak, 23 Nisan, 1 Mayıs, 19 Mayıs, 15 Temmuz, 30 Ağustos, 29 Ekim + değişken bayram tatilleri).

### 17.4 Controllers & Views

`LeaveRequestsController`:
- `index()` — çalışan kendi taleplerini görür, yönetici tüm bekleyenleri görür
- `create()` / `store()` — bakiye kontrolü + çakışma kontrolü
- `approve(LeaveRequest)` / `reject(LeaveRequest)` — yönetici onay/ret

`AttendanceController`:
- `index()` — aylık devam çizelgesi (grid görünüm)
- `store()` — giriş/çıkış kaydı
- `monthlyReport(Employee)` — çalışan aylık özet

**Faz 17 Tamamlanma Kriteri:** İzin talebi oluşturulabiliyor, onay akışı çalışıyor, bakiye doğru düşüyor, devam çizelgesi doldurulabiliyor.

---

## FAZ 18 — Çoklu Para Birimi

### 18.1 Migrations

```php
// erp_currencies
Schema::create('erp_currencies', function (Blueprint $table) {
    $table->id();
    $table->string('code', 3)->unique();       // USD, EUR, GBP
    $table->string('name');
    $table->string('symbol', 5);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// erp_exchange_rates (günlük kur tablosu)
Schema::create('erp_exchange_rates', function (Blueprint $table) {
    $table->id();
    $table->string('from_currency', 3);
    $table->string('to_currency', 3);
    $table->decimal('rate', 15, 6);
    $table->date('rate_date');
    $table->enum('source', ['manual', 'tcmb', 'api'])->default('manual');
    $table->timestamps();
    $table->unique(['from_currency', 'to_currency', 'rate_date']);
    $table->index('rate_date');
});
```

### 18.2 CurrencyService

```php
// Belirli tarihte kur çevir
public function convert(float $amount, string $from, string $to, Carbon $date): float

// TCMB'den günlük kurları çek ve kaydet (scheduler'dan çağrılır)
// TCMB XML endpoint'i: https://www.tcmb.gov.tr/kurlar/today.xml
public function fetchTcmbRates(): void

// Fonksiyonel para birimi (TRY) cinsinden raporlama tutarı
public function toFunctionalCurrency(float $amount, string $currency, Carbon $date): float
```

### 18.3 Scheduler Eklemesi

```php
// Her sabah TCMB kurlarını güncelle
Schedule::call(fn() => app(CurrencyService::class)->fetchTcmbRates())
    ->weekdays()
    ->at('09:30')
    ->name('erp:fetch-tcmb-rates');
```

### 18.4 Etkilenen Modüller

- `Invoice`, `PurchaseOrder`, `SalesOrder` — `currency` alanı zaten var, `exchange_rate` alanı ekle
- Raporlar: fonksiyonel para birimine (TRY) dönüştürülmüş tutarlar + orijinal döviz tutarı yan yana
- Kur farkı: ödeme tarihi ile fatura tarihi arasındaki kur farkı otomatik muhasebe kaydı

**Faz 18 Tamamlanma Kriteri:** USD fatura kesilebiliyor, ödeme TRY üzerinden yapılabiliyor, kur farkı muhasebesi otomatik oluşuyor.

---

## FAZ 19 — Üretim Modülü (Manufacturing)

### 19.1 Migrations

```php
// erp_boms (Ürün Ağacı — Bill of Materials)
Schema::create('erp_boms', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('erp_products'); // mamul
    $table->string('version', 10)->default('1.0');
    $table->boolean('is_active')->default(true);
    $table->decimal('quantity', 10, 3)->default(1); // bu BOM kaç birim üretir
    $table->timestamps();
    $table->unique(['product_id', 'version']);
});

// erp_bom_components (BOM kalemleri)
Schema::create('erp_bom_components', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bom_id')->constrained('erp_boms')->cascadeOnDelete();
    $table->foreignId('component_id')->constrained('erp_products'); // hammadde/yarı mamul
    $table->decimal('quantity', 10, 3);
    $table->string('notes')->nullable();
    $table->timestamps();
});

// erp_work_orders (İş Emirleri)
Schema::create('erp_work_orders', function (Blueprint $table) {
    $table->id();
    $table->string('wo_number')->unique();    // WO-2026-00001
    $table->foreignId('bom_id')->constrained('erp_boms');
    $table->foreignId('product_id')->constrained('erp_products');
    $table->foreignId('warehouse_id')->constrained('erp_warehouses');
    $table->decimal('planned_quantity', 10, 3);
    $table->decimal('produced_quantity', 10, 3)->default(0);
    $table->enum('status', ['draft', 'released', 'in_progress', 'completed', 'cancelled'])->default('draft');
    $table->date('planned_start');
    $table->date('planned_end');
    $table->date('actual_start')->nullable();
    $table->date('actual_end')->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});

// erp_work_order_consumptions (Hammadde tüketimi)
Schema::create('erp_work_order_consumptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('work_order_id')->constrained('erp_work_orders')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('erp_products');
    $table->decimal('planned_quantity', 10, 3);
    $table->decimal('actual_quantity', 10, 3)->default(0);
    $table->timestamps();
});
```

### 19.2 ManufacturingService

```php
// İş emri serbest bırakıldığında: hammadde rezervasyonu
public function releaseWorkOrder(WorkOrder $wo): void

// Üretim tamamlandığında: hammadde stoktan düş, mamul stoğa ekle
public function completeWorkOrder(WorkOrder $wo, float $producedQuantity): void

// BOM'a göre maliyet hesapla (hammadde maliyeti toplamı)
public function calculateBomCost(Bom $bom): float
```

### 19.3 Controllers & Views

`BomsController` — BOM oluştur, düzenle (dinamik kalem satırları)
`WorkOrdersController` — tam CRUD + serbest bırakma + tamamlama

**Faz 19 Tamamlanma Kriteri:** BOM tanımlanabiliyor, iş emri oluşturulup tamamlandığında stok doğru güncelleniyor.

---

## FAZ 20 — Bildirim Sistemi

### 20.1 In-App Bildirimler

Laravel'in `Notification` sistemini kullan, `erp_notifications` tablosuna yaz (Laravel'in varsayılan `notifications` tablosu).

Bildirim olayları:
- İzin talebi → yöneticiye bildirim
- İzin onaylandı/reddedildi → çalışana bildirim
- Onay bekleyen satın alma siparişi → yetkili kullanıcılara
- Vadesi geçmiş fatura → finans kullanıcılarına (günlük)
- Düşük stok (reorder_point altına düşme) → stok yöneticisine
- Çek/senet vade yaklaşması (3 gün önce) → finans kullanıcılarına
- Varlık bakım tarihi yaklaşması

### 20.2 E-posta Bildirimleri

Her bildirim aynı zamanda e-posta gönderir. `Mail::` kullan.

Mail sınıfları:
- `LeaveRequestSubmittedMail`
- `LeaveRequestDecisionMail`
- `OverdueInvoiceMail`
- `LowStockAlertMail`

### 20.3 Bildirim Tercihleri

```php
// erp_notification_preferences
Schema::create('erp_notification_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('notification_type');  // overdue_invoice, low_stock...
    $table->boolean('in_app')->default(true);
    $table->boolean('email')->default(true);
    $table->timestamps();
    $table->unique(['user_id', 'notification_type']);
});
```

### 20.4 Scheduler Eklemeleri

```php
Schedule::call(fn() => app(NotificationService::class)->sendOverdueInvoiceAlerts())
    ->dailyAt('08:00')->name('erp:overdue-invoice-alerts');

Schedule::call(fn() => app(NotificationService::class)->sendLowStockAlerts())
    ->dailyAt('08:05')->name('erp:low-stock-alerts');

Schedule::call(fn() => app(NotificationService::class)->sendCheckDueDateAlerts())
    ->dailyAt('08:10')->name('erp:check-due-alerts');
```

**Faz 20 Tamamlanma Kriteri:** Vadesi geçmiş fatura için in-app ve e-posta bildirimi alınıyor.

---

## FAZ 21 — Rol & Yetki Yönetim Arayüzü

### 21.1 Neden Gerekli

Şu an roller kod içinde tanımlı. Müşteri "satış yöneticisine sadece satış siparişleri görünsün" diyemez. Bu arayüz olmadan her yetki değişikliği için geliştirici gerekiyor.

### 21.2 Migrations

```php
// erp_roles
Schema::create('erp_roles', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();          // erp_admin, erp_hr_manager
    $table->text('description')->nullable();
    $table->boolean('is_system')->default(false); // sistem rolü silinemez
    $table->timestamps();
});

// erp_permissions
Schema::create('erp_permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name');                    // "Çalışan Oluştur"
    $table->string('slug')->unique();          // erp.employees.create
    $table->string('module');                  // hr, finance, inventory...
    $table->timestamps();
});

// erp_role_permissions
Schema::create('erp_role_permissions', function (Blueprint $table) {
    $table->foreignId('role_id')->constrained('erp_roles')->cascadeOnDelete();
    $table->foreignId('permission_id')->constrained('erp_permissions')->cascadeOnDelete();
    $table->primary(['role_id', 'permission_id']);
});

// erp_user_roles
Schema::create('erp_user_roles', function (Blueprint $table) {
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('role_id')->constrained('erp_roles')->cascadeOnDelete();
    $table->primary(['user_id', 'role_id']);
});
```

### 21.3 RoleService + Gate Entegrasyonu

```php
// Tüm permissionları Gate'e kaydet (ErpServiceProvider::boot'ta)
Permission::all()->each(function ($permission) {
    Gate::define($permission->slug, function (User $user) use ($permission) {
        return $user->erp_roles()
            ->whereHas('permissions', fn($q) => $q->where('slug', $permission->slug))
            ->exists();
    });
});
```

### 21.4 Controllers & Views

`RolesController` — rol listesi, oluştur, düzenle, sil (sistem rolleri korumalı)
`roles/edit.blade.php` — modül bazlı yetki matrisi (satır=modül, sütun=viewAny/view/create/update/delete/export)
`UsersController::roles()` — kullanıcıya rol atama/kaldırma

**Faz 21 Tamamlanma Kriteri:** Admin arayüzden yeni rol oluşturulabiliyor, yetkiler atanabiliyor, kullanıcıya rol verilebiliyor.

---

## FAZ 22 — Gelişmiş Raporlama & Excel Export

### 22.1 Excel Export (Tüm Modüller)

`maatwebsite/excel` paketi ekle:
```bash
composer require maatwebsite/excel
```

Her modülde export class'ı oluştur:
- `EmployeesExport`, `ProductsExport`, `InvoicesExport`, `SalesOrdersExport`
- `TrialBalanceExport`, `IncomeStatementExport`, `BalanceSheetExport`
- `PayrollSummaryExport`, `AttendanceSummaryExport`

### 22.2 Finansal Tablolar (Yazdırılabilir)

Bilanço ve gelir tablosu için profesyonel PDF şablonu (DomPDF):
- Şirket logolu başlık
- Karşılaştırmalı (bu dönem / önceki dönem)
- Türkiye muhasebe standardı düzeni

### 22.3 KDV Raporu

`ReportsController::vatReport(int $year, int $month)`:
- İndirilecek KDV (alış faturaları)
- Hesaplanan KDV (satış faturaları)
- Ödenecek/İade edilecek KDV farkı
- KDV beyanname hazırlık listesi
- Excel export

### 22.4 Yaşlandırma Raporu (Aging)

Alacak ve borç yaşlandırması:
```
Müşteri    | Toplam  | 0-30 gün | 31-60 gün | 61-90 gün | 90+ gün
---------------------------------------------------------------------
ABC Ltd.   | 50.000  | 20.000   | 15.000    | 10.000    | 5.000
```

**Faz 22 Tamamlanma Kriteri:** Bilanço, gelir tablosu ve KDV raporu Excel/PDF olarak indirilebiliyor.

---

## FAZ 23 — Veri Aktarım & Onboarding

### 23.1 Import Şablonları (Excel)

Her modül için indirilebilir Excel şablonu + toplu import:
- Çalışanlar (employee_number, first_name, last_name, email, hire_date, department, position)
- Ürünler (sku, name, category, unit, purchase_price, sale_price, tax_rate)
- Müşteriler (name, email, phone, tax_number, address)
- Tedarikçiler (name, email, phone, tax_number, address)
- Açık faturalar (invoice_number, customer, amount, due_date)
- Başlangıç stok seviyeleri (sku, warehouse, quantity)
- Açılış muhasebe bakiyeleri (account_code, debit, credit)

Tüm import'lar:
- Satır bazlı validasyon hatası raporlaması
- Başarılı / başarısız sayısı özeti
- Hatalı satırları gösteren Excel dosyası indirme

### 23.2 Kurulum Sihirbazı (Setup Wizard)

`/admin/erp/setup` — ilk kurulumda yönlendiren çok adımlı form:
1. Şirket bilgileri (isim, VKN, adres, logo)
2. Para birimi ve vergi ayarları
3. İlk admin kullanıcısı
4. Banka hesabı (opsiyonel)
5. e-Fatura ayarları (opsiyonel)
6. Tamamlandı → dashboard'a yönlendir

Setup tamamlandı mı kontrolü: `erp_settings` tablosunda `setup_completed` boolean alanı. Tamamlanmamışsa middleware setup'a yönlendirir.

**Faz 23 Tamamlanma Kriteri:** Ürün listesi Excel ile import edilebiliyor, setup sihirbazı tamamlanabiliyor.

---

## FAZ 24 — Final QA, Güvenlik ve Satış Hazırlığı

### 24.1 Tam Test Paketi

Faz 12'deki testlere ek:
```
ErpAccountingTest.php      — yevmiye dengesi, bilanço tutarlılığı
ErpPayrollLegalTest.php    — SGK hesaplama, vergi dilimi
ErpEFaturaTest.php         — mock entegratör ile gönderim akışı
ErpLeaveTest.php           — bakiye hesaplama, iş günü sayımı
ErpManufacturingTest.php   — BOM tüketimi, stok etkisi
ErpCurrencyTest.php        — kur çevirme, kur farkı muhasebesi
ErpRolePermissionTest.php  — rol atama, yetki kontrolü
```

### 24.2 Performans

- Her listeleme ekranı için `EXPLAIN` sorgu analizi
- N+1 sorgu taraması (`barryvdh/laravel-debugbar` dev'de açık)
- Büyük tablolar için veritabanı indeksleri gözden geçir
- `erp_journal_lines` tablosu büyük olacak — `account_id + entry_date` composite index zorunlu

### 24.3 Güvenlik Son Kontrol (CRM eksiklerini tekrarlamama)

- [ ] Her controller'da `Gate::authorize()` var mı?
- [ ] Tüm FormRequest'lerde modül izni kontrol ediliyor mu?
- [ ] API'de session fallback yok
- [ ] Bulk işlemler `chunkById(200)` kullanıyor, `max:500` limiti var
- [ ] Export'larda satır sayısı sınırı var
- [ ] Dosya yüklemelerinde GD ile yeniden kodlama yapılıyor
- [ ] `selectRaw` içinde kullanıcı girdisi yok
- [ ] e-Fatura API credentials `.env`'de, kod'a hardcode değil
- [ ] TCMB API çağrısı başarısız olursa son bilinen kur kullanılıyor (fallback)

### 24.4 Demo Ortamı Hazırlığı

Demo seeder'ı genişlet — gerçekçi Türkiye şirket verisi:
- 2 yıllık geçmişe sahip 50 çalışan
- 500 ürün, 3 depo
- 200 fatura (paid/overdue karışık)
- 12 aylık bordro geçmişi
- Gerçekçi muhasebe bakiyeleri

### 24.5 Dokümantasyon

`docs/` altında:
- `kullanici-kilavuzu.md` — modül bazlı kullanım anlatımı
- `kurulum-kilavuzu.md` — sunucu kurulumu adım adım
- `efatura-kurulum.md` — entegratör bağlantısı
- `sgk-bordro-kilavuzu.md` — mevzuat özeti + yazılımda nasıl yapılır

**Faz 24 Tamamlanma Kriteri:** `make test` yeşil (tüm fazlar dahil), güvenlik kontrol listesi tamamlandı, demo ortamı çalışıyor.

---

## Geliştirme Sırası (Güncellenmiş)

| Faz | Modül | Süre | Bağımlılık |
|-----|-------|------|-----------|
| 1 | İskelet | 1 gün | — |
| 2 | HR — Çalışanlar | 2 gün | Faz 1 |
| 3 | Inventory | 2 gün | Faz 1 |
| 4 | Procurement | 2 gün | Faz 2, 3 |
| 5 | Finance — Fatura/Ödeme | 3 gün | Faz 4 |
| 6 | Sales | 2 gün | Faz 3, 5 |
| 7 | Payroll — Temel | 2 gün | Faz 2 |
| 8 | Projects | 2 gün | Faz 2, 6 |
| 9 | Assets | 1 gün | Faz 1 |
| 10 | Dashboard | 1 gün | Faz 5, 6, 7 |
| 11 | API | 1 gün | Faz 3, 5, 6 |
| 12 | Güvenlik + Test (Tur 1) | 3 gün | Faz 1-11 |
| 13 | **Muhasebe / Genel Muhasebe** | 4 gün | Faz 5 |
| 14 | **Kasa & Banka** | 2 gün | Faz 13 |
| 15 | **e-Fatura & e-Arşiv** | 3 gün | Faz 5 |
| 16 | **Bordro — SGK + Vergi** | 3 gün | Faz 7, 13 |
| 17 | **İzin & Devam** | 3 gün | Faz 2 |
| 18 | **Çoklu Para Birimi** | 2 gün | Faz 5, 13 |
| 19 | **Üretim (BOM + İş Emri)** | 3 gün | Faz 3 |
| 20 | **Bildirim Sistemi** | 2 gün | Faz 5, 6, 17 |
| 21 | **Rol & Yetki Arayüzü** | 3 gün | Faz 1 |
| 22 | **Gelişmiş Raporlama + Excel** | 3 gün | Faz 13 |
| 23 | **Veri Aktarım & Onboarding** | 3 gün | Faz 3, 5, 13 |
| 24 | **Final QA + Satış Hazırlığı** | 5 gün | Tümü |

**Toplam tahmini süre: ~57 gün**

---

## Önemli Notlar

1. **Her faz bağımsız branch'te** çalışılacak: `feature/erp-faz-2-hr` gibi
2. **Migration önce, model sonra, controller en son** — bu sıra her fazda korunacak
3. **View'lar admin-panel componentlerini** kullanacak — `<x-admin-panel::table>`, `<x-admin-panel::button>`, `<x-admin-panel::bulk-actions>` vb.
4. **Her modülün kendi Policy'si** olacak, hiçbir controller'da `if ($user->role === 'admin')` yazmak yasak — Gate kullanılacak
5. **selectRaw'da kullanıcı girdisi asla** birleştirilmeyecek
6. **Bulk işlemlerde her zaman** `chunkById(200)` kullanılacak
7. **Faz 13 (Muhasebe) tamamlanmadan** Faz 15, 16, 22 başlatılmamalı — hepsi yevmiye sistemine bağlı
8. **e-Fatura (Faz 15) test modunda** başlanmalı, canlıya geçmeden önce GİB test ortamında doğrulama yapılmalı
9. **SGK hesaplamaları (Faz 16)** her yıl güncellenen parametrelere bağlı — `erp_payroll_parameters` tablosu yıl başında güncellenmeli
7. **Test önce yaz prensibine** geçilecek — en azından her controller için bir Feature test
