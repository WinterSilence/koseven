<?php

return [
    // Leave this alone
    'modules' => [

        // This should be the path to this modules userguide pages, without the 'guide/'. Ex: '/guide/modulename/' would be 'modulename'
        'kohana' => [

            // Whether this modules userguide pages should be shown
            'enabled' => true,

            // The name that should show up on the userguide index page
            'name' => 'Kohana Compatibility Module',

            // A short description of this module, shown on the index page
            'description' => 'Module to maintain backwards compatibility with old Kohana class names',

            // Copyright message, shown in the footer for this module
            'copyright' => '&copy; 2016-2019 Koseven Team - https://github.com/orgs/koseven/people',
        ],
    ],
];
