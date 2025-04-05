<?php
namespace Test;

use GCWorld\Routing\LoadRoutes;

require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$testDir = __DIR__.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR;

$files = glob($testDir.'*.php');
foreach($files as $file) {
    include_once $file;
}

$timeAnno = 0;
$timeAttr = 0;


$start = microtime(true);
$cLoader = LoadRoutes::getInstance();
$cLoader->setLint(false);
$cLoader->addPath($testDir . 'TestAnnotations.php');
$cLoader->generateRoutes(true, false);
$timeAnno += microtime(true) - $start;

$start = microtime(true);
$cLoader = LoadRoutes::getInstance();
$cLoader->setLint(false);
$cLoader->addPath($testDir . 'TestAttributes.php');
$cLoader->generateRoutes(true, false);
$timeAttr += microtime(true) - $start;


echo 'Anno: ',$timeAnno,PHP_EOL;
echo 'Attr: ',$timeAttr,PHP_EOL;

