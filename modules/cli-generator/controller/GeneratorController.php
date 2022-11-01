<?php
/**
 * GeneratorController
 * @package cli-generator
 * @version 1.0.0
 */

namespace CliGenerator\Controller;

use Cli\Library\Bash;
use CliGenerator\Library\Generator;

class GeneratorController extends \Cli\Controller\ToolController
{
    public function generatorAction()
    {
        $currentDir = getcwd();
        if( Generator::build( $currentDir, $this->req->param->command ) ) {
            Bash::echo('Generator executed succesfully');
        }
    }
}