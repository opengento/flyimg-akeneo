<?php

namespace Tests\Core\Processor;

use Core\Processor\ImageProcessor;
use Core\Entity\Image\OutputImage;
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
        $imageDimensions = $this->getImageInfo($image->getOutputImagePath())['dimensions'];
        $this->assertEquals($expectedSize, $imageDimensions);
    }

    public function shrinkProvider()
    {
        return [
        /* url option, out size, source image */
            ['w_300', '300x300', self::PNG_TEST_SQUARE_IMAGE],
            ['w_300', '300x200', self::PNG_TEST_LANDSCAPE_IMAGE],
            ['w_300', '300x451', self::PNG_TEST_PORTRAIT_IMAGE],
            ['h_300', '300x300', self::PNG_TEST_SQUARE_IMAGE],
            ['h_300', '450x300', self::PNG_TEST_LANDSCAPE_IMAGE],
            ['h_300', '200x300', self::PNG_TEST_PORTRAIT_IMAGE],
            ['w_300,h_150', '150x150', self::PNG_TEST_SQUARE_IMAGE],
            ['w_300,h_150', '225x150', self::PNG_TEST_LANDSCAPE_IMAGE],
            ['w_300,h_150', '100x150', self::PNG_TEST_PORTRAIT_IMAGE],
            ['w_150,h_300', '150x150', self::PNG_TEST_SQUARE_IMAGE],
            ['w_150,h_300', '150x100', self::PNG_TEST_LANDSCAPE_IMAGE],
            ['w_150,h_300', '150x225', self::PNG_TEST_PORTRAIT_IMAGE],
        ];
    }

    /**
     *
     * @param  string $filePath
     * @return string
     */
    protected function getImageInfo($filePath)
    {
        $imageInfoResponse = $this->imageProcessor->execute(ImageProcessor::IM_IDENTITY_COMMAND." ".$filePath);
        $imageDetails = $this->parseImageInfoResponse($imageInfoResponse);
        return $imageDetails;
    }

    /**
     * Parses the default output of imagemagik identify command
     * @param  array $output the STDOUT from executing an identify command
     * @return array         associative array with the info in there
     */
    protected function parseImageInfoResponse($output): array
    {
        if (!is_array($output) || empty($output)) {
            throw new Exception("Image identify failed", 1);
            return [];
        }

        $output = explode(' ', $output[0]);
        return [
            'filePath'     => $output[0],
            'format'       => $output[1],
            'dimensions'   => $output[2],
            'canvas'       => $output[3],
            'colorDepth'   => $output[4],
            'colorProfile' => $output[5],
            'weight'       => $output[6],
        ];
    }
}
