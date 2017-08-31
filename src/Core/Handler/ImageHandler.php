<?php

namespace Core\Handler;

use Core\Entity\AppParameters;
use Core\Entity\Image\InputImage;
use Core\Entity\OptionsBag;
use Core\Entity\Image\OutputImage;
use Core\Processor\ExtractProcessor;
use Core\Processor\FaceDetectProcessor;
use Core\Processor\ImageProcessor;
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

    /** @var ExtractProcessor */
    protected $extractProcessor;

    /** @var SecurityHandler */
    protected $securityHandler;

    /** @var Filesystem */
    protected $filesystem;

    /** @var AppParameters */
    protected $appParameters;

    /**
     * ImageHandler constructor.
     *
     * @param Filesystem    $filesystem
     * @param AppParameters $appParameters
     */
    public function __construct(Filesystem $filesystem, AppParameters $appParameters)
    {
        $this->filesystem = $filesystem;
        $this->appParameters = $appParameters;

        $this->imageProcessor = new ImageProcessor();
        $this->faceDetectProcessor = new FaceDetectProcessor();
        $this->extractProcessor = new ExtractProcessor();
        $this->securityHandler = new SecurityHandler($appParameters);
    }

    /**
     * @return ImageProcessor
     */
    public function getImageProcessor(): ImageProcessor
    {
        return $this->imageProcessor;
    }

    /**
     * @return AppParameters
     */
    public function getAppParameters(): AppParameters
    {
        return $this->appParameters;
    }

    /**
     * @return SecurityHandler
     */
    public function getSecurityHandler(): SecurityHandler
    {
        return $this->securityHandler;
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
        list($options, $imageSrc) = $this->securityHandler->checkSecurityHash($options, $imageSrc);
        $this->securityHandler->checkRestrictedDomains($imageSrc);


        $optionsBag = new OptionsBag($this->appParameters, $options);
        $inputImage = new InputImage($optionsBag, $imageSrc);
        $outputImage = new OutputImage($inputImage);

        try {
            if ($this->filesystem->has($outputImage->getOutputImageName()) && $optionsBag->get('refresh')) {
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

        if ($faceBlur && !$outputImage->isOutputGif()) {
            $this->faceDetectProcessor->blurFaces($outputImage->getInputImage());
        }

        if ($faceCrop && !$outputImage->isOutputGif()) {
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
        //Check Extract options
        $this->extractProcess($outputImage);

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
     * @param OutputImage $outputImage
     */
    protected function extractProcess(OutputImage $outputImage): void
    {
        $extract = $outputImage->extract('extract');
        $topLeftX = $outputImage->extract('extract-top-x');
        $topLeftY = $outputImage->extract('extract-top-y');
        $bottomRightX = $outputImage->extract('extract-bottom-x');
        $bottomRightY = $outputImage->extract('extract-bottom-y');

        if ($extract) {
            $this->extractProcessor->extract(
                $outputImage->getInputImage(),
                $topLeftX,
                $topLeftY,
                $bottomRightX,
                $bottomRightY
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
}
