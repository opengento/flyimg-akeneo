<?php

namespace Tests\Core\Entity;

use Core\Entity\Image;
use Core\Exception\ReadFileException;
use Tests\Core\BaseTest;

/**
 * @backupGlobals disabled
 */
class ImageTest extends BaseTest
{
    /**
     * Test parseOptions Method
     */
    public function testParseOptions()
    {
        $expectedParseArray = [
            'mozjpeg' => 1,
            'quality' => 90,
            'unsharp' => '0.25x0.25+8+0.065',
            'width' => 200,
            'height' => 100,
            'face-crop' => 0,
            'face-crop-position' => 0,
            'face-blur' => 1,
            'crop' => 1,
            'background' => '#999999',
            'strip' => 1,
            'resize' => 1,
            'gravity' => 'Center',
            'filter' => 'Lanczos',
            'rotate' => '-45',
            'scale' => '50',
            'sampling-factor' => '1x1',
            'refresh' => true,
            'extent' => '100x80',
            'preserve-aspect-ratio' => '1',
            'preserve-natural-size' => '1',
            'webp-lossless' => '0',
            'gif-frame' => '0',
            'thread' => '1',
        ];
        $parsedOptions = $this->coreManager->parse(self::OPTION_URL);
        $image = new Image($parsedOptions, self::JPG_TEST_IMAGE);
        $this->generatedImage[] = $image;

        $this->assertEquals($image->getOptions(), $expectedParseArray);
    }

    /**
     * Test SaveToTemporaryFile
     */
    public function testSaveToTemporaryFile()
    {
        $parsedOptions = $this->coreManager->parse(self::OPTION_URL);
        $image = new Image($parsedOptions, self::JPG_TEST_IMAGE);
        $this->generatedImage[] = $image;

        $this->assertFileExists($image->getOriginalFile());
    }

    /**
     * Test SaveToTemporaryFileException
     */
    public function testSaveToTemporaryFileException()
    {
        $this->expectException(ReadFileException::class);
        $image = new Image(['output' => 'jpg'], parent::JPG_TEST_IMAGE.'--fail');
        $this->generatedImage[] = $image;
    }

    /**
     * Test GenerateFilesName
     */
    public function testGenerateFilesName()
    {
        $image = new Image($this->coreManager->parse(parent::OPTION_URL), parent::JPG_TEST_IMAGE);
        $parsedOptions = $this->coreManager->parse(self::OPTION_URL);
        $image2 = new Image($parsedOptions, self::JPG_TEST_IMAGE);

        $this->generatedImage[] = $image2;
        $this->generatedImage[] = $image;

        $this->assertEquals($image2->getNewFileName(), $image->getNewFileName());
        $this->assertNotEquals($image2->getNewFilePath(), $image->getNewFilePath());
    }

    /**
     * Test ExtractByKey
     */
    public function testExtractByKey()
    {
        $parsedOptions = $this->coreManager->parse(self::OPTION_URL);
        $image = new Image($parsedOptions, self::JPG_TEST_IMAGE);
        $image->extract('width');
        $this->generatedImage[] = $image;
        $this->assertFalse(array_key_exists('width', $image->getOptions()));
    }
}
