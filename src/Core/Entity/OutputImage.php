<?php

namespace Core\Entity;

use Core\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OutputImage
 * @package Core\Entity
 */
class OutputImage
{
    /** Content TYPE */
    const WEBP_MIME_TYPE = 'image/webp';
    const JPEG_MIME_TYPE = 'image/jpeg';
    const PNG_MIME_TYPE = 'image/png';
    const GIF_MIME_TYPE = 'image/gif';

    /** Extension output */
    const EXT_AUTO = 'auto';
    const EXT_PNG = 'png';
    const EXT_WEBP = 'webp';
    const EXT_JPG = 'jpg';
    const EXT_GIF = 'gif';

    /** @var InputImage */
    protected $inputImage;

    /** @var string */
    protected $outputImageName;

    /** @var string */
    protected $outputImagePath;

    /** @var string */
    protected $outputImageExtension;

    /** @var string */
    protected $outputImageContent;

    /** @var string */
    protected $commandString;

    /**
     * OutputImage constructor.
     *
     * @param InputImage $inputImage
     */
    public function __construct(InputImage $inputImage)
    {
        $this->inputImage = $inputImage;
        $this->generateFilesName();
        $this->generateFileExtension();
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function extract(string $key): string
    {
        return $this->inputImage->extract($key);
    }

    /**
     * @return InputImage
     */
    public function getInputImage(): InputImage
    {
        return $this->inputImage;
    }

    /**
     * @return string
     */
    public function getOutputImageName(): string
    {
        return $this->outputImageName;
    }

    /**
     * @return string
     */
    public function getOutputImagePath(): string
    {
        return $this->outputImagePath;
    }

    /**
     * @param string $commandStr
     */
    public function setCommandString(string $commandStr)
    {
        $this->commandString = $commandStr;
    }

    public function getCommandString(): string
    {
        return $this->commandString;
    }

    /**
     * @return string
     */
    public function getOutputImageContent(): string
    {
        return $this->outputImageContent;
    }

    /**
     * @param string $outputImageContent
     */
    public function setOutputImageContent(string $outputImageContent)
    {
        $this->outputImageContent = $outputImageContent;
    }

    /**
     * @return string
     */
    public function getOutputImageExtension(): string
    {
        return $this->outputImageExtension;
    }

    /**
     * Remove Temporary file
     */
    public function removeOutputImage()
    {
        if (file_exists($this->getOutputImagePath())) {
            unlink($this->getOutputImagePath());
        }
    }

    /**
     * Remove all generated files
     */
    public function cleanupFiles()
    {
        $this->removeOutputImage();

        $fullPath = UPLOAD_DIR.$this->getOutputImageName();
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $this->getInputImage()->removeInputImage();
    }

    /**
     * Generate files name + files path
     */
    protected function generateFilesName()
    {
        $hashedOptions = $this->inputImage->getOptions();
        unset($hashedOptions['refresh']);
        $this->outputImageName = md5(implode('.', $hashedOptions).$this->inputImage->getSourceImageUrl());
        $this->outputImagePath = TMP_DIR.$this->outputImageName;

        if ($this->inputImage->getOptions()['refresh']) {
            $this->outputImagePath .= uniqid("-", true);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function generateFileExtension()
    {
        $outputExtension = $this->extract('output');

        if ($outputExtension == self::EXT_AUTO) {
            $this->outputImageExtension = self::EXT_JPG;
            if ($this->isPngSupport()) {
                $this->outputImageExtension = self::EXT_PNG;
            }
            if ($this->isWebPSupport() || $this->inputImage->getSourceImageMimeType() === self::WEBP_MIME_TYPE) {
                $this->outputImageExtension = self::EXT_WEBP;
            }
            if ($this->isGifSupport()) {
                $this->outputImageExtension = self::EXT_GIF;
            }
        } else {
            if (!in_array(
                $outputExtension,
                [self::EXT_PNG, self::EXT_JPG, self::EXT_GIF, self::EXT_JPG, self::EXT_WEBP]
            )
            ) {
                throw new InvalidArgumentException("Invalid file output requested : ".$outputExtension);
            }
            $this->outputImageExtension = $outputExtension;
        }
        $fileExtension = '.'.$this->outputImageExtension;
        $this->outputImagePath .= $fileExtension;
        $this->outputImageName .= $fileExtension;
    }

    /**
     * Return boolean stating if WebP image format is supported; following these conditions:
     *  - The request is specifically expecting a webP response, independent of the browser's capabilities
     *  OR:
     *  - The browser sent headers explicitly stating it supports webp (absolute requirement)
     *
     * @return bool
     */
    public function isWebPSupport(): bool
    {
        return $this->outputImageExtension == self::EXT_WEBP
            || (in_array(self::WEBP_MIME_TYPE, Request::createFromGlobals()->getAcceptableContentTypes()));
    }

    /**
     * @return bool
     */
    public function isGifSupport(): bool
    {
        return $this->inputImage->getSourceImageMimeType() == self::GIF_MIME_TYPE;
    }

    /**
     * @return bool
     */
    public function isPngSupport(): bool
    {
        return $this->inputImage->getSourceImageMimeType() == self::PNG_MIME_TYPE;
    }

    /**
     * @return bool
     */
    public function isMozJpegSupport(): bool
    {
        return $this->extract('mozjpeg') == 1 &&
            (!$this->isPngSupport() || $this->outputImageExtension == self::EXT_JPG) &&
            (!$this->isGifSupport()) &&
            ($this->getOutputImageExtension() != self::EXT_GIF);
    }
}
