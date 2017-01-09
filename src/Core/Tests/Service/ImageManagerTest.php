<?php
namespace Core\Tests\Service;

use Core\Service\ImageManager;
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
     *
     */
    public function tearDown()
    {
        $this->app = null;
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
     * Test extractByKey Method
     */
    public function testExtractByKey()
    {
        $tmpArray = [
            'key_1' => 1,
            'key_2' => 2,
            'key_3' => 3,
            'key_4' => 4,
            'key_5' => 5,
        ];
        $key2 = $this->app['image.manager']->extractByKey($tmpArray, 'key_2');
        $expectedArray = [
            'key_1' => 1,
            'key_3' => 3,
            'key_4' => 4,
            'key_5' => 5,
        ];
        $this->assertEquals($key2, 2);
        $this->assertEquals($expectedArray, $tmpArray);
    }

    /**
     * Test parseOptions Method
     */
    public function testParseOptions()
    {
        $options = 'w_200,h_100,c_1,bg_#999999,rz_1,sc_50,r_-45,unsh_0.25x0.25+8+0.065,rf_1,ett_100x80';
        $expectedParseArray = [
            'mozjpeg' => 1,
            'quality' => 90,
            'unsharp' => '0.25x0.25+8+0.065',
            'width' => 200,
            'height' => 100,
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
        $parsedOptions = $this->app['image.manager']->parseOptions($options);

        $this->assertEquals($parsedOptions, $expectedParseArray);
    }
}