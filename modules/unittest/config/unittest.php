<?php

return [

    // If you don't use a whitelist then only files included during the request will be counted
    // If you do, then only whitelisted items will be counted
    'use_whitelist' => true,

    // Items to whitelist, only used in cli
    'whitelist' => [

        // Should the app be whitelisted?
        // Useful if you just want to test your application
        'app' => true,

        // Set to array(TRUE) to include all modules, or use an array of module names
        // (the keys of the array passed to KO7::modules() in the bootstrap)
        // Or set to FALSE to exclude all modules
        'modules' => [true],

        // If you don't want the KO7 code coverage reports to pollute your app's,
        // then set this to FALSE
        'system' => true,
    ],
];
