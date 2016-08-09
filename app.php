<?php

use Core\Resolver\ControllerResolver;
use Core\Service\ImageManager;
use Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

$loader = require_once __DIR__ . '/vendor/autoload.php';

$app = new Silex\Application();

/**
 * Define Constants && Load parameters files
 */
define('ROOT_DIR', __DIR__);
define('UPLOAD_DIR', ROOT_DIR . '/var/upload/');
define('TMP_DIR', ROOT_DIR . '/var/tmp/');
define('LOG_DIR', ROOT_DIR . '/var/log/');

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
if (!is_dir(TMP_DIR)) {
    mkdir(TMP_DIR, 0777, true);
}
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0777, true);
}

$app['params'] = Yaml::parse(file_get_contents(ROOT_DIR . '/config/parameters.yml'));

/**
 * Routes
 */
$app['routes'] = $app->extend('routes', function (RouteCollection $routes) {
    $loader = new YamlFileLoader(new FileLocator(__DIR__ . '/config'));
    $collection = $loader->load('routes.yml');
    $routes->addCollection($collection);
    return $routes;
});


/**
 * Register Fly System Provider
 */
$app->register(new WyriHaximus\SliFly\FlysystemServiceProvider(), [
    'flysystem.filesystems' => [
        'upload_dir' => [
            'adapter' => 'League\Flysystem\Adapter\Local',
            'args' => [UPLOAD_DIR]
        ],
    ],
]);

/**
 * Monolog Service
 */
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.name' => 'fly-image',
    'monolog.level' => Logger::ERROR,
    'monolog.logfile' => LOG_DIR . 'dev.log',
));

$app['resolver'] = $app->share(function () use ($app) {
    return new ControllerResolver($app, $app['logger']);
});

$app['image.manager'] = $app->share(function ($app) {
    return new ImageManager($app['params'], $app['flysystems']['upload_dir']);
});

/** debug conf */
$app['debug'] = $app['params']['debug'];

return $app;
