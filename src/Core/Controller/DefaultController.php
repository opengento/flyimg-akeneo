<?php

namespace Core\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DefaultController extends CoreController
{

    public function indexAction()
    {
        return 'Hello from ' . $this->app->escape('Docker!');
    }

    /**
     * @param $options
     * @param null $imageSrc
     * @return Response
     */
    public function uploadAction($options, $imageSrc = null)
    {
        /** @var \Core\Service\ImageManager $resizer */
        $resizer = $this->app['image.resizer'];
        $image = $resizer->process($options, $imageSrc);
        $response = new Response();
        $response->headers->set('Content-Type', 'image');
        $response->setContent($image);
        return $response;
    }
}