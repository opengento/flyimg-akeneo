<?php

namespace Core\Controller;

use Silex\Application;

class CoreController
{
    /**
     * @var Silex/Application
     */
    protected $app;

    /**
     * @param Silex /Application $app
     */
    public function setApp(Application $app)
    {
        $this->app = $app;
    }
}