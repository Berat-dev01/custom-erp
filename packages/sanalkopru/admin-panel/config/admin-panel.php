<?php

return [
    'prefix' => 'admin',
    'middleware' => ['web', 'admin.auth'],
    'logo' => null,
    'app_name' => 'Admin Panel',

    'theme' => [
        'primary' => '#3b82f6',
        'sidebar_bg' => '#0f172a',
    ],

    'pagination' => 20,
    'guard' => 'admin',
    'login_route' => 'admin.login',
    'asset_path' => 'vendor/admin-panel',

    /*
     * Per-project role badge mapping: 'role_name' => ['Label', 'badge-variant']
     * Publish this config and customize per project — do not edit the package default.
     * Example: 'crm_owner' => ['Owner', 'primary']
     */
    'roles' => [],
    'default_role' => ['Staff', 'secondary'],
];
