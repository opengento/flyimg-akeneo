<?php

namespace Tests\Core\Processor;

use Core\Processor\ImageProcessor;
use Core\Entity\Image\OutputImage;
use Core\Entity\Image\ImageMetaInfo;
use Tests\Core\BaseTest;

/**
 * Class ImageProcessorTest
 * @package Tests\Core\Processor
 */
class ImageProcessorTest extends BaseTest
{
    const PNG_TEST_SQUARE_IMAGE = __DIR__.'/../../testImages/square-opaque-600.png';
    const PNG_TEST_LANDSCAPE_IMAGE = __DIR__.'/../../testImages/landscape-color-squares-900x600.png';
    const PNG_TEST_PORTRAIT_IMAGE = __DIR__.'/../../testImages/portrait-color-squares-600x900.png';
    const PNG_TEST_SMALL_SQUARE_IMAGE = __DIR__.'/../../testImages/square-opaque-200.png';
    const PNG_TEST_SMALL_LANDSCAPE_IMAGE = __DIR__.'/../../testImages/landscape-color-squares-300x200.png';
    const PNG_TEST_SMALL_PORTRAIT_IMAGE = __DIR__.'/../../testImages/portrait-color-squares-200x300.png';
    const OUTPUT_EXTENSIONS = ['png', 'jpg', 'webp', 'gif'];

    protected $imageProcessor;

    /**
     */
    public function setUp()
    {
        parent::setUp();
        $this->imageProcessor = $this->ImageHandler->getImageProcessor();
    }

    /**
     * @dataProvider shrinkProvider
     */
    public function testShrinkSuccess(string $options, string $expectedSize, string $sourceImage)
    {
        $image = $this->ImageHandler->processImage($options . ',o_png', $sourceImage);
        $this->generatedImage[] = $image;
        $this->assertFileExists($image->getOutputImagePath());
        $imageDimensions = $this->getImageInfo($image->getOutputImagePath())[ImageMetaInfo::IMAGE_PROP_DIMENSIONS];
        $this->assertEquals($expectedSize, $imageDimensions);
    }

    public function shrinkProvider(): array
    {
        $resizingTests = [
        /*   Test name                      url option, out size, source image */
            'Resize to width square' =>
            ['w_300', '300x300', self::PNG_TEST_SQUARE_IMAGE],
            'Resize to width landscape' =>
            ['w_300', '300x200', self::PNG_TEST_LANDSCAPE_IMAGE],
            'Resize to width portrait' =>
            ['w_300', '300x451', self::PNG_TEST_PORTRAIT_IMAGE],
            'Resize to height square' =>
            ['h_300', '300x300', self::PNG_TEST_SQUARE_IMAGE],
            'Resize to height landscape' =>
            ['h_300', '450x300', self::PNG_TEST_LANDSCAPE_IMAGE],
            'Resize to height portrait' =>
            ['h_300', '200x300', self::PNG_TEST_PORTRAIT_IMAGE],
            'Resize to width and height (landscape) square' =>
            ['w_300,h_150', '150x150', self::PNG_TEST_SQUARE_IMAGE],
            'Resize to width and height (landscape) landscape' =>
            ['w_300,h_150', '225x150', self::PNG_TEST_LANDSCAPE_IMAGE],
            'Resize to width and height (landscape) portrait' =>
            ['w_300,h_150', '100x150', self::PNG_TEST_PORTRAIT_IMAGE],
            'Resize to width and height (portrait) square' =>
            ['w_150,h_300', '150x150', self::PNG_TEST_SQUARE_IMAGE],
            'Resize to width and height (portrait) landscape' =>
            ['w_150,h_300', '150x100', self::PNG_TEST_LANDSCAPE_IMAGE],
            'Resize to width and height (portrait) portrait' =>
            ['w_150,h_300', '150x225', self::PNG_TEST_PORTRAIT_IMAGE],
            'Resize and Crop to square a square' =>
            ['w_300,h_300,c_1', '300x300', self::PNG_TEST_SQUARE_IMAGE],
            'Resize and Crop to square a landscape' =>
            ['w_300,h_300,c_1', '300x300', self::PNG_TEST_LANDSCAPE_IMAGE],
            'Resize and Crop to square a portrait' =>
            ['w_300,h_300,c_1', '300x300', self::PNG_TEST_PORTRAIT_IMAGE],
            'Resize and Crop to portrait (wider than portrait) square' =>
            ['w_250,h_300,c_1', '250x300', self::PNG_TEST_SQUARE_IMAGE],
            'Resize and Crop to portrait (wider than portrait) landscape' =>
            ['w_250,h_300,c_1', '250x300', self::PNG_TEST_LANDSCAPE_IMAGE],
            'Resize and Crop to portrait (wider than portrait) portrait' =>
            ['w_250,h_300,c_1', '250x300', self::PNG_TEST_PORTRAIT_IMAGE],
            'Resize and Crop to portrait (narrower than portrait) square' =>
            ['w_150,h_300,c_1', '150x300', self::PNG_TEST_SQUARE_IMAGE],
            'Resize and Crop to portrait (narrower than portrait) landscape' =>
            ['w_150,h_300,c_1', '150x300', self::PNG_TEST_LANDSCAPE_IMAGE],
            'Resize and Crop to portrait (narrower than portrait) portrait' =>
            ['w_150,h_300,c_1', '150x300', self::PNG_TEST_PORTRAIT_IMAGE],
            'Resize and Crop to landscape (taller than landscape) square' =>
            ['w_300,h_250,c_1', '300x250', self::PNG_TEST_SQUARE_IMAGE],
            'Resize and Crop to landscape (taller than landscape) landscape' =>
            ['w_300,h_250,c_1', '300x250', self::PNG_TEST_LANDSCAPE_IMAGE],
            'Resize and Crop to landscape (taller than landscape) portrait' =>
            ['w_300,h_250,c_1', '300x250', self::PNG_TEST_PORTRAIT_IMAGE],
            'Resize and Crop to landscape (shorter than landscape) square' =>
            ['w_300,h_150,c_1', '300x150', self::PNG_TEST_SQUARE_IMAGE],
            'Resize and Crop to landscape (shorter than landscape) landscape' =>
            ['w_300,h_150,c_1', '300x150', self::PNG_TEST_LANDSCAPE_IMAGE],
            'Resize and Crop to landscape (shorter than landscape) portrait' =>
            ['w_300,h_150,c_1', '300x150', self::PNG_TEST_PORTRAIT_IMAGE],
        ];

        $tests = [];

        foreach ($resizingTests as $key => $test) {
            foreach (self::OUTPUT_EXTENSIONS as $extension) {
                $test[0] = $test[0].',o_'.$extension;
                $tests[$key . ' with ' . $extension] = $test;
            }
        }

        return $tests;
    }

    /**
     * Returns an associative array with the info of an image in a given path.
     * @param  string $filePath
     * @return array
     */
    protected function getImageInfo($filePath): array
    {
        $imgInfo = new ImageMetaInfo($filePath);
        return $imgInfo->getInfo();
    }
}
