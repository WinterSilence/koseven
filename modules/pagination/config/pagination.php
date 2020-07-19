<?php

return [
    
    // Application defaults
    'default' => [
        'current_page' => ['source' => 'query_string', 'key' => 'page'], // source: "query_string" or "route"
        'total_items' => 0,
        'items_per_page' => 10,
        'view' => 'pagination/basic',
        'auto_hide' => true,
        'first_page_in_url' => false,
    ],

];
