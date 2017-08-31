<?php

namespace Core\Controller;

use Core\Entity\Image\OutputImage;
use Core\Handler\ImageHandler;
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
     * @return ImageHandler
     */
    public function getImageHandler(): ImageHandler
    {
        return $this->app['image.handler'];
    }

    /**
     * @param string $templateName
     *
     * @return Response
     */
    public function render(string $templateName): Response
    {
        ob_start();
        include(ROOT_DIR.'/src/Core/Views/'.$templateName.'.php');
        $body = ob_get_contents();
        ob_end_clean();

        return new Response($body);
    }

    /**
     * @param OutputImage $image
     *
     * @return Response
     */
    public function generateImageResponse(OutputImage $image): Response
    {
        $response = new Response();
        $response->setContent($image->getOutputImageContent());
        $response = $this->setHeadersContent($image, $response);
        $image->removeOutputImage();

        return $response;
    }

    /**
     * @param OutputImage $image
     *
     * @return Response
     */
    public function generatePathResponse(OutputImage $image): Response
    {
        $response = new Response();
        $imagePath = $image->getOutputImageName();
        $imagePath = sprintf($this->app['flysystems']['file_path_resolver'], $imagePath);
        $response->setContent($imagePath);
        $image->removeOutputImage();

        return $response;
    }

    /**
     * @param OutputImage $image
     * @param Response    $response
     *
     * @return Response
     */
    protected function setHeadersContent(OutputImage $image, Response $response): Response
    {
        $imageHandler = $this->getImageHandler();
        $response->headers->set('Content-Type', $imageHandler->getResponseContentType($image));

        $expireDate = new \DateTime();
        $expireDate->add(new \DateInterval('P1Y'));
        $response->setExpires($expireDate);
        $longCacheTime = 3600 * 24 * ((int)$this->app['params']->get('header_cache_days'));

        $response->setMaxAge($longCacheTime);
        $response->setSharedMaxAge($longCacheTime);
        $response->setPublic();

        if ($image->getInputImage()->getOptionsBag()->get('refresh')) {
            $response->headers->set('Cache-Control', 'no-cache, private');
            $response->setExpires(null)->expire();

            $response->headers->set('im-identify', $imageHandler->getImageProcessor()->getImageIdentity($image));
            $response->headers->set('im-command', $image->getCommandString());
        }

        return $response;
    }
}
