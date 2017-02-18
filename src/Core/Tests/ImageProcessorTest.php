<?php

namespace Core\Tests;

use Core\Entity\Image;
use Core\Service\ImageProcessor;

class ImageProcessorTest extends BaseTest
{

    /**
     */
    public function testProcessPNG()
    {
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $this->image = $processor->process(parent::OPTION_URL.',o_png', parent::PNG_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::PNG_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessWebpFromPng()
    {
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $this->image = $processor->process(parent::OPTION_URL.',o_webp', parent::PNG_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::WEBP_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessJpgFromPng()
    {
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $this->image = $processor->process(parent::OPTION_URL.',o_jpg', parent::PNG_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::JPEG_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessGifFromPng()
    {
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $this->image = $processor->process(parent::OPTION_URL.',o_gif', parent::PNG_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::GIF_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessJpg()
    {
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $this->image = $processor->process(parent::OPTION_URL, parent::JPG_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
    }

    /**
     */
    public function testProcessGif()
    {
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $this->image = $processor->process(parent::GIF_OPTION_URL, parent::GIF_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::GIF_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessPngFromGif()
    {
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $this->image = $processor->process(parent::GIF_OPTION_URL.',o_png', parent::GIF_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::PNG_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessJpgFromGif()
    {
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $this->image = $processor->process(parent::GIF_OPTION_URL.',o_jpg', parent::GIF_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::JPEG_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessWebpFromGif()
    {
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $this->image = $processor->process(parent::GIF_OPTION_URL.',o_webp', parent::GIF_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::WEBP_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     * @param $filePath
     * @return mixed
     */
    protected function getFileMemeType($filePath)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
    }
}
