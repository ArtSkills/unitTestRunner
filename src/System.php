<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 16.08.16
 * Time: 11:49
 */

namespace ArtSkills\TestRunner;


class System
{
	/**
	 * Выполнение комманды сервера относительно $relativePath
	 *
	 * @param string $cmd
	 * @param string|boolean $relativePath
	 * @return string
	 */
	public static function execute($cmd, $relativePath = false) {
		if ($relativePath !== false) {
			$curDir = getcwd();
			chdir($relativePath);
		}

		$output = '';
		exec($cmd, $output);

		if ($relativePath !== false) {
			chdir($curDir);
		}
		return implode("\n", $output);
	}
}