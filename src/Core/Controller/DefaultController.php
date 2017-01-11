<?php

namespace Core\Controller;

use Core\Entity\Image;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends CoreController
{

    public function indexAction()
    {
        return 'Flyimg: Hello from ' . $this->app->escape('Docker!');
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
        $image = new Image($options, $imageSrc, $this->app['params']);
        try {
           $imageContent = $manager->process($image);
        } catch (\Exception $e) {
            $image->unlinkUsedFiles();
            return new Response($e->getMessage(), Response::HTTP_FORBIDDEN);
        }

        return $this->generateImageResponse($image, $imageContent);
    }
}
