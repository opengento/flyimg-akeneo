<?php

namespace Tests\Core\Controller;

use Core\StorageProvider\S3StorageProvider;
use ReflectionClass;
use Silex\WebTestCase;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tests\Core\BaseTest;

class DefaultControllerTest extends WebTestCase
{
    /**
     *
     */
    public function testIndexAction()
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isOk());
    }

    /**
     *
     */
    public function testUploadAction()
    {
        $client = static::createClient();
        $client->request('GET', '/upload/w_200,h_200,c_1,rf_1,o_png/'.BaseTest::JPG_TEST_IMAGE);
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertFalse($client->getResponse()->isEmpty());
    }

    /**
     *
     */
    public function testUploadActionWithFaceDetection()
    {
        $client = static::createClient();
        $client->request('GET', '/upload/fc_1/'.BaseTest::FACES_TEST_IMAGE);
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertFalse($client->getResponse()->isEmpty());
    }

    /**
     *
     */
    public function testUploadActionWithRefreshOption()
    {
        $client = static::createClient();
        $client->request('GET', '/upload/w_200,h_200,o_gif,rf_1/'.BaseTest::JPG_TEST_IMAGE);
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertFalse($client->getResponse()->isEmpty());
    }

    /**
     *
     */
    public function testUploadActionForbidden()
    {
        $client = static::createClient();
        $client->request('GET', '/upload/w_200,h_200,c_1/Rovinj-Croatia-nonExist.jpg');
        $this->assertTrue($client->getResponse()->isForbidden());
    }

    /**
     *
     */
    public function testUploadActionInvalidExtension()
    {
        $client = static::createClient();
        $client->request('GET', '/upload/w_200,h_200,c_1,o_xxx/Rovinj-Croatia-nonExist.jpg');
        $this->assertTrue($client->getResponse()->isForbidden());
    }

    /**
     *
     */
    public function testPathAction()
    {
        $client = static::createClient();
        $client->request('GET', '/path/w_200,h_200,c_1/'.BaseTest::JPG_TEST_IMAGE);
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertFalse($client->getResponse()->isEmpty());
    }

    /**
     *
     */
    public function testPathActionForbidden()
    {
        $client = static::createClient();
        $client->request('GET', '/path/w_200,h_200,c_1/Rovinj-Croatia-nonExist.jpg');
        $this->assertTrue($client->getResponse()->isForbidden());
    }

    /**
     *
     */
    public function testUploadActionWithRestrictedDomains()
    {
        $client = static::createClient();
        $class = new ReflectionClass ($this->app['image.handler']);
        $property = $class->getProperty('defaultParams');
        $property->setAccessible(true);
        $defaultParams = $this->app['image.handler']->getDefaultParams();
        $defaultParams['restricted_domains'] = true;
        $property->setValue($this->app['image.handler'], $defaultParams);
        $client->request('GET', '/path/w_200,h_200,c_1/Rovinj-Croatia-nonExist.jpg');
        $this->assertTrue($client->getResponse()->isForbidden());
    }

    /**
     *
     */
    public function testUploadActionWithS3Storage()
    {
        $client = static::createClient();
        unset($this->app['flysystems']);
        $awsS3 = [
            'aws_s3' => [
                'access_id' => 'xxxxx',
                'secret_key' => 'xxxxx',
                'region' => 'xxxxx',
                'bucket_name' => 'xxxxx',
            ],
        ];
        $this->app['params'] = array_merge($this->app['params'], $awsS3);
        $this->app->register(new S3StorageProvider());
        $client->request('GET', '/path/w_200,h_200,c_1/Rovinj-Croatia-nonExist.jpg');
        $this->assertTrue($client->getResponse()->isForbidden());
    }

    /**
     * Creates the application.
     *
     * @return HttpKernelInterface
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../../app.php';
        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }
}
