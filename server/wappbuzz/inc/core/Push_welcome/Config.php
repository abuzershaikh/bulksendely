<?php return  [
    'platform' => 3,
    'id' => 'push_welcome',
    'folder' => 'core',
    'name' => 'Welcome Notification',
    'author' => 'Stackcode',
    'author_uri' => 'https://stackposts.com',
    'desc' => 'Send to new subscribers with a sequence of notifications',
    'icon' => 'fad fa-door-open',
    'color' => '#09448d',
    'parent' => [
      'id' => 'features',
      'name' => 'Features',
      'position' => 200
    ],
    'show_plan' => false,
    'cron' => [
        'name' => 'Web Push Notification - Welcome Notification',
        'uri' => 'push_welcome/cron',
        'style' => '* * * * *',
    ]
]; 