<?php

/**
 * Module builder
 * @package cli-generator
 * @version 0.0.1
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
        // make sure the folder is empty

        $models = $config['model']['items'] ?? [];
        $controllers = $config['controller']['items'] ?? [];
        unset($config['controller']);
        unset($config['model']);
        Fs::mkdir($here);

        if (Fs::scan($here)) {
            Bash::echo('Target module already exists : ' . $here);
        } else {
            // make sure we can write here
            if (!is_writable($here)) {
                Bash::echo('Unable to write to current directory : ' . $here);
                return false;
            }
            // $config = ConfigCollector::collect($here);

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
                    $createModuleDir => $config['files'] ?? [ 'install', 'update', 'remove' ]
                ],
                '__dependencies' => [
                    'required' => $config['dependencies']['required'] ?? [ [ 'required-module' => NULL ] ],
                    'optional' => $config['dependencies']['optional'] ?? [ [ 'optional-module' => NULL, ] ],
                ],
                'autoload' => [
                    'classes' => [],
                    'files' => []
                ],
                '__gitignore' => $gitignore
            ];

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
            self::buildController($here, $c);
        }
        return true;
    }

    static function buildModel($moduleDir, $config, $moduleName = null)
    {

        // $config['name'] = self::toSnake($config['name']);
        $config['name'] = self::toCamel($config['name'], true, '-');
        $config['properties'] = [
            [
                'name' => 'table',
                'prefix' => 'protected static',
                'value' => self::toSnake( $config['name'] )
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
            $config['ns'] = self::toCamel($moduleName ?? $config['name'], true, '_') . '\\Model';
        }

        $start = 1;
        foreach ($config['fields'] as &$field) {
            if (!isset($field['index'])) {
                $field['index'] = (int) ($start . '000');
            }
            $start++;
        }
        
        BModel::build($moduleDir, self::toSnake( $config['name'] ), $config);
    }
    static function buildController($moduleDir, $config)
    {
        BController::build($moduleDir, $config['name'], $config);
    }

    static function toCamel($string, $capitalizeFirstCharacter = false, $find = '_')
    {

        $str = str_replace(' ', '', ucwords(str_replace($find, ' ', $string)));

        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }
    static function toSnake($input)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
