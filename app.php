<?php

declare(strict_types = 1);

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

$loader = require_once __DIR__.'/vendor/autoload.php';

$app = new Silex\Application();

/**
 * Define Constants && Load parameters files
 */
define('UPLOAD_WEB_DIR', 'uploads/');
define('UPLOAD_DIR', __DIR__.'/web/'.UPLOAD_WEB_DIR);
define('TMP_DIR', __DIR__.'/var/tmp/');
define('ROOT_DIR', __DIR__);

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
if (!is_dir(TMP_DIR)) {
    mkdir(TMP_DIR, 0777, true);
}

$app['params'] = Yaml::parse(file_get_contents(__DIR__.'/config/parameters.yml'));

/**
 * Routes
 */
$app['routes'] = $app->extend(
    'routes',
    function (RouteCollection $routes) {
        $loader = new YamlFileLoader(new FileLocator(__DIR__.'/config'));
        $collection = $loader->load('routes.yml');
        $routes->addCollection($collection);

        return $routes;
    }
);

/** Register Storage provider */

switch ($app['params']['storage_system']) {
    case 's3':
        $app->register(new \Core\StorageProvider\S3StorageProvider());
        break;
    case 'local':
    default:
        $app->register(new \Core\StorageProvider\LocalStorageProvider());
        break;
}

/** Monolog Service */
$app->register(
    new Silex\Provider\MonologServiceProvider(),
    array(
        'monolog.name' => 'flyimg',
        'monolog.level' => \Monolog\Logger::ERROR,
        'monolog.logfile' => 'php://stderr',
    )
);

/** Controller Resolver */
/**
 * @param \Silex\Application $app
 *
 * @return \Core\Resolver\ControllerResolver
 */
$app['resolver'] = function (\Silex\Application $app) {
    return new \Core\Resolver\ControllerResolver($app, $app['logger']);
};

/** Image processor Service */
$app['image.processor'] = function () {
    return new \Core\Processor\ImageProcessor();
};
/** facedetection processor Service */
$app['facedetection.processor'] = function () {
    return new \Core\Processor\FaceDetectProcessor();
};

/** Core Manager Service */
$app['image.handler'] = function (\Silex\Application $app) {
    return new \Core\Handler\ImageHandler(
        $app['image.processor'],
        $app['facedetection.processor'],
        $app['flysystems']['upload_dir'],
        $app['params']
    );
};

/** debug conf */
$app['debug'] = $app['params']['debug'];
return $app;
