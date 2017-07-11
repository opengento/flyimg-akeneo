<?php

declare(strict_types = 1);
require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\Routing\RouteCollection;

$ExceptionHandler = function (\Exception $e) use ($app) {
    $out = fopen('php://stderr', 'w');
    fputs($out, $e->getMessage());
    fclose($out);
};
$exceptionHandler = ExceptionHandler::register(false);
$exceptionHandler->setHandler($ExceptionHandler);

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

/**
 * Routes
 */
$routesResolver = new \Core\Resolver\RoutesResolver();
$app['routes'] = $app->extend(
    'routes',
    function (RouteCollection $routes) use ($routesResolver) {
        return $routesResolver->parseRoutesFromYamlFile($routes, __DIR__.'/config/routes.yml');
    }
);

/**
 * Application parameters
 */
$app['params'] = yaml_parse(file_get_contents(__DIR__.'/config/parameters.yml'));

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

/**
 * Controller Resolver
 *
 * @param \Silex\Application $app
 *
 * @return \Core\Resolver\ControllerResolver
 */
$app['resolver'] = function (\Silex\Application $app) {
    return new \Core\Resolver\ControllerResolver($app, $app['logger']);
};

/** ImageProcessor Service */
$app['image.processor'] = function () {
    return new \Core\Processor\ImageProcessor();
};
/** FaceDetectProcessor Service */
$app['facedetection.processor'] = function () {
    return new \Core\Processor\FaceDetectProcessor();
};

/**
 * Core Manager Service
 *
 * @param \Silex\Application $app
 *
 * @return \Core\Handler\ImageHandler
 */
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
