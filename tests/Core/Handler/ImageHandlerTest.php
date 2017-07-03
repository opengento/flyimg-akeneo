<?php

namespace Tests\Core\Service;

use Core\Entity\Image;
use Core\Exception\AppException;
use ReflectionClass;
use Tests\Core\BaseTest;

class ImageHandlerTest extends BaseTest
{
    /**
     */
    public function testProcessPNG()
    {
        $image = $this->ImageHandler->processImage(parent::CROP_OPTION_URL, parent::PNG_TEST_IMAGE);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getNewFilePath());
        $this->assertEquals(Image::PNG_MIME_TYPE, $this->getFileMemeType($image->getNewFilePath()));
    }

    /**
     */
    public function testProcessWebpFromPng()
    {
        $image = $this->ImageHandler->processImage(parent::OPTION_URL.',o_webp', parent::PNG_TEST_IMAGE);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getNewFilePath());
        $this->assertEquals(Image::WEBP_MIME_TYPE, $this->getFileMemeType($image->getNewFilePath()));
    }

    /**
     */
    public function testProcessJpgFromPng()
    {
        $image = $this->ImageHandler->processImage(parent::OPTION_URL.',o_jpg', parent::PNG_TEST_IMAGE);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getNewFilePath());
        $this->assertEquals(Image::JPEG_MIME_TYPE, $this->getFileMemeType($image->getNewFilePath()));
    }

    /**
     */
    public function testProcessGifFromPng()
    {
        $image = $this->ImageHandler->processImage(parent::OPTION_URL.',o_gif', parent::PNG_TEST_IMAGE);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getNewFilePath());
        $this->assertEquals(Image::GIF_MIME_TYPE, $this->getFileMemeType($image->getNewFilePath()));
    }

    /**
     */
    public function testProcessJpg()
    {
        $image = $this->ImageHandler->processImage(parent::OPTION_URL, parent::JPG_TEST_IMAGE);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getNewFilePath());
    }

    /**
     */
    public function testProcessGif()
    {
        $image = $this->ImageHandler->processImage(parent::GIF_OPTION_URL, parent::GIF_TEST_IMAGE);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getNewFilePath());
        $this->assertEquals(Image::GIF_MIME_TYPE, $this->getFileMemeType($image->getNewFilePath()));
    }

    /**
     */
    public function testProcessPngFromGif()
    {
        $image = $this->ImageHandler->processImage(parent::GIF_OPTION_URL.',o_png', parent::GIF_TEST_IMAGE);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getNewFilePath());
        $this->assertEquals(Image::PNG_MIME_TYPE, $this->getFileMemeType($image->getNewFilePath()));
    }

    /**
     */
    public function testProcessJpgFromGif()
    {
        $image = $this->ImageHandler->processImage(parent::GIF_OPTION_URL.',o_jpg', parent::GIF_TEST_IMAGE);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getNewFilePath());
        $this->assertEquals(Image::JPEG_MIME_TYPE, $this->getFileMemeType($image->getNewFilePath()));
    }

    /**
     */
    public function testProcessWebpFromGif()
    {
        $image = $this->ImageHandler->processImage(parent::GIF_OPTION_URL.',o_webp', parent::GIF_TEST_IMAGE);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getNewFilePath());
        $this->assertEquals(Image::WEBP_MIME_TYPE, $this->getFileMemeType($image->getNewFilePath()));
    }

    /**
     *
     */
    public function testRestrictedDomains()
    {
        $this->expectException(AppException::class);
        $class = new ReflectionClass($this->app['image.handler']);
        $property = $class->getProperty('defaultParams');
        $property->setAccessible(true);
        $defaultParams = $this->app['image.handler']->getDefaultParams();
        $defaultParams['restricted_domains'] = true;
        $property->setValue($this->app['image.handler'], $defaultParams);

        $image = $this->ImageHandler->processImage(parent::OPTION_URL.',o_webp', parent::PNG_TEST_IMAGE);
        $this->generatedImage[] = $image;
    }

    /**
     * @param $filePath
     *
     * @return mixed
     */
    protected function getFileMemeType($filePath)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
    }
}
