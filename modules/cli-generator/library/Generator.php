<?php

/**
 * Controller helper
 * @package cli-generator
 * @version 0.1.0
 */

namespace CliGenerator\Library;

use Cli\Library\Bash;
use Mim\Library\Fs;
use CliModule\Library\BController;

class Generator
{
    static $config = [
        'module' => [
            'repo_dir' => '_repo',
            'author' => [
                'name' => 'Dev',
                'email' => 'dev@github.net',
                'website' => '',
            ],
            'items' => [
                [
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
                    'controller' => [
                        'items' => [
                            [
                                "gate" => "api",
                                "extends" => "\\Api\\Controller",
                                "model" => "\\Options\\Model\\Options",
                                "format" => [
                                    "name" => "details-options",
                                    "fields" => [
                                        "product",
                                        "details"
                                    ]
                                ],
                                "Doc.Path" => "Store/Product/Details/Options",
                                "route" => [
                                    "path" => [
                                        "value" => "/store/(:store)/product/(:product)/details/(:details)/(:type)/options",
                                        "params" => [
                                            "store" => "number",
                                            "product" => "number",
                                            "details" => "number",
                                            "type" => "slug"
                                        ]
                                    ]
                                ],
                                "parents" => [
                                    "store" => [
                                        "model" => "Store\\Model\\Store",
                                        "field" => "id",
                                        "filters" => [
                                            "status" => "1",
                                            "services" => [
                                                "user" => [
                                                    "property" => "id",
                                                    "column" => "user"
                                                ],
                                                "brand" => [
                                                    "property" => "id",
                                                    "column" => "merchant_brand"
                                                ]
                                            ]
                                        ]
                                    ],
                                    "product" => [
                                        "model" => "Product\\Model\\Product",
                                        "field" => "id",
                                        "filters" => [
                                            "parents" => [
                                                "store" => [
                                                    "property" => "id",
                                                    "column" => "store"
                                                ]
                                            ]
                                        ],
                                        "setget" => [
                                            "property" => "id",
                                            "column" => "product"
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
                                "auths" => [
                                    "app" => true,
                                    "user" => true
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
                                "name" => "DetailsController",
                                "ns" => "Module\\Controller"
                            ]
                        ],
                    ],
                    'model' => [
                        'items' => [
                            [
                                'name' => 'gallery_item',
                                'fields' => [
                                    'id' => [
                                        'type' => 'INTEGER',
                                        'attrs' => [
                                            'unsigned' => TRUE,
                                            'primary_key' => TRUE,
                                            'auto_increment' => TRUE,
                                        ],
                                        'format' => [
                                            'type' => 'number'
                                        ]
                                    ],
                                    'user' => [
                                        'type' => 'INTEGER',
                                        'attrs' => [
                                            'unsigned' => TRUE,
                                            'null' => FALSE,
                                        ],
                                        'format' => [
                                            'type' => 'user'
                                        ]
                                    ],
                                    'object' => [
                                        'type' => 'INTEGER',
                                        'attrs' => [
                                            'unsigned' => TRUE,
                                            'null' => FALSE,
                                        ],
                                        'format' => [
                                            'type' => 'object',
                                            'model' => [
                                                'name' => 'Object\\Model\\Obj',
                                                'field' => 'id',
                                                'type' => 'number'
                                            ],
                                            'format' => 'std-object'
                                        ]
                                    ],
                                    'text' => [
                                        'type' => 'TEXT',
                                        'attrs' => [],
                                        'format' => [
                                            'type' => 'text'
                                        ]
                                    ],
                                    'varchar' => [
                                        'type' => 'VARCHAR',
                                        'length' => 100,
                                        'attrs' => [
                                            'null' => TRUE,
                                            'unique' => TRUE,
                                        ],
                                        'format' => [
                                            'type' => 'text'
                                        ]
                                    ],
                                    'bigint' => [
                                        'type' => 'BIGINT',
                                        'attrs' => [
                                            'unsigned' => TRUE,
                                            'null' => FALSE,
                                            'default' => 1,
                                        ],
                                        'format' => [
                                            'type' => 'number'
                                        ]
                                    ],
                                    'double' => [
                                        'type' => 'DOUBLE',
                                        'length' => '12,3',
                                        'attrs' => [
                                            'unsigned' => TRUE,
                                            'null' => FALSE,
                                            'default' => 12.2,
                                        ],
                                        'format' => [
                                            'type' => 'number'
                                        ]
                                    ],
                                    'integer' => [
                                        'type' => 'INTEGER',
                                        'attrs' => [
                                            'unsigned' => TRUE,
                                            'null' => FALSE,
                                            'default' => 1,
                                        ],
                                        'format' => [
                                            'type' => 'number'
                                        ]
                                    ],
                                    'tinyint' => [
                                        'type' => 'TINYINT',
                                        'attrs' => [
                                            'unsigned' => TRUE,
                                            'null' => FALSE,
                                            'default' => 1,
                                        ],
                                        'format' => [
                                            'type' => 'number'
                                        ]
                                    ],
                                    'enum' => [
                                        'type' => 'TINYINT',
                                        'attrs' => [
                                            'unsigned' => TRUE,
                                            'null' => FALSE,
                                            'default' => 1,
                                        ],
                                        'format' => [
                                            'type' => 'enum',
                                            'enum' => 'merchant.status',
                                            'vtype' => 'int'
                                        ]
                                    ],
                                    'date' => [
                                        'type' => 'DATE',
                                        'attrs' => [
                                            'null' => FALSE,
                                        ],
                                        'format' => [
                                            'type' => 'date'
                                        ]
                                    ],
                                    'datetime' => [
                                        'type' => 'DATETIME',
                                        'attrs' => [
                                            'null' => FALSE,
                                        ],
                                        'format' => [
                                            'type' => 'date'
                                        ]
                                    ],
                                    'created' => [
                                        'type' => 'TIMESTAMP',
                                        'attrs' => [
                                            'default' => 'CURRENT_TIMESTAMP',
                                        ],
                                        'format' => [
                                            'type' => 'date'
                                        ]
                                    ],
                                    'updated' => [
                                        'type' => 'TIMESTAMP',
                                        'attrs' => [
                                            'default' => 'CURRENT_TIMESTAMP',
                                            'update' => 'CURRENT_TIMESTAMP',
                                        ],
                                        'format' => [
                                            'type' => 'date'
                                        ]
                                    ],

                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'app' => [
            'items' => [
                [
                    'path' => false,
                    'name' => 'CartApp',
                    'version' => '0.0.1',
                    'host' => 'cart.test',
                    'https' => false,
                    'keep_module' => true,
                    'timezone' => 'Asia/Jakarta',
                    'local_repo' => __DIR__ . '_repo',
                    'moduledir' => 'module-cart',
                ]
            ]
        ]

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

    public static function build($generatorBasedir, $command)
    {
        if ($command === 'init') {
            self::boilerplate($generatorBasedir);
            Bash::echo('Config file generated');
        } elseif ($command === 'run') {
            self::generate($generatorBasedir);
        } else {
            Bash::error('Unrecognized command!');
        }
        return;
    }

    static function boilerplate($dir)
    {
        Fs::write($dir . '/generator.yaml', \yaml_emit(self::$config));
        Fs::write($dir . '/options.yaml', \yaml_emit(self::$availableOptions));
    }

    static function generate($dir)
    {
        $yaml = file_get_contents($dir . '/generator.yaml') ?? '';
        $yaml = \yaml_parse($yaml);

        $appConfigs = $yaml['app']['items'] ?? [];
        $moduleConfigs = $yaml['module']['items'] ?? [];

        foreach ($appConfigs as $config) {
            self::buildApp($dir, $config);
        }
        foreach ($moduleConfigs as $config) {
            $config['author'] = $yaml['module']['author'];
            self::buildModule($dir . '/' . $yaml['module']['repo_dir'], $config);
        }
        return;
    }

    static function buildApp()
    {
        // to-do list
    }

    static function buildModule($dir, $config)
    {
        ModuleBuilder::buildExtend($dir . '/' . $config['name'], $config);
        Bash::echo('Generator executed successfully');
    }
}
