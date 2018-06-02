<?php

namespace Core\StorageProvider;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Aws\S3\S3Client;
use Core\Exception\MissingParamsException;
use Core\StorageProvider\AkeneoApi\AkeneoApiAdapter;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use WyriHaximus\SliFly\FlysystemServiceProvider;

/**
 * Storage class to manage Storage provider from FlySystem
 *
 * Class StorageProvider
 * @package Core\Provider
 */
class S3StorageProvider implements ServiceProviderInterface
{
    /**
     * @param Container $app
     *
     * @throws MissingParamsException
     */
    public function register(Container $app)
    {
        $s3Params = $app['params']->parameterByKey('aws_s3');
        if (in_array("", $s3Params)) {
            throw new MissingParamsException("One of AWS S3 parameters in empty ! ");
        }

        $this->registerS3ServiceProvider($app, $s3Params);
        $app['flysystems']['file_path_resolver'] = function () use ($s3Params) {
            return sprintf('https://s3.%s.amazonaws.com/%s/', $s3Params['region'], $s3Params['bucket_name']).'%s';
        };
    }

    /**
     * @param Container $app
     * @param array     $s3Params
     *
     * @return Container
     */
    protected function registerS3ServiceProvider(Container $app, array $s3Params): Container
    {
        $s3Client = new S3Client(
            [
                'credentials' => ['key' => $s3Params['access_id'], 'secret' => $s3Params['secret_key']],
                'region' => $s3Params['region'],
                'version' => 'latest',
            ]
        );

        $params = $app['params']->parameterByKey('akeneo_api');
        if (in_array("", $params)) {
            throw new MissingParamsException("One of Akeneo API's parameters in empty ! ");
        }

        $clientBuilder = new AkeneoPimClientBuilder(
            $params['base_url']
        );

        $client = $clientBuilder->buildAuthenticatedByPassword(
            $params['client_id'],
            $params['client_secret'],
            $params['user'],
            $params['password']
        );

        $app->register(
            new FlysystemServiceProvider(),
            [
                'flysystem.filesystems' => [
                    'upload_dir' => [
                        'adapter' => 'League\Flysystem\AwsS3v3\AwsS3Adapter',
                        'args' => [$s3Client, $s3Params['bucket_name']],
                    ],
                ],
                'akeneo' => [
                    'adapter' => AkeneoApiAdapter::class,
                    'args' => [$client],
                ],
            ]
        );

        return $app;
    }
}
