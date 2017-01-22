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

        $expireDate = new \DateTime();
        $expireDate->add(new \DateInterval('P1Y'));
        $response->setExpires($expireDate);
        $longCacheTime = 3600 * 24 * ((int)$this->app['params']['header_cache_days']);

        $response->setMaxAge($longCacheTime);
        $response->setSharedMaxAge($longCacheTime);
        $response->setPublic();

        if ($image->getOptions()['refresh']) {
            $response->headers->set('Cache-Control', 'no-cache, private');
            $response->setExpires(null)->expire();

            $response->headers->set('im-identify', $this->app['image.processor']->getImageIdentity($image));
            $response->headers->set('im-command', $image->getFinalCommandStr());
        }

        $response->setContent($imageContent);

        $image->unlinkUsedFiles();
        return $response;
    }
}
