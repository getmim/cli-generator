<?php

/**
 * Controller helper
 * @package cli-generator
 * @version 1.0.0
 */

namespace CliGenerator\Library;

use Cli\Library\Bash;
use Mim\Library\Fs;
use CliModule\Library\BController;

class Generator
{
    static $config = [
        'config' => [
            'path' => false,
            'name' => 'cart-module',
            'git' => '',
            'license' => 'MIT',
            'files' => [
                'install',
                'update',
                'remove'
            ],
            'dependencies' => [
                'required' => [
                    [
                        'module-name' => NULL
                    ]
                ],
                'optional' => [
                    [
                        'optional-module-name' => NULL,
                    ]
                ],
            ],
        ],
        'model' => [
            'id' => 'int',
            'user' => NULL, // accepted params [ 'user,bigint' => 'user,fieldtype' ]
            'object' => 'Object\\Model\\ObjectSample',
            'text' => NULL, // accepted params: [ 'nullable;default' => 'nullable,null' ]
            'varchar' => '100', // accepted params: [ 'len;unique;nullable;default' => '100,unique,nullable,null'] 
            'bigint' => NULL, // accepted params: [ 'unsigned;nullable;default' => 'unsigned,nullable,1' ]
            'double' => '12,3', // accepted params: [ '12,3;unsigned;nullable;default' => 'unsigned,nullable,1' ]
            'integer' => NULL, // accepted params: [ 'unsigned;nullable;unique;default' = 'unsigned,nullable,unique,1' ]
            'tinyint' => NULL, // accepted params: [ 'unsigned;nullable;default' = 'unsigned,nullable,1' ]
            'enum' => 'merchant.status',
            'date' => NULL, // accepted params: [ 'nullable,default' = 'nullable, null' ]
            'datetime' => NULL, // accepted params: [ 'nullable,default' = 'nullable, null' ]
        ],
        'controller' => [
            "gate" => "api",
            "model" => "BbGallery",
            "format" => [
                "name" => "details-options",
                "fields" => [
                    "product",
                    "details"
                ]
            ],
            "Doc.Path" => "BbMedia/Gallery",
            "route" => [
                "path" => [
                    "value" => "/media/(:media)/gallery",
                    "params" => [
                        "media" => "number"
                    ]
                ]
            ],
            "parents" => [
                "store" => [
                    "model" => "BbMedia\\Model\\Media",
                    "field" => "id",
                    "filters" => [
                        "status" => "1",
                        "services" => [
                            "user" => [
                                "property" => "id",
                                "column" => "user"
                            ],
                        ]
                    ]
                ],
                "details" => [
                    "model" => "Details\\Model\\Details",
                    "field" => "id",
                    "filters" => [
                        "status" => "1",
                        "parents" => [
                            "store" => [
                                "property" => "id",
                                "column" => "store"
                            ],
                            "product" => [
                                "property" => "id",
                                "column" => "product"
                            ]
                        ]
                    ],
                    "setget" => [
                        "property" => "id",
                        "column" => "details"
                    ]
                ]
            ],
            "filters" => [
                "status" => "1",
                "services" => [
                    "user" => [
                        "property" => "id",
                        "column" => "user"
                    ]
                ],
                "parents" => [
                    "details" => [
                        "property" => "id",
                        "column" => "details"
                    ]
                ]
            ],
            "methods" => [
                "index" => [
                    "filters" => [
                        "name",
                        "status"
                    ],
                    "sorts" => [
                        "id",
                        "name",
                        "created"
                    ]
                ],
                "single" => [],
                "create" => [
                    "form" => "api.details-options.create",
                    "columns" => [
                        "services" => [
                            "user" => [
                                "property" => "id",
                                "column" => "user"
                            ]
                        ]
                    ]
                ],
                "update" => [
                    "form" => "api.details-options.update"
                ],
                "delete" => [
                    "status" => "0"
                ]
            ],
        ],
        'app' => [
            'type' => 'app',
            'path' => false,
            'name' => 'CartApp',
            'version' => '0.0.1',
            'host' => 'cart.test',
            'https' => false,
            'keep_module' => true,
            'timezone' => 'Asia/Jakarta',
            'local_repo' => __DIR__ . '_repo',
            'moduledir' => 'module-cart',
        ],

    ];

