<?php

/**
 * Module builder
 * @package cli-generator
 * @version 0.0.8
 */

namespace CliGenerator\Library;

use Mim\Library\Fs;
use Cli\Library\Bash;
use CliModule\Library\BController;
use CliModule\Library\BModel;
use CliModule\Library\ConfigCollector;

class ModuleBuilder extends \CliModule\Library\Builder

{
    static function buildExtend(string $here, array $config): bool
    {
        if (isset($config['regenerate']) && $config['regenerate'] === true) {
            if (is_dir($here)) {
                Fs::rmdir($here);
            }
        }

        // make sure the folder is empty
        $models = $config['model']['items'] ?? [];
        $controllers = $config['controller']['items'] ?? [];
        unset($config['controller']);
        unset($config['model']);
        Fs::mkdir($here);

        $gitignore = [];
            foreach ($config['gitignore'] ?? [] as $ignore) {
                $gitignore[$ignore] = true;
            }
            $createModuleDir = sprintf('modules/%s', $config['name']);
            $config = [
                '__name' => $config['name'],
                '__version' => '0.0.1',
                '__git' => $config['git'] ?? '-',
                '__license' => $config['license'] ?? 'MIT',
                '__author' => $config['author'] ?? ['name' => '', 'email' => '-', 'website' => '-'],
                '__files' => [
                    $createModuleDir => $config['files'] ?? ['install', 'update', 'remove']
                ],
                '__dependencies' => [
                    'required' => $config['dependencies']['required'] ?? [['required-module' => NULL]],
                    'optional' => $config['dependencies']['optional'] ?? [['optional-module' => NULL,]],
                ],
                'autoload' => [
                    'classes' => [],
                    'files' => []
                ],
                '__gitignore' => $gitignore
            ];

        if (Fs::scan($here)) {
            Bash::echo('Target module already exists : ' . $here);
        } else {
            // make sure we can write here
            if (!is_writable($here)) {
                Bash::echo('Unable to write to current directory : ' . $here);
                return false;
            }
            // $config = ConfigCollector::collect($here);

            if (!$config)
                return false;

            $mod_name = $config['__name'];
            $mod_dir  = $here . '/modules/' . $mod_name;
            $mod_conf_file = $mod_dir . '/config.php';

            $nl = PHP_EOL;

            $tx = '<?php' . $nl;
            $tx .= $nl;
            $tx .= 'return ' . to_source($config) . ';';

            Fs::write($mod_conf_file, $tx);

            // now, create readme file
            self::readme($here, $config['__name'], $config['__git']);
        }




        foreach ($models as $c) {
            self::buildModel($here, $c, $config['__name']);
        }
        foreach ($controllers as $c) {
            self::buildController($here, $c, $config['__name']);
        }
        return true;
    }

