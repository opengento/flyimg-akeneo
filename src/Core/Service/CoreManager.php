<?php

namespace Core\Service;

use Core\Entity\Image;
use Core\Exception\AppException;
use Core\Traits\ParserTrait;
use League\Flysystem\Filesystem;

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

    /** @var Filesystem */
    protected $filesystem;


    /**
     * CoreManager constructor.
     *
     * @param ImageProcessor $imageProcessor
     * @param array          $defaultParams
     * @param Filesystem     $filesystem
     */
    public function __construct(ImageProcessor $imageProcessor, array $defaultParams, Filesystem $filesystem)
    {
        $this->imageProcessor = $imageProcessor;
        $this->defaultParams = $defaultParams;
        $this->filesystem = $filesystem;
    }

    /**
     * @return ImageProcessor
     */
    public function getImageProcessor(): ImageProcessor
    {
        return $this->imageProcessor;
    }

    /**
     * @return array
     */
    public function getDefaultParams(): array
    {
        return $this->defaultParams;
    }

    /**
     * @param $options
     * @param $imageSrc
     *
     * @return Image
     */
    public function processImage(string $options, string $imageSrc): Image
    {
        $this->checkRestrictedDomains($imageSrc);
        $parsedOptions = $this->parse($options);
        $image = new Image($parsedOptions, $imageSrc);

        try {

            if ($this->filesystem->has($image->getNewFileName()) && $image->getOptions()['refresh']) {
                $this->filesystem->delete($image->getNewFileName());
            }

            if (!$this->filesystem->has($image->getNewFileName())) {
                $image = $this->getImageProcessor()->processNewImage($image);
            }

            $image->setContent($this->filesystem->read($image->getNewFileName()));
        } catch (\Exception $e) {
            $image->unlinkUsedFiles();
            throw $e;
        }


        return $image;
    }

    /**
     * @param $options
     *
     * @return array
     */
    public function parse(string $options): array
    {
        return $this->parseOptions($options, $this->defaultParams);
    }

    /**
     * Check Restricted Domain enabled
     *
     * @param string $imageSource
     *
     * @throws AppException
     */
    protected function checkRestrictedDomains(string $imageSource)
    {
        if ($this->defaultParams['restricted_domains'] &&
            is_array($this->defaultParams['whitelist_domains']) &&
            !in_array(parse_url($imageSource, PHP_URL_HOST), $this->defaultParams['whitelist_domains'])
        ) {
            throw  new AppException(
                'Restricted domains enabled, the domain your fetching from is not allowed: '.
                parse_url($imageSource, PHP_URL_HOST)
            );
        }
    }
}
