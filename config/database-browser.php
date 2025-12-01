<?php

return [
    'connections' => [
        'read' => env('DB_BROWSER_READ_CONNECTION', 'pgsql'),
        'write' => env('DB_BROWSER_WRITE_CONNECTION', 'pgsql'),
    ],

    'tables' => [
        'allowed' => array_filter(explode(',', (string) env('DB_BROWSER_ALLOWED_TABLES'))),
        'disallowed' => array_filter(explode(',', (string) env('DB_BROWSER_DISALLOWED_TABLES'))),
        'limit' => (int) env('DB_BROWSER_TABLE_LIMIT', 100),
    ],

    'writes' => [
        'enabled' => filter_var(env('DB_BROWSER_ENABLE_WRITES', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'authorization' => [
        'gate' => env('DB_BROWSER_ACCESS_GATE', 'viewDatabaseBrowser'),
        'policy' => env('DB_BROWSER_ACCESS_POLICY'),
    ],

    'navigation' => [
        'label' => env('DB_BROWSER_NAVIGATION_LABEL', 'Database Browser'),
        'title' => env('DB_BROWSER_PAGE_TITLE', 'Database Browser'),
        'group' => env('DB_BROWSER_NAVIGATION_GROUP', 'Database'),
        'icon' => env('DB_BROWSER_NAVIGATION_ICON', 'heroicon-o-circle-stack'),
        'sort' => env('DB_BROWSER_NAVIGATION_SORT', 9000),
    ],

    'page' => [
        'slug' => env('DB_BROWSER_PAGE_SLUG', 'database-browser'),
    ],

    'preview' => [
        'rows' => (int) env('DB_BROWSER_PREVIEW_LIMIT', 100),
        'column_threshold' => (int) env('DB_BROWSER_COLUMN_THRESHOLD', 70),
    ],

    'livewire' => [
        'namespace' => env('DB_BROWSER_LIVEWIRE_NAMESPACE', 'database-browser'),
    ],

    'columns' => [
        'immutable' => [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
        'sensitive_fragments' => [
            'password',
            'secret',
            'token',
            'recovery_code',
            'two_factor',
            'otp',
        ],
    ],
];
