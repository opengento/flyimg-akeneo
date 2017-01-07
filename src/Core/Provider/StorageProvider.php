<?php

namespace Core\Provider;


use Aws\S3\S3Client;
use WyriHaximus\SliFly\FlysystemServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Storage class to manage Storage provider from FlySystem
 *
 * Class StorageProvider
 * @package Core\Provider
 */
class StorageProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        switch ($app['params']['storage_system']) {
            case 's3':
                $this->registerStorageSystemS3($app);
                break;
            case 'local':
            default:
                $this->registerStorageSystemLocal($app);
                break;
        }
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        $app['params'];
    }

    /**
     * @param Application $app
     */
    protected function registerStorageSystemLocal(Application $app)
    {
        $app->register(new FlysystemServiceProvider(), [
            'flysystem.filesystems' => [
                'upload_dir' => [
                    'adapter' => 'League\Flysystem\Adapter\Local',
                    'args' => [UPLOAD_DIR]
                ],
            ],
        ]);
    }

    /**
     * @param Application $app
     * @throws \Exception
     */
    protected function registerStorageSystemS3(Application $app)
    {
        $s3Params = $app['params']['aws_s3'];
        if (in_array("", $s3Params)) {
            throw new \Exception("One of AWS S3 parameters in empty ! ");
        }
        $s3Client = new S3Client([
            'credentials' => [
                'key' => $s3Params['access_id'],
                'secret' => $s3Params['secret_key'],
            ],
            'region' => $s3Params['region'],
            'version' => 'latest',
        ]);

        $app->register(new FlysystemServiceProvider(), [
            'flysystem.filesystems' => [
                'upload_dir' => [
                    'adapter' => 'League\Flysystem\AwsS3v3\AwsS3Adapter',
                    'args' => [
                        $s3Client,
                        $s3Params['bucket_name']
                    ]
                ]
            ]
        ]);
    }
}

