<?php

namespace App\Providers;

use App\Erp\Http\Middleware\AuthenticateErpApi;
use App\Erp\Http\Middleware\EnsureErpAccess;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\Position;
use App\Erp\Models\Product;
use App\Erp\Models\Customer;
use App\Erp\Models\Expense;
use App\Erp\Models\Invoice;
use App\Erp\Models\PurchaseOrder;
use App\Erp\Models\Asset;
use App\Erp\Models\Project;
use App\Erp\Models\SalesOrder;
use App\Erp\Models\Supplier;
use App\Erp\Models\Warehouse;
use App\Erp\Policies\AssetPolicy;
use App\Erp\Policies\CustomerPolicy;
use App\Erp\Policies\DepartmentPolicy;
use App\Erp\Policies\EmployeePolicy;
use App\Erp\Policies\ExpensePolicy;
use App\Erp\Policies\InvoicePolicy;
use App\Erp\Policies\PositionPolicy;
use App\Erp\Policies\ProductPolicy;
use App\Erp\Policies\ProjectPolicy;
use App\Erp\Policies\PurchaseOrderPolicy;
use App\Erp\Policies\SalesOrderPolicy;
use App\Erp\Policies\SupplierPolicy;
use App\Erp\Policies\WarehousePolicy;
use App\Erp\Services\Assets\DepreciationService;
use App\Erp\Services\Finance\InvoiceService;
use App\Erp\Services\Inventory\StockService;
use App\Erp\Services\Payroll\PayrollService;
use App\Erp\Services\Procurement\PurchaseOrderService;
use App\Erp\Services\Sales\SalesOrderService;
use App\Erp\Services\Authorization\ErpAuthorization;
use App\Erp\Services\Authorization\ErpPermissionCatalog;
use App\Erp\Services\Navigation\ErpNavigation;
use App\Erp\Support\ErpFormatter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ErpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ErpPermissionCatalog::class);
        $this->app->singleton(ErpAuthorization::class);
        $this->app->singleton(ErpNavigation::class);
        $this->app->singleton(ErpFormatter::class);
        $this->app->singleton(StockService::class);
        $this->app->singleton(PurchaseOrderService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(SalesOrderService::class);
        $this->app->singleton(PayrollService::class);
        $this->app->singleton(DepreciationService::class);
        $this->app->singleton(\App\Erp\Services\Accounting\AccountingService::class);
        $this->app->singleton(\App\Erp\Services\Payroll\TurkishPayrollCalculator::class);
        $this->app->singleton(\App\Erp\Services\EFatura\EFaturaService::class);
    }

    public function boot(): void
    {
        Relation::morphMap([
            'erp_api_token'  => \App\Erp\Models\ErpApiToken::class,
            'erp_employee'   => Employee::class,
            'erp_department' => Department::class,
            'erp_position'   => Position::class,
            'erp_product'        => Product::class,
            'erp_warehouse'      => Warehouse::class,
            'erp_supplier'       => Supplier::class,
            'erp_purchase_order' => PurchaseOrder::class,
            'erp_invoice'        => Invoice::class,
            'erp_expense'        => Expense::class,
            'erp_customer'       => Customer::class,
            'erp_sales_order'    => SalesOrder::class,
            'erp_project'        => Project::class,
            'erp_asset'              => Asset::class,
            'erp_account'            => \App\Erp\Models\Account::class,
            'erp_journal_entry'      => \App\Erp\Models\JournalEntry::class,
            'erp_depreciation_entry' => \App\Erp\Models\DepreciationEntry::class,
            'erp_payroll_run'        => \App\Erp\Models\PayrollRun::class,
            'erp_payment'            => \App\Erp\Models\Payment::class,
        ]);

        $this->loadViewsFrom(resource_path('views/erp'), 'erp');
        $this->loadTranslationsFrom(lang_path('erp'), 'erp');

        $this->registerBlade();
        $this->registerMiddleware();
        $this->registerAuthorization();
        $this->registerRateLimiters();
        $this->loadWebRoutes();
        $this->loadApiRoutes();
        $this->scheduleCommands();
    }

    private function registerBlade(): void
    {
        Blade::if('feature', fn (string $feature): bool => (bool) data_get(config('features', []), $feature, false));
    }

    private function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('erp.access', EnsureErpAccess::class);
        $this->app['router']->aliasMiddleware('erp.api.auth', AuthenticateErpApi::class);
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Position::class, PositionPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Asset::class, AssetPolicy::class);

        $catalog = $this->app->make(ErpPermissionCatalog::class);

        foreach ($catalog->permissions() as $permission) {
            Gate::define(
                $permission,
                fn (?Authenticatable $user = null): bool => $this->app->make(ErpAuthorization::class)->can($user, $permission)
            );
        }

        View::composer('erp::*', function ($view): void {
            $navigation = $this->app->make(ErpNavigation::class);

            $view->with('erpNavigationGroups', $navigation->groups(request()));
            $view->with('erpNavigation', $navigation->items(request()));
            $view->with('erpFormat', $this->app->make(ErpFormatter::class));
        });
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('erp-api', function (Request $request): Limit {
            $limit = (int) config('erp.api.rate_limit_per_minute', 60);
            $key   = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute($limit)->by('erp-api:'.$key);
        });

        RateLimiter::for('erp-ai', function (Request $request): Limit {
            $limit = (int) config('erp.ai.rate_limit_per_minute', 10);
            $key   = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute($limit)->by('erp-ai:'.$key);
        });
    }

    private function loadWebRoutes(): void
    {
        Route::group([], base_path('routes/erp-web.php'));
    }

    private function loadApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/erp-api.php'));
    }

    private function scheduleCommands(): void
    {
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            $schedule->call(fn () => $this->app->make(InvoiceService::class)->markOverdueInvoices())
                ->dailyAt('01:00')
                ->name('erp:mark-overdue-invoices')
                ->withoutOverlapping();

            $schedule->call(fn () => $this->app->make(DepreciationService::class)->runMonthlyDepreciation())
                ->monthlyOn(1, '02:00')
                ->name('erp:run-monthly-depreciation')
                ->withoutOverlapping();

            $schedule->job(new \App\Erp\Jobs\CheckEFaturaStatusJob())
                ->everyFiveMinutes()
                ->name('erp:check-efatura-status')
                ->withoutOverlapping()
                ->when(fn () => config('erp.efatura.enabled', false));
        });
    }
}
