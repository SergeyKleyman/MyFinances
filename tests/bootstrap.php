<?php

declare(strict_types=1);

// Report all PHP errors
error_reporting(E_ALL);

// Ensure that composer has installed all dependencies
if (!file_exists(dirname(__DIR__) . '/composer.lock')) {
    die("Dependencies must be installed using composer\n");
}

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/dummyFuncForTestsWithNamespace.php';
require __DIR__ . '/dummyFuncForTestsWithoutNamespace.php';

/*
Dummy comment to verify PHP source code max allowed line length (which is 200).
PHP source code max allowed line length is configured in <repo root>/phpcs.xml.dist

1--------10--------20--------30--------40--------50--------60--------70--------80--------90--------100-------110-------120-------130-------140-------150-------160-------170-------180-------190------->
|--------|---------|---------|---------|---------|---------|---------|---------|---------|---------|---------|---------|---------|---------|---------|---------|---------|---------|---------|---------|
*/
