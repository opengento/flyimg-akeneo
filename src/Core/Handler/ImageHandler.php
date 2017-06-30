<?php

namespace Core\Handler;

use Core\Entity\Image;
use Core\Exception\AppException;
use Core\Processor\ImageProcessor;
use Core\Processor\FaceDetectionProcessor;
use Core\Traits\ParserTrait;
use League\Flysystem\Filesystem;

/**
 * Class ImageHandler
 * @package Core\Service
 */
class ImageHandler
{
    use ParserTrait;

    /** @var ImageProcessor */
    protected $imageProcessor;

    /** @var FaceDetectionProcessor */
    protected $fdProcessor;

    /** @var Filesystem */
    protected $filesystem;

    /** @var array */
    protected $defaultParams;

    /**
     * ImageHandler constructor.
     *
     * @param ImageProcessor         $imageProcessor
     * @param FaceDetectionProcessor $fdProcessor
     * @param Filesystem             $filesystem
     * @param array                  $defaultParams
     */
    public function __construct(
        ImageProcessor $imageProcessor,
        FaceDetectionProcessor $fdProcessor,
        Filesystem $filesystem,
        array $defaultParams
    ) {
        $this->imageProcessor = $imageProcessor;
        $this->fdProcessor = $fdProcessor;
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
        $parsedOptions = $this->parseOptions($options, $this->defaultParams);
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
            $image->unlinkUsedFiles();
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
            $this->fdProcessor->blurFaces($image);
        }

        if ($faceCrop && !$image->isGifSupport()) {
            $this->fdProcessor->cropFaces($image, $faceCropPosition);
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
}
