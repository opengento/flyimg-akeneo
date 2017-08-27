<?php

namespace Tests\Core\Processor;

//use Core\Handler\ImageHandler;
use Core\Entity\OutputImage;
use Tests\Core\BaseTest;

/**
 * Class ImageProcessorTest
 * @package Tests\Core\Processor
 */
class ImageProcessorTest extends \PHPUnit_Framework_TestCase
{
    const PNG_TEST_SQUARE_IMAGE = __DIR__.'/../testImages/square-opaque-600.png';
    const PNG_TEST_LANDSCAPE_IMAGE = __DIR__.'/../testImages/landscape-color-squares-900x600.png';
    const PNG_TEST_PORTRAIT_IMAGE = __DIR__.'/../testImages/portrait-color-squares-600x900.png';

    /**
     */
    public function testSquareShrinkSuccess()
    {
        $image = $this->ImageHandler->processImage('w_300,o_png', self::PNG_TEST_SQUARE_IMAGE);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getOutputImagePath());
        //$this->assertEquals(OutputImage::WEBP_MIME_TYPE, $this->getFileMimeType($image->getOutputImagePath()));
    }
}
