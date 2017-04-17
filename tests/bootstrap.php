<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once AS_COMMON . 'config/bootstrap_test.php';
\ArtSkills\Lib\Env::setFixtureFolder(TEST_FIXTURE);
\ArtSkills\Lib\Env::setMockFolder(TESTS . 'Suite' . DS . 'Mock' . DS);
\ArtSkills\Lib\Env::setMockNamespace('App\Test\Suite\Mock');
