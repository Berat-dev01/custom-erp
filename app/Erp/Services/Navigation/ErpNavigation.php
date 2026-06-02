<?php

namespace App\Erp\Services\Navigation;

use Illuminate\Http\Request;

class ErpNavigation
{
    /**
     * @return list<array{label: string, route: string, permission: string, active: bool}>
     */
    public function items(Request $request): array
    {
        return collect($this->definitions())
            ->map(fn (array $item): array => [
                ...$item,
                'active' => $request->routeIs($item['route']) || $request->routeIs($item['route'].'.*'),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, icon: string, active: bool, items: list<array{label: string, route: string, permission: string, active: bool}>}>
     */
    public function groups(Request $request): array
    {
        return collect($this->groupDefinitions())
            ->map(function (array $group) use ($request): array {
                $items = collect($group['items'])
                    ->map(fn (array $item): array => [
                        ...$item,
                        'active' => $request->routeIs($item['route']) || $request->routeIs($item['route'].'.*'),
                    ])
                    ->all();

                return [
                    ...$group,
                    'items'  => $items,
                    'active' => collect($items)->contains(fn (array $item): bool => $item['active']),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{label: string, route: string, permission: string}>
     */
    private function definitions(): array
    {
        return collect($this->groupDefinitions())
            ->flatMap(fn (array $group): array => $group['items'])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, icon: string, items: list<array{label: string, route: string, permission: string}>}>
     */
    private function groupDefinitions(): array
    {
        return [
            [
                'label' => __('Genel Bakış'),
                'icon'  => 'layout-dashboard',
                'items' => [
                    ['label' => __('Dashboard'), 'route' => 'erp.dashboard', 'permission' => 'erp.dashboard.view'],
                ],
            ],
            [
                'label' => __('İK'),
                'icon'  => 'users',
                'items' => [
                    ['label' => __('Çalışanlar'),   'route' => 'erp.employees.index',  'permission' => 'erp.employees.view'],
                    ['label' => __('Departmanlar'), 'route' => 'erp.departments.index', 'permission' => 'erp.departments.view'],
                    ['label' => __('Pozisyonlar'),  'route' => 'erp.positions.index',   'permission' => 'erp.positions.view'],
                ],
            ],
            [
                'label' => __('Stok'),
                'icon'  => 'package',
                'items' => [
                    ['label' => __('Ürünler'),        'route' => 'erp.products.index',        'permission' => 'erp.products.view'],
                    ['label' => __('Depolar'),         'route' => 'erp.warehouses.index',      'permission' => 'erp.warehouses.view'],
                    ['label' => __('Stok Hareketleri'),'route' => 'erp.stock-movements.index', 'permission' => 'erp.stock_movements.view'],
                ],
            ],
            [
                'label' => __('Satın Alma'),
                'icon'  => 'shopping-cart',
                'items' => [
                    ['label' => __('Tedarikçiler'),       'route' => 'erp.suppliers.index',       'permission' => 'erp.suppliers.view'],
                    ['label' => __('Satın Alma Siparişleri'),'route' => 'erp.purchase-orders.index','permission' => 'erp.purchase_orders.view'],
                ],
            ],
            [
                'label' => __('Satış'),
                'icon'  => 'badge-dollar-sign',
                'items' => [
                    ['label' => __('Müşteriler'),       'route' => 'erp.customers.index',     'permission' => 'erp.customers.view'],
                    ['label' => __('Satış Siparişleri'),'route' => 'erp.sales-orders.index',  'permission' => 'erp.sales_orders.view'],
                ],
            ],
            [
                'label' => __('Finans'),
                'icon'  => 'wallet',
                'items' => [
                    ['label' => __('Faturalar'), 'route' => 'erp.invoices.index',  'permission' => 'erp.invoices.view'],
                    ['label' => __('Ödemeler'),  'route' => 'erp.payments.index',  'permission' => 'erp.payments.view'],
                    ['label' => __('Giderler'),  'route' => 'erp.expenses.index',  'permission' => 'erp.expenses.view'],
                ],
            ],
            [
                'label' => __('Bordro'),
                'icon'  => 'banknote',
                'items' => [
                    ['label' => __('Bordro Çalıştırma'), 'route' => 'erp.payroll-runs.index', 'permission' => 'erp.payroll.view'],
                ],
            ],
            [
                'label' => __('Projeler'),
                'icon'  => 'clipboard-list',
                'items' => [
                    ['label' => __('Projeler'), 'route' => 'erp.projects.index', 'permission' => 'erp.projects.view'],
                ],
            ],
            [
                'label' => __('Sabit Kıymetler'),
                'icon'  => 'building-2',
                'items' => [
                    ['label' => __('Varlıklar'), 'route' => 'erp.assets.index', 'permission' => 'erp.assets.view'],
                ],
            ],
            [
                'label' => __('Kasa & Banka'),
                'icon'  => 'landmark',
                'items' => [
                    ['label' => __('Banka Hesapları'), 'route' => 'erp.bank-accounts.index', 'permission' => 'erp.bank.view'],
                    ['label' => __('Çek/Senet'),       'route' => 'erp.checks.index',        'permission' => 'erp.bank.view'],
                ],
            ],
            [
                'label' => __('Muhasebe'),
                'icon'  => 'book-open',
                'items' => [
                    ['label' => __('Hesap Planı'),     'route' => 'erp.accounts.index',        'permission' => 'erp.accounts.view'],
                    ['label' => __('Yevmiye Fişleri'), 'route' => 'erp.journal-entries.index', 'permission' => 'erp.journal_entries.view'],
                    ['label' => __('Mizan'),           'route' => 'erp.reports.trial-balance', 'permission' => 'erp.reports.view'],
                    ['label' => __('Bilanço'),         'route' => 'erp.reports.balance-sheet', 'permission' => 'erp.reports.view'],
                    ['label' => __('Gelir Tablosu'),   'route' => 'erp.reports.income-statement','permission' => 'erp.reports.view'],
                    ['label' => __('KDV Raporu'),      'route' => 'erp.reports.tax-report',    'permission' => 'erp.reports.view'],
                ],
            ],
            [
                'label' => __('Raporlar'),
                'icon'  => 'bar-chart-2',
                'items' => [
                    ['label' => __('Gelir / Gider'),   'route' => 'erp.reports.revenue',   'permission' => 'erp.reports.view'],
                    ['label' => __('Stok Değeri'),      'route' => 'erp.reports.inventory', 'permission' => 'erp.reports.view'],
                    ['label' => __('İK Özeti'),         'route' => 'erp.reports.hr',        'permission' => 'erp.reports.view'],
                    ['label' => __('Yaşlandırma'),      'route' => 'erp.reports.aging',     'permission' => 'erp.reports.view'],
                ],
            ],
            [
                'label' => __('Sistem'),
                'icon'  => 'settings',
                'items' => [
                    ['label' => __('API Tokenleri'), 'route' => 'erp.api-tokens.index', 'permission' => 'erp.api.manage'],
                ],
            ],
        ];
    }
}