    static $availableOptions = [
        'migration' => [
            'types' => [
                'text' => [
                    'CHAR',
                    'ENUM',
                    'LONGTEXT',
                    'SET',
                    'TEXT',
                    'TINYTEXT',
                    'VARCHAR'
                ],
                'numeric' => [
                    'BIGINT',
                    'BOOLEAN',
                    'DECIMAL',
                    'DOUBLE',
                    'FLOAT',
                    'INTEGER',
                    'TINYINT',
                    'SMALLINT',
                    'MEDIUMINT',
                ],
                'date' => [
                    'DATE',
                    'DATETIME',
                    'TIMESTAMP',
                    'TIME',
                    'YEAR'
                ],
            ],
            'attributes' => [
                'null',
                'default',
                'update',
                'unsigned',
                'unique',
                'primary_key',
                'auto_increment'
            ]
        ],
        'formatter' => [
            'types' => [
                'number' => [
                    'type' => 'number',
                    'decimal' => 2
                ],
                'user' => [
                    'type' => 'user'
                ],
                'object' => [
                    'type' => 'object',
                    'model' => [
                        'name' => 'Object\\Model\\Obj',
                        'field' => 'id',
                        'type' => 'number'
                    ],
                    'format' => 'std-object'
                ],
                'text' => [
                    'type' => 'text'
                ],
                'date' => [
                    'type' => 'date'
                ]

            ]
        ]
    ];

    public static function build($generatorBasedir, $commandsArgs)
    {
        $inputFile = 'generator.yaml';
        $output = 'generator';
        if (count($commandsArgs) > 2) {
            Bash::error('Too Many Arguments');
        }
        if (count($commandsArgs) === 2 && current($commandsArgs) === 'run') {
            if (!is_file($generatorBasedir . '/' . $commandsArgs[1])) {
                Bash::error('Invalid generator file name');
            }
            $inputFile = $commandsArgs[1];
        }

        if (count($commandsArgs) === 2 &&  current($commandsArgs) === 'init') {
            $output = to_slug($commandsArgs[1]);
        }
        $command = current($commandsArgs);


        if ($command === 'init') {
            self::boilerplate($generatorBasedir, $output);
            Bash::echo('Config file generated');
        } elseif ($command === 'run') {
            self::generate($generatorBasedir, $inputFile);
        } else {
            Bash::error('Unrecognized command!');
        }
        return;
    }

    static function boilerplate($dir, $output)
    {
        $emit = self::$config['model'];
        $currentDirName = explode('/', $dir);
        $currentDirName = to_slug(end($currentDirName));
        $cname = str_replace('-', '_', $currentDirName);
        
        if ($output === 'controller') {
            $emit = self::$config['controller'];
        }
        if ($output === 'app') {
            $emit = self::$config['controller'];
        }
        $emit = [
            $cname => $emit
        ];
        Fs::write($dir . sprintf('/%s.yaml', $output), \yaml_emit($emit));
    }

    static function generate($dir, $inputFile)
    {
        if(!is_file($dir . '/'. $inputFile)) {
            Bash::error('File not found, please specify name!');
        }
        $yaml = file_get_contents($dir . '/'. $inputFile) ?? '';
        $yaml = \yaml_parse($yaml);

        if( isset( $yaml['host'] ) ) {
            self::buildApp($dir, $yaml);
            Bash::echo('Generator executed successfully');
        }
        else {
            ModuleBuilder::buildExtend($dir, $yaml);
            Bash::echo('Generator executed successfully');
        }
    }

    static function buildApp()
    {
        // to-do list
    }
}
