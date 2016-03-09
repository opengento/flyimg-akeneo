<?php

use Core\Resolver\ControllerResolver;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;


//$cmd='/opt/mozjpeg/bin/cjpeg -version  2>&1';
$loader = require_once __DIR__ . '/vendor/autoload.php';
$loader->add('Core', __DIR__ . '/src');

$app = new Silex\Application();

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

return $app;