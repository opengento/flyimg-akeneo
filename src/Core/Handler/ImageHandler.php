<?php

namespace Core\Handler;

use Core\Entity\InputImage;
use Core\Entity\OutputImage;
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
     * @return OutputImage
     * @throws \Exception
     */
    public function processImage(string $options, string $imageSrc): OutputImage
    {
        $this->checkRestrictedDomains($imageSrc);

        $parsedOptions = $this->parseOptions($options);
        $inputImage = new InputImage($parsedOptions, $imageSrc);
        $outputImage = new OutputImage($inputImage);

        try {
            if ($this->filesystem->has($outputImage->getOutputImageName()) && $parsedOptions['refresh']) {
                $this->filesystem->delete($outputImage->getOutputImageName());
            }

            if (!$this->filesystem->has($outputImage->getOutputImageName())) {
                $outputImage = $this->processNewImage($outputImage);
            }

            $outputImage->setOutputImageContent($this->filesystem->read($outputImage->getOutputImageName()));
        } catch (\Exception $e) {
            $outputImage->removeOutputImage();
            throw $e;
        }

        return $outputImage;
    }

    /**
     * @param OutputImage $outputImage
     */
    protected function faceDetectionProcess(OutputImage $outputImage): void
    {
        $faceCrop = $outputImage->extract('face-crop');
        $faceCropPosition = $outputImage->extract('face-crop-position');
        $faceBlur = $outputImage->extract('face-blur');

        if ($faceBlur && !$outputImage->isGifSupport()) {
            $this->faceDetectProcessor->blurFaces($outputImage->getInputImage());
        }

        if ($faceCrop && !$outputImage->isGifSupport()) {
            $this->faceDetectProcessor->cropFaces($outputImage->getInputImage(), $faceCropPosition);
        }
    }

    /**
     * @param OutputImage $outputImage
     *
     * @return OutputImage
     */
    protected function processNewImage(OutputImage $outputImage): OutputImage
    {
        //Check Face Detection options
        $this->faceDetectionProcess($outputImage);

        $outputImage = $this->getImageProcessor()->processNewImage($outputImage);
        $this->filesystem->write(
            $outputImage->getOutputImageName(),
            stream_get_contents(fopen($outputImage->getOutputImagePath(), 'r'))
        );

        return $outputImage;
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
     * @param OutputImage $outputImage
     *
     * @return string
     */
    public function getResponseContentType(OutputImage $outputImage): string
    {
        if ($outputImage->getOutputImageExtension() == OutputImage::EXT_WEBP) {
            return OutputImage::WEBP_MIME_TYPE;
        }
        if ($outputImage->getOutputImageExtension() == OutputImage::EXT_PNG) {
            return OutputImage::PNG_MIME_TYPE;
        }
        if ($outputImage->getOutputImageExtension() == OutputImage::EXT_GIF) {
            return OutputImage::GIF_MIME_TYPE;
        }

        return OutputImage::JPEG_MIME_TYPE;
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
