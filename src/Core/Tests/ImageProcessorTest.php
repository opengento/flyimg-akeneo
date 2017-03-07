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
        $this->image = $this->coreManager->processImage(parent::OPTION_URL.',o_png', parent::PNG_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::PNG_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessWebpFromPng()
    {
        $this->image = $this->coreManager->processImage(parent::OPTION_URL.',o_webp', parent::PNG_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::WEBP_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessJpgFromPng()
    {
        $this->image = $this->coreManager->processImage(parent::OPTION_URL.',o_jpg', parent::PNG_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::JPEG_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessGifFromPng()
    {
        $this->image = $this->coreManager->processImage(parent::OPTION_URL.',o_gif', parent::PNG_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::GIF_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessJpg()
    {
        $this->image = $this->coreManager->processImage(parent::OPTION_URL, parent::JPG_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
    }

    /**
     */
    public function testProcessGif()
    {
        $this->image = $this->coreManager->processImage(parent::GIF_OPTION_URL, parent::GIF_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::GIF_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessPngFromGif()
    {
        $this->image = $this->coreManager->processImage(parent::GIF_OPTION_URL.',o_png', parent::GIF_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::PNG_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessJpgFromGif()
    {
        $this->image = $this->coreManager->processImage(parent::GIF_OPTION_URL.',o_jpg', parent::GIF_TEST_IMAGE);
        $this->assertFileExists($this->image->getNewFilePath());
        $this->assertEquals(Image::JPEG_MIME_TYPE, $this->getFileMemeType($this->image->getNewFilePath()));
    }

    /**
     */
    public function testProcessWebpFromGif()
    {
        $this->image = $this->coreManager->processImage(parent::GIF_OPTION_URL.',o_webp', parent::GIF_TEST_IMAGE);
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
