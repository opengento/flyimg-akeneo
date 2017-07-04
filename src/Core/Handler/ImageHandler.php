<?php

namespace Core\Handler;

use Core\Entity\Image;
use Core\Exception\AppException;
use Core\Processor\ImageProcessor;
use Core\Processor\FaceDetectProcessor;
use League\Flysystem\Filesystem;

/**
 * Class ImageHandler
 * @package Core\Service
 */
class ImageHandler
{
    /** @var ImageProcessor */
    protected $imageProcessor;

    /** @var FaceDetectProcessor */
    protected $faceDetectProcessor;

    /** @var Filesystem */
    protected $filesystem;

    /** @var array */
    protected $defaultParams;

    /**
     * ImageHandler constructor.
     *
     * @param ImageProcessor      $imageProcessor
     * @param FaceDetectProcessor $faceDetectProcessor
     * @param Filesystem          $filesystem
     * @param array               $defaultParams
     */
    public function __construct(
        ImageProcessor $imageProcessor,
        FaceDetectProcessor $faceDetectProcessor,
        Filesystem $filesystem,
        array $defaultParams
    ) {
        $this->imageProcessor = $imageProcessor;
        $this->faceDetectProcessor = $faceDetectProcessor;
        $this->filesystem = $filesystem;
        $this->defaultParams = $defaultParams;
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
     * @param string $options
     * @param string $imageSrc
     *
     * @return Image
     * @throws \Exception
     */
    public function processImage(string $options, string $imageSrc): Image
    {
        $this->checkRestrictedDomains($imageSrc);
        $parsedOptions = $this->parseOptions($options);
        $image = new Image($parsedOptions, $imageSrc);

        try {
            if ($this->filesystem->has($image->getNewFileName()) && $image->getOptions()['refresh']) {
                $this->filesystem->delete($image->getNewFileName());
            }

            if (!$this->filesystem->has($image->getNewFileName())) {
                $image = $this->processNewImage($image);
            }

            $image->setContent($this->filesystem->read($image->getNewFileName()));
        } catch (\Exception $e) {
            $image->removeTemporaryFiles();
            throw $e;
        }

        return $image;
    }

    /**
     * @param Image $image
     */
    protected function faceDetectionProcess(Image $image): void
    {
        $faceCrop = $image->extract('face-crop');
        $faceCropPosition = $image->extract('face-crop-position');
        $faceBlur = $image->extract('face-blur');

        if ($faceBlur && !$image->isGifSupport()) {
            $this->faceDetectProcessor->blurFaces($image);
        }

        if ($faceCrop && !$image->isGifSupport()) {
            $this->faceDetectProcessor->cropFaces($image, $faceCropPosition);
        }
    }

    /**
     * @param Image $image
     *
     * @return Image
     */
    protected function processNewImage(Image $image): Image
    {
        //Check Face Detection options
        $this->faceDetectionProcess($image);

        $image = $this->getImageProcessor()->processNewImage($image);
        $this->filesystem->write(
            $image->getNewFileName(),
            stream_get_contents(fopen($image->getNewFilePath(), 'r'))
        );

        return $image;
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

    /**
     * @param Image $image
     *
     * @return string
     */
    public function getResponseContentType(Image $image): string
    {
        if ($image->getOutputExtension() == Image::EXT_WEBP) {
            return Image::WEBP_MIME_TYPE;
        }
        if ($image->getOutputExtension() == Image::EXT_PNG) {
            return Image::PNG_MIME_TYPE;
        }
        if ($image->getOutputExtension() == Image::EXT_GIF) {
            return Image::GIF_MIME_TYPE;
        }

        return Image::JPEG_MIME_TYPE;
    }

    /**
     * Parse options: match options keys and merge default options with given ones
     *
     * @param string $options
     *
     * @return array
     */
    public function parseOptions(string $options): array
    {
        $defaultOptions = $this->defaultParams['default_options'];
        $optionsKeys = $this->defaultParams['options_keys'];
        $optionsSeparator = !empty($this->defaultParams['options_separator']) ?
            $this->defaultParams['options_separator'] : ',';
        $optionsUrl = explode($optionsSeparator, $options);
        $options = [];
        foreach ($optionsUrl as $option) {
            $optArray = explode('_', $option);
            if (key_exists($optArray[0], $optionsKeys) && !empty($optionsKeys[$optArray[0]])) {
                $options[$optionsKeys[$optArray[0]]] = $optArray[1];
            }
        }

        return array_merge($defaultOptions, $options);
    }
}
