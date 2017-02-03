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
        $this->image = new Image(parent::OPTION_URL . ',o_png', parent::PNG_TEST_IMAGE, $this->app['params']);
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $processor->process($this->image);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals('image/png', $this->getFileMemeType($this->image->getNewFilePath()));
    }
    /**
     */
    public function testProcessWebP()
    {
        $this->image = new Image(parent::OPTION_URL . ',o_webp', parent::PNG_TEST_IMAGE, $this->app['params']);
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $processor->process($this->image);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals('image/webp', $this->getFileMemeType($this->image->getNewFilePath()));
    }
    /**
     */
    public function testProcessJpgFromPng()
    {
        $this->image = new Image(parent::OPTION_URL . ',o_jpg', parent::PNG_TEST_IMAGE, $this->app['params']);
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $processor->process($this->image);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals('image/jpeg', $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessJpg()
    {
        $this->image = new Image(parent::OPTION_URL, parent::JPG_TEST_IMAGE, $this->app['params']);
        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $processor->process($this->image);
        $this->assertFileExists($this->image->getNewFilePath());
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
