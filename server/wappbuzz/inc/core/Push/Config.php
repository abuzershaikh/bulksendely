<?php return [
    'platform' => 3,
    'id' => 'push',
    'folder' => 'core',
    'name' => 'Web Push Notification',
    'author' => 'Stackcode',
    'author_uri' => 'https://stackposts.com',
    'desc' => 'Customize system interface',
    'icon' => 'fad fa-bell-on',
    'color' => '#0040ff',
    'show_plan' => false,
    'disabled' => true, // Push notifications system disabled
    'menu' => [
        // Menu disabled - push notifications system is disabled
        // 'custom' => 'Core\Push\Controllers\Push::sidebar',
        // 'tab' => 1,
        // 'type' => 'top',
        // 'position' => 100000,
        // 'name' => 'Web Push Notification',
    ],
    'parent' => [
        'id' => '',
        'name' => '',
        'position' => 200
    ],
    'js' =>
    [
        // JS disabled - push notifications system is disabled
        // 0 => 'Assets/js/push.js',
    ],
    'cron' => [
        // Cron disabled - push notifications system is disabled
        // 'name' => 'Web Push Composer',
        // 'uri' => 'push/cron',
        // 'style' => '* * * * *',
    ]
];