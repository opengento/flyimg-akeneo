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

    /**
     * @param $options
     * @param null $imageSrc
     * @return Response
     */
    public function uploadAction($options, $imageSrc = null)
    {
        /** @var \Core\Service\ImageManager $manager */
        $manager = $this->app['image.manager'];
        $image = $manager->process($options, $imageSrc);
        return $this->generateImageResponse($image);
    }
}