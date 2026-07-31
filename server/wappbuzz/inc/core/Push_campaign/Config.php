<?php return [
    'platform' => 3,
    'id' => 'push_campaign',
    'folder' => 'core',
    'name' => 'Campaign',
    'author' => 'Stackcode',
    'author_uri' => 'https://stackposts.com',
    'desc' => 'Efficiently manage all your campaigns and gain deeper insights into your audiences',
    'icon' => 'fad fa-bars',
    'color' => '#49d50f',
    'parent' => 
    [
        'id' => 'features',
        'name' => 'Features',
        'position' => 900
    ],
        'show_plan' => false,
        'js' => [
        "Assets/js/push_campaign.js"
    ]
];