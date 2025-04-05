<?php
namespace Test;

use GCWorld\Routing\LoadRoutes;

require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$testDir = __DIR__.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR;

$files = glob($testDir.'*.php');
foreach($files as $file) {
    include_once $file;
}

$cLoader = LoadRoutes::getInstance();
$cLoader->setLint(false);
$cLoader->addPath($testDir);
$cLoader->generateRoutes(true, true);
