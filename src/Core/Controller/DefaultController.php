<?php

namespace Core\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends CoreController
{

    public function indexAction()
    {
        $resi = $this->app['image.resizer'];
        echo '<pre>';
        var_dump($resi);
        exit;
        return 'Hello from ' . $this->app->escape('Docker!');
    }
}