<?php
ini_set('display_errors', '1');
require __DIR__ . '/../vendor/autoload.php';

use ArtSkills\TestRunner\TestingShell;
$shell = new TestingShell();
$shell->main();
