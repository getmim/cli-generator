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
            foreach($config['gitignore'] ?? [] as $ignore) {
                $gitignore[$ignore] = true;
            }
            $createModuleDir = sprintf('modules/%s', $config['name']);
            $config = [
                '__name' => $config['name'],
                '__version' => '0.0.1',
                '__git' => $config['git'] ?? '-',
                '__license' => $config['license'] ?? 'MIT',
                '__author' => $config['author'] ?? [ 'name'=> '', 'email' => '-', 'website' => '-' ],
                '__files' => [
                    $createModuleDir => $config['files']
                ],
                '__dependencies' => [
                    'required' => $config['dependencies']['required'] ?? [],
                    'optional' => $config['dependencies']['optional'] ?? [],
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
            self::buildModel($here, $c);
        }
        foreach ($controllers as $c) {
            self::buildController($here, $c);
        }
        return true;
    }

    static function buildModel($moduleDir, $config)
    {

        $tableName = null;
        foreach ($config['properties'] as $cfg) {
            if (is_array($cfg)) {
                foreach ($cfg as $k => $c) {
                    if ($k === 'name' && $c === 'table') {
                        $tableName = $cfg['value'];
                        break;
                    }
                }
            }
        }
        if (!$tableName) {

            Bash::echo('Model ' . $config['name'] . ' Skipped, no table name detected');
            return;
        }
        BModel::build($moduleDir, $tableName, $config);
    }
    static function buildController($moduleDir, $config)
    {
        BController::build($moduleDir, $config['name'], $config);
    }
}
