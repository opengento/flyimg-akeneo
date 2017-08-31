<?php

namespace Tests\Core\Entity\Image;

use Core\Entity\Image\InputImage;
use Core\Entity\Image\OutputImage;
use Core\Entity\OptionsBag;
use Tests\Core\BaseTest;

/**
 * @backupGlobals disabled
 */
class OutputImageTest extends BaseTest
{
    /**
     * Test parseOptions Method
     */
    public function testParseOptions()
    {
        $expectedParseArray = [
            'mozjpeg' => 1,
            'quality' => 90,
            'output' => 'auto',
            'unsharp' => '0.25x0.25+8+0.065',
            'width' => '200',
            'height' => '100',
            'face-crop' => 0,
            'face-crop-position' => 0,
            'face-blur' => '1',
            'crop' => '1',
            'background' => '#999999',
            'strip' => 1,
            'resize' => '1',
            'gravity' => 'Center',
            'filter' => 'Lanczos',
            'rotate' => '-45',
            'scale' => '50',
            'sampling-factor' => '1x1',
            'refresh' => '1',
            'extent' => '100x80',
            'preserve-aspect-ratio' => 1,
            'preserve-natural-size' => 1,
            'webp-lossless' => 0,
            'gif-frame' => 0,
            'extract' => null,
            'extract-top-x' => null,
            'extract-top-y' => null,
            'extract-bottom-x' => null,
            'extract-bottom-y' => null,
            'thread' => 1,
        ];
        $optionsBag = new OptionsBag($this->ImageHandler->getAppParameters(), self::OPTION_URL);
        $inputImage = new InputImage($optionsBag, self::JPG_TEST_IMAGE);

        $this->assertEquals($inputImage->getOptionsBag()->asArray(), $expectedParseArray);
    }

    /**
     * Test SaveToTemporaryFile
     */
    public function testSaveToTemporaryFile()
    {
        $optionsBag = new OptionsBag($this->ImageHandler->getAppParameters(), self::OPTION_URL);
        $inputImage = new InputImage($optionsBag, self::JPG_TEST_IMAGE);
        $image = new OutputImage($inputImage);
        $this->generatedImage[] = $image;

        $this->assertFileExists($image->getInputImage()->getSourceImagePath());
    }

    /**
     * Test GenerateFilesName
     */
    public function testGenerateFilesName()
    {
        $optionsBag = new OptionsBag($this->ImageHandler->getAppParameters(), self::OPTION_URL);
        $inputImage = new InputImage($optionsBag, self::JPG_TEST_IMAGE);
        $image = new OutputImage($inputImage);

        $optionsBag2 = new OptionsBag($this->ImageHandler->getAppParameters(), self::OPTION_URL);
        $inputImage2 = new InputImage($optionsBag2, self::JPG_TEST_IMAGE);
        $image2 = new OutputImage($inputImage2);

        $this->generatedImage[] = $image2;
        $this->generatedImage[] = $image;

        $this->assertEquals($image2->getOutputImageName(), $image->getOutputImageName());
        $this->assertNotEquals($image2->getOutputImagePath(), $image->getOutputImagePath());
    }

    /**
     * Test ExtractByKey
     */
    public function testExtractByKey()
    {
        $optionsBag = new OptionsBag($this->ImageHandler->getAppParameters(), self::OPTION_URL);
        $inputImage = new InputImage($optionsBag, self::JPG_TEST_IMAGE);
        $image = new OutputImage($inputImage);

        $image->extract('width');
        $this->generatedImage[] = $image;
        $this->assertFalse(array_key_exists('width', $image->getInputImage()->getOptionsBag()->asArray()));
    }
}
