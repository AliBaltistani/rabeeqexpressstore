<?php

return [
    'panels' => [
        'admin' => [
            'id' => 'admin',
            'path' => 'admin',
            'auth_guard' => 'web',
            'brand' => 'Rabeq Express Store Admin',
            'login_route_name' => 'filament.admin.auth.login',
            'registration_enabled' => false,
            'theme' => 'light',
            'favicon' => null,
            'colors' => [
                'primary' => 'amber',
            ],
            'timezone' => 'UTC',
            'dark_mode' => true,
            'sidebar' => [
                'is_collapsible_on_desktop' => true,
                'width' => '20rem',
                'collapsed_width' => '3.5rem',
            ],
            'topbar' => [
                'is_sticky' => true,
            ],
        ],
    ],
    'database_notifications' => [
        'enabled' => true,
    ],
];
