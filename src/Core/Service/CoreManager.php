<?php

namespace Core\Service;

use Core\Entity\Image;
use Core\Exception\AppException;
use Core\Traits\ParserTrait;

/**
 * Class CoreManager
 * @package Core\Service
 */
class CoreManager
{
    use ParserTrait;

    /** @var ImageProcessor */
    protected $imageProcessor;

    /** @var array */
    protected $defaultParams;

    /**
     * CoreManager constructor.
     * @param ImageProcessor $imageProcessor
     * @param array          $defaultParams
     */
    public function __construct(ImageProcessor $imageProcessor, array $defaultParams)
    {
        $this->imageProcessor = $imageProcessor;
        $this->defaultParams = $defaultParams;
    }

    /**
     * @return ImageProcessor
     */
    public function getImageProcessor()
    {
        return $this->imageProcessor;
    }

    /**
     * @return array
     */
    public function getDefaultParams()
    {
        return $this->defaultParams;
    }

    /**
     * @param $options
     * @param $imageSrc
     * @return Image
     */
    public function processImage($options, $imageSrc)
    {
        $parsedOptions = $this->parse($options);
        $image = new Image($parsedOptions, $imageSrc);
        $this->checkRestrictedDomains($image);

        $image = $this->getImageProcessor()->process($image);

        return $image;
    }

    /**
     * @param $options
     * @return array
     */
    public function parse($options)
    {
        return $this->parseOptions($options, $this->defaultParams);
    }

    /**
     * Check Restricted Domain enabled
     * @param Image $image
     * @throws AppException
     */
    protected function checkRestrictedDomains(Image $image)
    {
        if ($this->defaultParams['restricted_domains'] &&
            is_array($this->defaultParams['whitelist_domains']) &&
            !in_array(parse_url($image->getSourceFile(), PHP_URL_HOST), $this->defaultParams['whitelist_domains'])
        ) {
            throw  new AppException(
                'Restricted domains enabled, the domain your fetching from is not allowed: '.
                parse_url($image->getSourceFile(), PHP_URL_HOST)
            );
        }
    }
}
