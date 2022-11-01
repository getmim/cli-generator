<?php

return [
    '__name' => 'cli-generator',
    '__version' => '0.1.0',
    '__git' => 'git@github.com:getmim/cli-generator.git',
    '__license' => 'MIT',
    '__author' => [
        'name' => 'Rian',
        'email' => 'godamri@gmail.com',
        'website' => '-'
    ],
    '__files' => [
        'modules/cli-generator' => ['install','update','remove']
    ],
    '__dependencies' => [
        'required' => [
            [
                'cli' => NULL
            ],
            // [
            //     'cli-module-generator' => NULL
            // ]
        ],
        'optional' => []
    ],
    'autoload' => [
        'classes' => [
            'CliGenerator\\Controller' => [
                'type' => 'file',
                'base' => 'modules/cli-generator/controller'
            ],
            'CliGenerator\\Library' => [
                'type' => 'file',
                'base' => 'modules/cli-generator/library'
            ]
        ],
        'files' => []
    ],
    'routes' => [
        'tool' => [
            'toolCliGenerator' => [
                'info' => 'CLI Generator',
                'path' => [
                    'value' => 'generator (:command)',
                    'params' => [
                        'command' => 'slug'
                    ]
                ],
                'handler' => 'CliGenerator\\Controller\\Generator::generator'
            ]
        ],
        'cli' => []
    ],
    'cli' => [
        'autocomplete' => [
            '!^generator( [a-z]*)?$!' => [
                'priority' => 3,
                'handler' => [
                    'class' => 'CliGenerator\\Library\\Autocomplete',
                    'method' => 'command'
                ]
            ]
        ]
    ]
];