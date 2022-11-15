<?php

/**
 * Module builder
 * @package cli-generator
 * @version 1.0.0
 */

namespace CliGenerator\Library;

use Mim\Library\Fs;
use Cli\Library\Bash;
use CliModule\Library\BModel;
use CliModule\Library\BController;

class ModuleBuilder extends \CliModule\Library\Builder

{
    static function buildExtend(string $here, array $obj): bool
    {
        Fs::mkdir($here);
        $author = ['name' => '', 'email' => '-', 'website' => '-'];

        if(is_file(BASEPATH . '/etc/cache/module-init.php')) {
            $include = require BASEPATH . '/etc/cache/module-init.php';
            if(isset($include['author'])) {
                $author = $include['author'];
            }
        }
        $name = explode('/', $here);
        $name = end($name);

        $createModuleDir = sprintf('modules/%s', $name);
        $config = [
            '__name' => $name,
            '__version' => '0.0.1',
            '__git' => '-',
            '__license' => 'MIT',
            '__author' => $author,
            '__files' => [
                $createModuleDir => ['install', 'update', 'remove']
            ],
            '__dependencies' => [
                'required' => [],
                'optional' => [],
            ],
            'autoload' => [
                'classes' => [],
                'files' => []
            ],
            '__gitignore' => []
        ]; {
            // make sure we can write here
            if (!is_writable($here)) {
                Bash::echo('Unable to write to current directory : ' . $here);
                return false;
            }

            if (!$config) {
                return false;
            }
            // check if lib_enum exists
            if( isset($obj['lib_enum']) ) {
                $config['libEnum']['enums'] = $obj['lib_enum'];
                unset($obj['lib_enum']);
                $config['__dependencies']['required'][] = [
                    'lib-enum' => NULL
                ];
            }

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
        $first = current($obj);
        $isApi = isset($first['gate']);
        unset($first);
        if (!$isApi) {
            // object
            foreach($obj as $tableName=>$modelConfig) {
                self::buildModel($here, [ $tableName => $modelConfig ], $config);
            }
        } else {
            // api
            foreach($obj as $ctrlName=>$ctrlConfig) {
                self::buildController($here, [ $ctrlName => $ctrlConfig ], $config);
            }
        }
        return true;
    }

    static function buildModel($moduleDir, $data, &$config = null)
    {
        $currentDirName = explode('/', $moduleDir);
        $currentDirName = to_slug(end($currentDirName));
        $tableName = current(array_keys($data));
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
        $config['__dependencies']['required'][] = [
            'lib-formatter' => NULL
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
            $config['ns'] = to_ns($currentDirName) . '\\Model';
        }

        $start = 1;
        $data = current($data);
        foreach ($data as $fieldName => &$field) {
            if (is_string($field) || !$field) {

                if ($field && ($fieldName === 'id' || str_starts_with($field, 'id;'))) {

                    $params = explode(';', $field);
                    $type = 'INTEGER';

                    if (isset($params[1]) && $params[1] === 'bigint') {
                        $type = 'BIGINT';
                    }

                    $config['fields'][$fieldName] = [
                        'type' => $type,
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

                if (($fieldName === 'user' && !$field) || ($field === 'user' || ($field && str_starts_with($field, 'user;')))) {

                    $type = 'INTEGER';

                    if ($field) {

                        if ($field === 'user') {
                            $fieldName = 'user';
                        } else {
                            $params = explode(';', $field);
                            array_shift($params);
                            if (isset($params[0]) && $params[0] === 'bigint') {
                                $type = 'BIGINT';
                            }
                        }
                    }

                    $config['fields'][$fieldName] = [
                        'type' => $type,
                        'attrs' => [
                            'unsigned' => true,
                            'null' => false,
                        ],
                        'format' => [
                            'type' => 'user'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $config['__dependencies']['required'][] = [
                        'lib-user' => NULL
                    ];
                    $start++;
                    continue;
                }

                if ($field && (strtolower($field) === 'text' || str_starts_with($field, 'text;'))) {

                    $params = explode(';', $field);
                    array_shift($params);
                    $attrs = [];

                    if (in_array('nullable', $params)) {
                        $attrs['null'] = true;
                    } else {
                        $attrs['null'] = false;
                        $attrs['default'] = null;
                    }

                    if (count($params) === 2) {
                        $attrs['default'] = trim(end($params));
                        if (strtolower($attrs['default']) === 'null') {
                            $attrs['default'] = null;
                        }
                    }

                    $config['fields'][$fieldName] = [
                        'type' => 'TEXT',
                        'attrs' => $attrs,
                        'format' => [
                            'type' => 'text'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }

                if ($field && (strtolower($field) === 'double' || str_starts_with($field, 'double;'))) {

                    $params = explode(';', $field);
                    array_shift($params);
                    $attrs = [];

                    if (in_array('nullable', $params)) {
                        $attrs['null'] = true;
                    }
                    else {
                        $attrs['null'] = false;
                        $attrs['default'] = null;
                    }

                    if (isset($params[0])) {
                        $len = $params[0];
                    }

                    if (in_array('unique', $params)) {
                        $attrs['unique'] = true;
                    }
                    else {
                        $attrs['unique'] = false;
                    }

                    if (in_array('unsigned', $params)) {
                        $attrs['unsigned'] = true;
                    }

                    if (count($params) === 4) {

                        $attrs['default'] = trim(end($params));
                        if (strtolower($attrs['default']) === 'null') {
                            $attrs['default'] = null;
                        }
                    }

                    $config['fields'][$fieldName] = [
                        'type' => 'DOUBLE',
                        'length' => $len,
                        'attrs' => $attrs,
                        'format' => [
                            'type' => 'number'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }

                if ($field && ((strtolower($field) === 'integer' || str_starts_with($field, 'integer;')) || (strtolower($field) === 'int' || str_starts_with($field, 'int;')))) {

                    $params = explode(';', $field);
                    array_shift($params);
                    $attrs = [];

                    if (in_array('nullable', $params)) {
                        $attrs['null'] = true;
                    }
                    else {
                        $attrs['null'] = false;
                        $attrs['default'] = null;
                    }

                    if (in_array('unsigned', $params)) {
                        $attrs['unsigned'] = true;
                    }

                    if (in_array('unique', $params)) {
                        $attrs['unique'] = true;
                    }
                    else {
                        $attrs['unique'] = false;
                    }

                    if (count($params) === 4) {

                        $attrs['default'] = trim(end($params));
                        if (strtolower($attrs['default']) === 'null') {
                            $attrs['default'] = null;
                        }
                    }

                    $config['fields'][$fieldName] = [
                        'type' => 'INTEGER',
                        'attrs' => $attrs,
                        'format' => [
                            'type' => 'number'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }

                if ($field && ((strtolower($field) === 'tinyinteger' || str_starts_with($field, 'tinyinteger;')) || (strtolower($field) === 'tinyint' || str_starts_with($field, 'tinyint;')))) {

                    $params = explode(';', $field);
                    array_shift($params);
                    $attrs = [];

                    if (in_array('nullable', $params)) {
                        $attrs['null'] = true;
                    }
                    else {
                        $attrs['null'] = false;
                        $attrs['default'] = null;
                    }

                    if (in_array('unsigned', $params)) {
                        $attrs['unsigned'] = true;
                    }

                    if (in_array('unique', $params)) {
                        $attrs['unique'] = true;
                    }
                    else {
                        $attrs['unique'] = false;
                    }

                    if (count($params) === 4) {
                        $attrs['default'] = trim(end($params));
                        if (strtolower($attrs['default']) === 'null') {
                            $attrs['default'] = null;
                        }
                    }

                    $config['fields'][$fieldName] = [
                        'type' => 'TINYINT',
                        'attrs' => $attrs,
                        'format' => [
                            'type' => 'number'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }

                if ($field && (strtolower($field) === 'varchar' || str_starts_with($field, 'varchar;'))) {

                    $params = explode(';', $field);
                    $len = 100;
                    array_shift($params);

                    if (isset($params[0])) {
                        $len = $params[0];
                    }

                    $attrs = [];

                    if (in_array('nullable', $params)) {
                        $attrs['null'] = true;
                    }
                    else {
                        $attrs['null'] = false;
                        $attrs['default'] = null;
                    }

                    if (in_array('unique', $params)) {
                        $attrs['unique'] = true;
                    }
                    else {
                        $attrs['unique'] = false;
                    }

                    if (count($params) === 4) {
                        $attrs['default'] = trim(end($params));
                        if (strtolower($attrs['default']) === 'null') {
                            $attrs['default'] = null;
                        }
                    }

                    $config['fields'][$fieldName] = [
                        'type' => 'TEXT',
                        'length' => $len,
                        'attrs' => $attrs,
                        'format' => [
                            'type' => 'text'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }

                if ($field && (strtolower($field) === 'date' || str_starts_with($field, 'date;'))) {

                    $params = explode(';', $field);
                    array_shift($params);
                    $attrs = [];

                    if (in_array('nullable', $params)) {
                        $attrs['null'] = true;
                    }
                    else {
                        $attrs['null'] = false;
                        $attrs['default'] = null;
                    }

                    if (count($params) === 2) {
                        $attrs['default'] = trim(end($params));
                        if (strtolower($attrs['default']) === 'null') {
                            $attrs['default'] = null;
                        }
                    }

                    $config['fields'][$fieldName] = [
                        'type' => 'DATE',
                        'attrs' => $attrs,
                        'format' => [
                            'type' => 'date'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }

                if ($field && (strtolower($field) === 'datetime' || str_starts_with($field, 'datetime;'))) {

                    $params = explode(';', $field);
                    array_shift($params);
                    $attrs = [];

                    if (in_array('nullable', $params)) {
                        $attrs['null'] = true;
                    }
                    else {
                        $attrs['null'] = false;
                        $attrs['default'] = null;
                    }

                    if (count($params) === 2) {
                        $attrs['default'] = trim(end($params));
                        if (strtolower($attrs['default']) === 'null') {
                            $attrs['default'] = null;
                        }
                    }

                    $config['fields'][$fieldName] = [
                        'type' => 'DATETIME',
                        'attrs' => $attrs,
                        'format' => [
                            'type' => 'date'
                        ],
                        'index' => (int) ($start . '000')
                    ];
                    $start++;
                    continue;
                }

                if ($field && str_starts_with($field, 'enum;')) {

                    $params = explode(';', $field);
                    array_shift($params);

                    if (isset($params[0])) {

                        $len = $params[0];
                        $config['fields'][$fieldName] = [
                            'type' => 'TINYINT',
                            'attrs' => [
                                'unsigned' => true,
                                'null' => false,
                                'default' => 1
                            ],
                            'format' => [
                                'type' => 'enum',
                                'enum' => $params[0],
                                'vtype' => 'int'
                            ],
                            'index' => (int) ($start . '000')
                        ];
                        $start++;
                    }
                    continue;
                }
            } else {
                $field['index'] = (int) ($start . '000');
                $config['fields'][$fieldName] = $field;
                $start++;
            }
        }
        if (!isset($config['fields']['created'])) {
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
    static function buildController($moduleDir, $data, &$config = null)
    {
        $currentDirName = explode('/', $moduleDir);
        $currentDirName = to_slug(end($currentDirName));
        $cname = to_slug(current(array_keys($data)));
        $cname = str_ends_with($cname, 'controller') ? $cname : $cname . '-controller';
        $config['name'] = to_ns($cname);
        $data = current($data); 

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
            $config['ns'] = sprintf('%s\\Controller', to_ns($cname));
        }

        if (isset($data['parents']) && is_array($data['parents'])) {
            foreach ($data['parents'] as $parentName => &$parent) {
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
            $config['parents'] = $data['parents'];
        }

        foreach ($data['filters'] as &$filter) {
            if (is_array($filter) && count($filter) === 1 && !is_array($field = current($filter))) {
                $filter = [
                    $field => [
                        'property' => 'id',
                        'column' => $field,
                    ]
                ];
            }
        }
        $config['filters'] = $data['filters'];

        if (isset($data['methods']) && is_array($data['methods'])) {
            foreach ($data['methods'] as $name => &$method) {
                if (isset($method['filters']) && !is_array($method['filters'])) {
                    $method['filters'] = explode(',', $method['filters']);
                }
                if (isset($method['sorts']) && !is_array($method['sorts'])) {
                    $method['sorts'] = explode(',', $method['sorts']);
                }
                if ($name === 'create') {
                    $method['form'] = sprintf('api.%s.create', str_replace('api-', '', $cname));
                }
                if ($name === 'update') {
                    $method['form'] = sprintf('api.%s.update', str_replace('api-', '', $cname));
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
                $config['methods'][$name] = $method;
            }
        }
        // dd($config, $data);
        $config['route'] = $data['route'];
        $config['model'] = $data['model'];
        BController::build($moduleDir, $cname, $config);
    }
}
