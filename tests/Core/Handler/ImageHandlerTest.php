<?php

namespace Tests\Core\Service;

use Core\Entity\Image;
use Imagick;
use Tests\Core\BaseTest;

class ImageHandlerTest extends BaseTest
{
    /**
     */
    public function testProcessPNG()
    {
        $image = $this->ImageHandler->processImage(parent::CROP_OPTION_URL.',o_png', parent::PNG_TEST_IMAGE);
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
    public function testProcessFaceCropping()
    {
        $image = $this->ImageHandler->processImage('fc_1,rf_1', parent::FACES_TEST_IMAGE);
        $image1 = new \Imagick($image->getNewFilePath());
        $image2 = new \Imagick(parent::FACES_CP0_TEST_IMAGE);
        $result = $image1->compareImages($image2, \Imagick::METRIC_MEANSQUAREERROR);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getNewFilePath());
        $this->assertEquals(0, $result[1]);
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
     * @param $filePath
     *
     * @return mixed
     */
    protected function getFileMemeType($filePath)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
    }
}
