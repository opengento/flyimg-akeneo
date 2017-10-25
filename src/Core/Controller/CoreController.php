<?php

namespace Core\Controller;

use Core\Entity\Image\OutputImage;
use Core\Handler\ImageHandler;
use Silex\Application;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
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
    public function application(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return ImageHandler
     */
    public function imageHandler(): ImageHandler
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
        $templateFullPath = ROOT_DIR.'/src/Core/Views/'.$templateName.'.php';
        if (!file_exists($templateFullPath)) {
            throw new FileNotFoundException('Template file note exist: '.$templateFullPath);
        }
        ob_start();
        include($templateFullPath);
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
        $response = $this->generateHeaders($image, $response);
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
    protected function generateHeaders(OutputImage $image, Response $response): Response
    {
        $imageHandler = $this->imageHandler();
        $response->headers->set('Content-Type', $imageHandler->responseContentType($image));

        $expireDate = new \DateTime();
        $expireDate->add(new \DateInterval('P1Y'));
        $response->setExpires($expireDate);
        $longCacheTime = 3600 * 24 * ((int)$this->app['params']->parameterByKey('header_cache_days'));

        $response->setMaxAge($longCacheTime);
        $response->setSharedMaxAge($longCacheTime);
        $response->setPublic();

        if ($image->getInputImage()->optionsBag()->get('refresh')) {
            $response->headers->set('Cache-Control', 'no-cache, private');
            $response->setExpires(null)->expire();

            $response->headers->set('im-identify', $imageHandler->imageProcessor()->imageIdentityInformation($image));
            $response->headers->set('im-command', $image->getCommandString());
        }

        return $response;
    }
}
