<?php

namespace Tests\Core;

use Core\Entity\Image;
use Core\Service\CoreManager;
use Silex\Application;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    const JPG_TEST_IMAGE = __DIR__.'/../testImages/square.jpg';
    const PNG_TEST_IMAGE = __DIR__.'/../testImages/square.png';
    const GIF_TEST_IMAGE = __DIR__.'/../testImages/animated.gif';
    const OPTION_URL = 'w_200,h_100,c_1,bg_#999999,rz_1,sc_50,r_-45,unsh_0.25x0.25+8+0.065,ett_100x80,fb_1,rf_1';
    const GIF_OPTION_URL = 'w_200,h_100,rf_1';

    /**
     * @var Application
     */
    protected $app = null;

    /**
     * @var CoreManager
     */
    protected $coreManager = null;

    /**
     * @var array
     */
    protected $generatedImage = [];

    /**
     *
     */
    public function setUp()
    {
        $this->app = $this->createApplication();
        $this->coreManager = $this->app['core.manager'];
    }

    /**
     *
     */
    protected function tearDown()
    {
        foreach ($this->generatedImage as $image) {
            if (!$image instanceof Image) {
                continue;
            }
            $image->unlinkUsedFiles(true);
        }
    }


    /**
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../app.php';
        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    /**
     */
    public function testApplicationInstance()
    {
        $this->assertInstanceOf('Silex\Application', $this->app);
    }
}
