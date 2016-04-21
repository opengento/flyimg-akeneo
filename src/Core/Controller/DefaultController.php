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


    public function uploadAction($options, $imageSrc)
    {
        $options = $this->parseOptions($options);

        /** @var \Core\Service\ImageResizer $resizer */
        $resizer = $this->app['image.resizer'];
        $image = $resizer->resize($imageSrc, $options);
        $response = new Response();
        $response->headers->set('Content-Type', 'image');
        $response->setContent($image);
        return $response;
    }

    private function parseOptions($options)
    {
        $defaultOptions = $this->app['params']['default_options'];
//        echo '<pre>';
//        var_dump($defaultOptions);
//        exit;
        $optionsUrl = explode($this->app['params']['options_separator'], $options);
        $options = [];
        foreach ($optionsUrl as $option) {
            $optArray = explode('_', $option);
            if ($optArray[0] == 'w') {
                $options['width'] = $optArray[1];
            }
            if ($optArray[0] == 'h') {
                $options['height'] = $optArray[1];
            }
            if ($optArray[0] == 'q') {
                $options['quality'] = $optArray[1];
            }
        }


//TODO
        return array_merge($defaultOptions, $options);
    }
}