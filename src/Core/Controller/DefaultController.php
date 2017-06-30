<?php

namespace Core\Controller;

use Symfony\Component\HttpFoundation\Response;

class DefaultController extends CoreController
{
    /**
     * @return string
     */
    public function indexAction()
    {
        return $this->render('Default/index');
    }

    /**
     * @param string $options
     * @param string $imageSrc
     *
     * @return Response
     */
    public function uploadAction(string $options, string $imageSrc = null): Response
    {
        try {
            $image = $this->getImageHandler()->processImage($options, $imageSrc);
        } catch (\Exception $e) {
            return new Response($e->getMessage().' '.$e->getFile().' '.$e->getLine(), Response::HTTP_FORBIDDEN);
        }

        return $this->generateImageResponse($image);
    }

    /**
     * @param string $options
     * @param string $imageSrc
     *
     * @return Response
     */
    public function pathAction(string $options, string $imageSrc = null): Response
    {
        try {
            $image = $this->getImageHandler()->processImage($options, $imageSrc);
        } catch (\Exception $e) {
            return new Response($e->getMessage().' '.$e->getFile().' '.$e->getLine(), Response::HTTP_FORBIDDEN);
        }

        return $this->generatePathResponse($image);
    }
}
