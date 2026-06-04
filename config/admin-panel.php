<?php

return [
    'prefix' => 'admin',
    'middleware' => ['web', 'admin.auth'],
    'logo' => null,
    'app_name' => 'ERP',

    'theme' => [
        'primary' => '#3b82f6',
        'sidebar_bg' => '#0f172a',
    ],

    'pagination' => 20,
    'guard' => 'admin',
    'login_route' => 'admin.login',
    'asset_path' => 'vendor/admin-panel',

    'roles' => [
        'superadmin' => ['Superadmin', 'primary'],
    ],
    'default_role' => ['Staff', 'secondary'],
];
