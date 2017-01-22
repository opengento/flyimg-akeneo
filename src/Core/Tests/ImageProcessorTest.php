<?php

namespace Core\Tests;

use Core\Entity\Image;
use Core\Service\ImageProcessor;
use Silex\Application;


class ImageProcessorTest extends \PHPUnit_Framework_TestCase
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
        $app = require __DIR__ . '/../../../app.php';
        $app['debug'] = true;
        unset($app['exception_handler']);
        return $app;
    }

    /**
     */
    public function testProcess()
    {
        $options = 'w_200,h_100,c_1,bg_#999999,rz_1,sc_50,r_-45,unsh_0.25x0.25+8+0.065,rf_1,ett_100x80';
        $image = new Image($options, __DIR__ . '/../../../web/Rovinj-Croatia.jpg', $this->app['params']);

        $this->assertFileExists($image->getTemporaryFile());

        /** @var ImageProcessor $processor */
        $processor = $this->app['image.processor'];
        $processor->process($image);

        $this->assertFileExists($image->getNewFilePath());
        unlink($image->getNewFilePath());
        unlink($image->getTemporaryFile());
    }
}