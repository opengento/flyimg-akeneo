<?php
namespace Core\Tests\Service;

use Core\Service\ImageManager;
use Silex\Application;

class ImageManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    protected $app;
    /**
     * @var ImageManager
     */
    protected $imageManager;

    /**
     *
     */
    public function setUp()
    {
        $this->app = $this->createApplication();
        $this->imageManager = $this->app['image.manager'];
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
     *
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
        $key2 = $this->imageManager->extractByKey($tmpArray, 'key_2');
        $expectedArray = [
            'key_1' => 1,
            'key_3' => 3,
            'key_4' => 4,
            'key_5' => 5,
        ];
        $this->assertEquals($key2, 2);
        $this->assertEquals($expectedArray, $tmpArray);
    }
}