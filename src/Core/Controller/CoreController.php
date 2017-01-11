<?php

namespace Core\Controller;

use Core\Entity\Image;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class CoreController
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function setApp(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param Image $image
     * @param mixed $imageContent
     * @return Response
     */
    public function generateImageResponse(Image $image, $imageContent)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'image/jpeg');
        if ($image->getOptions()['refresh']) {
            $response->headers->set('im-identify', $image->getImageIdentity());
            $response->headers->set('im-command', $image->getFinalCommandStr());
        }
        $response->setContent($imageContent);

        $image->unlinkUsedFiles();
        return $response;
    }
}
