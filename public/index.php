<?php

//$cmd='/opt/mozjpeg/bin/cjpeg -version  2>&1';
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app->get('/', function() use($app) {
    return 'Hello from '.$app->escape('Docker!');
});

$app->run();