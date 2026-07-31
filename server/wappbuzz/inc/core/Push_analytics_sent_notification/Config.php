<?php return [
    'platform' => 3,
    'id' => 'push_analytics_sent_notification',
    'folder' => 'core',
    'name' => 'Sent notification',
    'author' => 'Stackcode',
    'author_uri' => 'https://stackposts.com',
    'desc' => 'Understand how effectively your notifications reach and engage your audience',
    'icon' => 'fad fa-tasks',
    'color' => '#5c3bd0',
    'parent' => [
        'id' => 'analytics',
        'name' => 'Analytics',
        'position' => 100
    ],
    'show_plan' => false,
    'js' => [
        "Assets/js/push_analytics_sent_notification.js",
        "Assets/plugins/bootstrap-table/bootstrap-table.min.js"
    ],
        'css' => [
        "Assets/plugins/bootstrap-table/bootstrap-table.min.css"
    ],
];