<?php return [
    'platform' => 3,
    'id' => 'push_composer',
    'folder' => 'core',
    'name' => 'Composer',
    'author' => 'Stackcode',
    'author_uri' => 'https://stackposts.com',
    'desc' => 'Create a message to prompt users to take action',
    'icon' => 'fad fa-paper-plane',
    'color' => '#ff0e0e',
    'parent' => [
        'id' => 'features',
        'name' => 'Features',
        'position' => 100
    ],
    'show_plan' => false,
    'js' => [
        "Assets/js/push_composer.js"
    ],
];