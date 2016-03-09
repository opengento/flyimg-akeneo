<?php

namespace Core\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends CoreController
{

    public function indexAction()
    {
        return 'Hello from ' . $this->app->escape('Docker!');
    }
}