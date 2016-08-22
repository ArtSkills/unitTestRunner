<?php
namespace App\Lib;

class System
{
	/**
	 * Выполнение комманды сервера относительно $relativePath
	 *
	 * @param string $cmd
	 * @param string|boolean $relativePath
	 * @param bool $stringResult
	 * @return string|array
	 */
	public static function execute($cmd, $relativePath = false, $stringResult = true) {
		if ($relativePath !== false) {
			$curDir = getcwd();
			chdir($relativePath);
		}

		$output = '';
		exec($cmd.' 2>&1', $output);

		if ($relativePath !== false) {
			chdir($curDir);
		}

		if ($stringResult) {
			return implode("\n", $output);
		} else {
			return $output;
		}
	}
}