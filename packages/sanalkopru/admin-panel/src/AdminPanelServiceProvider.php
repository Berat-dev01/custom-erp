<?php

namespace Sanalkopru\AdminPanel;

use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Sanalkopru\AdminPanel\Http\Middleware\AdminAuthenticate;

class AdminPanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admin-panel.php', 'admin-panel');

        $this->app->singleton('admin-panel', fn () => new AdminPanel);
    }

    public function boot(Router $router): void
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'admin-panel');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'admin-panel');
        Paginator::defaultView('admin-panel::pagination.links');
        Paginator::defaultSimpleView('admin-panel::pagination.simple-default');

        if (method_exists($this, 'loadAnonymousComponentsFrom')) {
            $this->loadAnonymousComponentsFrom(__DIR__.'/../resources/views/components', 'admin-panel');
        }

        $router->aliasMiddleware('admin.auth', AdminAuthenticate::class);

        $this->publishes([
            __DIR__.'/../config/admin-panel.php' => config_path('admin-panel.php'),
        ], 'admin-panel-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/admin-panel'),
        ], 'admin-panel-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/admin-panel'),
        ], 'admin-panel-lang');

        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/admin-panel/css'),
            __DIR__.'/../resources/js' => public_path('vendor/admin-panel/js'),
        ], 'admin-panel-assets');
    }
}
