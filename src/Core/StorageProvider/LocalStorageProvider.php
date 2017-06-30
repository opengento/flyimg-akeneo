<?php

namespace Core\StorageProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use WyriHaximus\SliFly\FlysystemServiceProvider;

/**
 * Storage class to manage Storage provider from FlySystem
 *
 * Class StorageProvider
 * @package Core\Provider
 */
class LocalStorageProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $app Container
     */
    public function register(Container $app)
    {
        $app->register(
            new FlysystemServiceProvider(),
            [
                'flysystem.filesystems' => [
                    'upload_dir' => [
                        'adapter' => 'League\Flysystem\Adapter\Local',
                        'args' => [UPLOAD_DIR],
                    ],
                ],
            ]
        );

        $app['flysystems']['file_path_resolver'] = function () use ($app) {
            $hostname = getenv('HOSTNAME_URL');
            if (empty($hostname)) {
                $schema = $app['request_context']->getScheme();
                $host = $app['request_context']->getHost();
                $port = $app['request_context']->getHttpPort();
                $hostname = $schema.'://'.$host.($port == '80' ? '' : ':'.$port);
            }

            return $hostname.'/'.UPLOAD_WEB_DIR.'%s';
        };
    }
}
