<?php

use Core\Resolver\ControllerResolver;
use Core\Service\Resizer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

//$cmd='/opt/mozjpeg/bin/cjpeg -version  2>&1';
$loader = require_once __DIR__ . '/vendor/autoload.php';

$app = new Silex\Application();

/**
 * Load parameters files
 */
$app['params'] = Yaml::parse(file_get_contents(__DIR__ . '/config/parameters.yml'));


/**
 * Routes
 */
$app['routes'] = $app->extend('routes', function (RouteCollection $routes) {
    $loader = new YamlFileLoader(new FileLocator(__DIR__ . '/config'));
    $collection = $loader->load('routes.yml');
    $routes->addCollection($collection);
    return $routes;
});

$app['resolver'] = $app->share(function () use ($app) {
    return new ControllerResolver($app, $app['logger']);
});

$app['image.resizer'] = $app->share(function ($app) {
    return new Resizer($app['params']);
});

return $app;