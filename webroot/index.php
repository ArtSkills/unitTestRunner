<?php
ini_set('display_errors', '1');
require __DIR__ . '/../vendor/autoload.php';

use ArtSkills\TestRunner\IndexController;
$controller = new IndexController();
$controller->main();