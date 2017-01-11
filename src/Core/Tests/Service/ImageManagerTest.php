<?php
namespace Core\Tests\Service;

use Core\Entity\Image;
use Silex\Application;

class ImageManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    protected $app = null;

    /**
     *
     */
    public function setUp()
    {
        $this->app = $this->createApplication();
    }

    /**
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../app.php';
        $app['debug'] = true;
        unset($app['exception_handler']);
        return $app;
    }

    /**
     * Test parseOptions Method
     */
    public function testParseOptions()
    {
        $options = 'w_200,h_100,c_1,bg_#999999,rz_1,sc_50,r_-45,unsh_0.25x0.25+8+0.065,rf_1,ett_100x80,fb_1';
        $image = new Image($options, 'http://fakeurl-to-img.jpeg', $this->app['params']);

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
            'thread' => '1',
        ];
        $parsedOptions = $image->parseOptions($options);

        $this->assertEquals($parsedOptions, $expectedParseArray);
    }
}