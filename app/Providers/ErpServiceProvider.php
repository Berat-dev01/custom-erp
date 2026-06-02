<?php

namespace App\Providers;

use App\Erp\Http\Middleware\AuthenticateErpApi;
use App\Erp\Http\Middleware\EnsureErpAccess;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\Position;
use App\Erp\Policies\DepartmentPolicy;
use App\Erp\Policies\EmployeePolicy;
use App\Erp\Policies\PositionPolicy;
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
    }

    public function boot(): void
    {
        Relation::morphMap([
            'erp_api_token' => \App\Erp\Models\ErpApiToken::class,
            'erp_employee'  => Employee::class,
            'erp_department'=> Department::class,
            'erp_position'  => Position::class,
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
            // Scheduler görevleri ilerleyen fazlarda eklenecek
        });
    }
}
