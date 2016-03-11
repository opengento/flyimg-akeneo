<?php

namespace Core\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends CoreController
{

    public function indexAction()
    {
        $resi = $this->app['image.resizer'];
        return 'Hello from ' . $this->app->escape('Docker!');
    }


    public function uploadAction($options, $imageSrc)
    {
        $options = $this->parseOptions($options);

        /** @var \Core\Service\Resizer $resizer */
        $resizer = $this->app['image.resizer'];
        return $resizer->resize($imageSrc, $options);
    }

    private function parseOptions($options)
    {
        $optionsUrl = explode($this->app['params']['options_separator'], $options);
        $options = [];
//TODO
        return $options;
    }
}