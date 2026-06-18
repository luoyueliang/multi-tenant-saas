<?php

return [
    'default_tenant_id' => null,
    
    'admin_domain' => env('ADMIN_DOMAIN', 'admin.lyt.com'),
    
    'platform_domains' => [
        'localhost',
        '127.0.0.1',
        'admin.example.com',
        'admin.lyt.com',
    ],
    
    'cache' => [
        'prefix' => 'tenant:',
        'ttl' => 3600,
    ],
];