    static function buildModel($moduleDir, $config, $moduleName = null)
    {
        $tableName = $config['name'];
        $config['name'] = to_ns($tableName);
        $config['properties'] = [
            [
                'name' => 'table',
                'prefix' => 'protected static',
                'value' => $tableName
            ],
            [
                'name' => 'chains',
                'prefix' => 'protected static',
                'value' => [],
            ],
            [
                'name' => 'q',
                'prefix' => 'protected static',
                'value' => []
            ]
        ];
        if (!isset($config['extends'])) {
            $config['extends'] = '\\Mim\\Model';
        }
        if (!isset($config['methods'])) {
            $config['methods'] = [];
        }
        if (!isset($config['implements'])) {
            $config['implements'] = [];
        }
        if (!isset($config['ns'])) {
            $config['ns'] = to_ns($moduleName) . '\\Model';
        }

        $start = 1;
        foreach ($config['fields'] as $fieldName => &$field) {
            if (is_string($field)) {
                if ($fieldName === 'id' && $field === 'id') {
                    $field = [
                        'type' => 'INTEGER',
                        'attrs' => [
                            'unsigned' => true,
                            'primary_key' => true,
                            'auto_increment' => true,
                        ],
                        'format' => [
                            'type' => 'number'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }
                if ($fieldName === 'user' && $field === 'user') {
                    $field = [
                        'type' => 'INTEGER',
                        'attrs' => [
                            'unsigned' => true,
                            'null' => false,
                        ],
                        'format' => [
                            'type' => 'user'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }
                if (strtolower($field) === 'text') {
                    $field = [
                        'type' => 'TEXT',
                        'attrs' => [],
                        'format' => [
                            'type' => 'text'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }
                if (strtolower($field) === 'varchar') {
                    $field = [
                        'type' => 'VARCHAR',
                        'length' => 100,
                        'attrs' => [
                            'null' => true,
                            'unique' => false
                        ],
                        'format' => [
                            'type' => 'text'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }

                if (strtolower($field) === 'double') {
                    $field = [
                        'type' => 'DOUBLE',
                        'length' => '12,3',
                        'attrs' => [
                            'null' => true,
                            'unsigned' => true,
                            'default' => 0
                        ],
                        'format' => [
                            'type' => 'number'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }

                if ( str_starts_with($field, "enum:") ) {
                    $enum = explode('enum:', $field);
                    $enum = end($enum);
                    $field = [
                        'type' => 'TINYINT',
                        'attrs' => [
                            'unsigned' => true,
                            'null' => false,
                            'default' => 1
                        ],
                        'format' => [
                            'type' => 'enum',
                            'enum' => $enum,
                            'vtype' => 'int'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }

                if (strtolower($field) === 'integer' || strtolower($field) === 'int') {
                    $field = [
                        'type' => 'INTEGER',
                        'attrs' => [
                            'null' => false,
                            'unsigned' => true,
                            'default' => 0
                        ],
                        'format' => [
                            'type' => 'number'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }
                if (strtolower($field) === 'tinyinteger' || strtolower($field) === 'tinyint') {
                    $field = [
                        'type' => 'TINYINT',
                        'attrs' => [
                            'null' => false,
                            'unsigned' => true,
                            'default' => 1
                        ],
                        'format' => [
                            'type' => 'number'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }
                if (strtolower($field) === 'date') {
                    $field = [
                        'type' => 'DATE',
                        'attrs' => [
                            'null' => false
                        ],
                        'format' => [
                            'type' => 'date'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }
                if (strtolower($field) === 'datetime') {
                    $field = [
                        'type' => 'DATETIME',
                        'attrs' => [
                            'null' => false
                        ],
                        'format' => [
                            'type' => 'date'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }
                if (strtolower($field) === 'created') {
                    $field = [
                        'type' => 'TIMESTAMP',
                        'attrs' => [
                            'default' => 'CURRENT_TIMESTAMP'
                        ],
                        'format' => [
                            'type' => 'date'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }
                if (strtolower($field) === 'updated') {
                    $field = [
                        'type' => 'TIMESTAMP',
                        'attrs' => [
                            'default' => 'CURRENT_TIMESTAMP',
                            'update' => 'CURRENT_TIMESTAMP'
                        ],
                        'format' => [
                            'type' => 'date'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }
            }
            if (is_array($field)) {
                if(!isset($field['index'])) {
                    $field['index'] = (int) ($start . '000');
                }
            } else {
                unset($config['fields'][$fieldName]);
            }
            $start++;
        }
        if (!isset($config['fields']['created'])) {
            $start++;
            $config['fields']['created'] = [
                'type' => 'TIMESTAMP',
                'attrs' => [
                    'default' => 'CURRENT_TIMESTAMP'
                ],
                'format' => [
                    'type' => 'date'
                ],
                'index' => (int) ($start . '000')
            ];
        }
        if (!isset($config['fields']['updated'])) {
            $start++;
            $config['fields']['updated'] = [
                'type' => 'TIMESTAMP',
                'attrs' => [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP'
                ],
                'format' => [
                    'type' => 'date'
                ],
                'index' => (int) ($start . '000')
            ];
        }

        BModel::build($moduleDir, $tableName, $config);
    }
    static function buildController($moduleDir, $config, $moduleName = null)
    {
        if (isset($config['regenerate']) && $config['regenerate'] === true) {
            if (is_dir($moduleDir)) {
                Fs::rmdir($moduleDir);
            }
        }
        $config['extends'] = '\Api\Controller';

        if (!isset($config['auths'])) {
            $config['auths'] = [
                'app' => true,
                'user' => true,
            ];
        }

        if (!isset($config['gate'])) {
            $config['gate'] = 'api';
        }
        if (!isset($config['ns'])) {
            $config['ns'] = sprintf('%s\\Controller', to_ns($moduleName));
        }

        if (isset($config['parents']) && is_array($config['parents'])) {
            foreach ($config['parents'] as $parentName => &$parent) {
                if (isset($parent['filters']) && is_array($parent['filters'])) {
                    foreach ($parent['filters'] as &$filter) {
                        if (is_array($filter)) {
                            $filterCtr = [];
                            foreach ($filter as $f) {
                                if (!is_array($f))
                                    $filterCtr[$f] = [
                                        'property' => 'id',
                                        'column' => $f,
                                    ];
                            }
                            $filter = $filterCtr;
                        }
                    }
                }
                if (isset($parent['setget']) && true === $parent['setget']) {
                    $parent['setget'] = [
                        'property' => 'id',
                        'column' => $parentName
                    ];
                }
            }
        }

        foreach ($config['filters'] as &$filter) {
            if (is_array($filter) && count($filter) === 1 && !is_array($field = current($filter))) {
                $filter = [
                    $field => [
                        'property' => 'id',
                        'column' => $field,
                    ]
                ];
            }
        }

        if (isset($config['methods']) && is_array($config['methods'])) {
            foreach ($config['methods'] as $name => &$method) {
                if (isset($method['filters']) && !is_array($method['filters'])) {
                    $method['filters'] = explode(',', $method['filters']);
                }
                if (isset($method['sorts']) && !is_array($method['sorts'])) {
                    $method['sorts'] = explode(',', $method['sorts']);
                }
                if ($name === 'create') {
                    $method['form'] = sprintf('api.%s.create', str_replace('api-', '', $moduleName ));
                }
                if ($name === 'update') {
                    $method['form'] = sprintf('api.%s.update', str_replace('api-', '', $moduleName ));
                }
                if ($name === 'delete') {
                    if (is_int($method)) {
                        $method = [
                            'status' => (int) $method
                        ];
                    } else {
                        $method = [];
                    }
                }
            }
        }
        BController::build($moduleDir, $config['name'], $config);
    }
}
