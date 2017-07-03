<?php

namespace Tests\Core\StorageProvider;

use Aws\S3\Exception\S3Exception;
use Core\Exception\MissingParamsException;
use Core\Handler\ImageHandler;
use Core\StorageProvider\S3StorageProvider;
use Tests\Core\BaseTest;

class S3StorageProviderTest extends BaseTest
{
    /**
     *
     */
    public function testUploadActionWithS3StorageS3Exception()
    {
        $this->expectException(S3Exception::class);

        unset($this->app['flysystems']);
        unset($this->app['image.handler']);
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
        /** Core Manager Service */
        $this->ImageHandler =
            new ImageHandler(
                $this->app['image.processor'],
                $this->app['facedetection.processor'],
                $this->app['flysystems']['upload_dir'],
                $this->app['params']
            );
        $this->ImageHandler->processImage(parent::OPTION_URL.',o_webp', parent::PNG_TEST_IMAGE);
    }

    /**
     *
     */
    public function testUploadActionWithS3StorageException()
    {
        $this->expectException(MissingParamsException::class);
        $awsS3 = [
            'aws_s3' => [
                'access_id' => 'xxxxx',
                'secret_key' => '',
                'region' => '',
                'bucket_name' => '',
            ],
        ];
        $this->app['params'] = array_merge($this->app['params'], $awsS3);
        $this->app->register(new S3StorageProvider());
    }
}
