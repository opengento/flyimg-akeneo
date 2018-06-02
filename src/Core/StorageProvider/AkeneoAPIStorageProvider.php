<?php

namespace Core\StorageProvider;

use Akeneo\Pim\ApiClient\AkeneoPimClient;
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
class AkeneoAPIStorageProvider implements ServiceProviderInterface
{
    /**
     * @param Container $app
     *
     * @throws MissingParamsException
     */
    public function register(Container $app)
    {
        $akeneoParams = $app['params']->parameterByKey('akeneo_api');
        if (in_array("", $akeneoParams)) {
            throw new MissingParamsException("One of Akeneo API's parameters in empty ! ");
        }

        $this->registerAkeneoAPIServiceProvider($app, $akeneoParams);
        $app['flysystems']['file_path_resolver'] = function () use ($akeneoParams) {
            return sprintf('%s/', $akeneoParams['base_url']) . '%s';
        };
    }

    /**
     * @param Container $app
     * @param array     $params
     *
     * @return Container
     */
    protected function registerAkeneoAPIServiceProvider(Container $app, array $params): Container
    {
        $clientBuilder = new AkeneoPimClientBuilder(
            $params['api_url']
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
                        'adapter' => AkeneoApiAdapter::class,
                        'args' => [$client],
                    ],
                ],
            ]
        );

        return $app;
    }
}
