<?php
/**
 * Autocomplete provider
 * @package cli-generator
 * @version 0.1.0
 */

namespace CliGenerator\Library;

class Autocomplete extends \Cli\Autocomplete
{
	static function files(array $args): string{
		return '2';
	}

	static function command(array $args): string{
		$farg = $args[1] ?? null;
		$result = ['generator', 'init', 'run'];

		if(!$farg)
			return trim(implode(' ', $result));

		return parent::lastArg($farg, $result);
	}
}