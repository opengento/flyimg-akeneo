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
        $longCacheTime = 3600*24*365;
        $cacheHeaders = [
            'max_age'       => $longCacheTime,
            's_maxage'      => $longCacheTime,
            'public'        => true,
        ];

        if ($image->getOptions()['refresh']) {
            $cacheHeaders = [];
            $response->headers->set('Cache-Control', 'no-cache, private');
            $response->setExpires(null)->expire();

            $response->headers->set('im-identify', $image->getImageIdentity());
            $response->headers->set('im-command', $image->getFinalCommandStr());
        }

        $response->setCache($cacheHeaders);
        $response->setContent($imageContent);

        $image->unlinkUsedFiles();
        return $response;
    }
}
