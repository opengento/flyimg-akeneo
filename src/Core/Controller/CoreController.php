<?php

namespace Core\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class CoreController
{
    protected $app;

    /**
     * @param Application $app
     */
    public function  setApp(Application $app)
    {
        $this->app = $app;
    }
}