<?php
return [
    'platform' => 3,
    'id' => 'push_schedules',
    'folder' => 'core',
    'name' => 'Schedules',
    'author' => 'Stackcode',
    'author_uri' => 'https://stackposts.com',
    'desc' => 'Customize system interface',
    'icon' => 'fad fa-calendar-alt',
    'color' => '#c300e7',
    'parent' => [
        'id' => 'features',
        'name' => 'Features',
        'position' => 1000
    ],
    'css' => [
        'Assets/css/schedules.css'
    ],
    'js' => [
        'Assets/js/schedules.js',
    ]
];