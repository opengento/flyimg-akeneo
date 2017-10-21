<?php

namespace Core\Entity\Image;

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
    const EXT_INPUT = 'input';
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

    /** @var array list of the supported output extensions */
    protected $allowedOutExtensions = [self::EXT_PNG, self::EXT_JPG, self::EXT_GIF, self::EXT_WEBP];

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
    public function extractKey(string $key): string
    {
        return $this->inputImage->extractKey($key);
    }

    /**
     * @return InputImage
     */
    public function inputImage(): InputImage
    {
        return $this->inputImage;
    }

    /**
     * @return string
     */
    public function outputImageName(): string
    {
        return $this->outputImageName;
    }

    /**
     * @return string
     */
    public function outputImagePath(): string
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

    public function commandString(): string
    {
        return $this->commandString;
    }

    /**
     * @return string
     */
    public function outputImageContent(): string
    {
        return $this->outputImageContent;
    }

    /**
     * @param string $outputImageContent
     */
    public function attachOutputContent(string $outputImageContent)
    {
        $this->outputImageContent = $outputImageContent;
    }

    /**
     * @return string
     */
    public function outputImageExtension(): string
    {
        return $this->outputImageExtension;
    }

    /**
     * Remove Temporary file
     */
    public function removeOutputImage()
    {
        if (file_exists($this->outputImagePath())) {
            unlink($this->outputImagePath());
        }
    }

    /**
     * Remove all generated files
     */
    public function cleanupFiles()
    {
        $this->removeOutputImage();

        $fullPath = UPLOAD_DIR.$this->outputImageName();
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $this->inputImage()->removeInputImage();
    }

    /**
     * Generate files name + files path
     */
    protected function generateFilesName()
    {
        $hashedOptions = clone $this->inputImage->optionsBag();
        $hashedOptions->remove('refresh');
        $this->outputImageName = md5(
            implode('.', $hashedOptions->asArray()).$this->inputImage->sourceImageUrl()
        );
        $this->outputImagePath = TMP_DIR.$this->outputImageName;

        if ($this->inputImage->optionsBag()->get('refresh')) {
            $this->outputImagePath .= uniqid("-", true);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function generateFileExtension()
    {
        $this->outputImageExtension = $this->resolveOutputImageExtension($this->extractKey('output'));
        $fileExtension = '.'.$this->outputImageExtension;
        $this->outputImagePath .= $fileExtension;
        $this->outputImageName .= $fileExtension;
    }

    /**
     * Given a certain output expected this method will resolve the extension
     *
     * @param  string $requestedOutput file type extension, or behaviour like `auto` or `input`
     *
     * @return string The extension to generate given all the configs and conditions present.
     * @throws InvalidArgumentException
     */
    protected function resolveOutputImageExtension(string $requestedOutput): string
    {
        $resolvedExtension = self::EXT_JPG;

        if ($requestedOutput == self::EXT_INPUT) {
            $resolvedExtension = $this->inputImageExtension();
        } elseif ($requestedOutput == self::EXT_AUTO) {
            $resolvedExtension = $this->autoExtension();
        } else {
            if (!in_array($requestedOutput, $this->allowedOutExtensions)) {
                // Maybe trow exception only when in debug mode ?
                throw new InvalidArgumentException("Invalid file output requested : ".$requestedOutput);
            }
            $resolvedExtension = $requestedOutput;
        }

        return $resolvedExtension;
    }

    /**
     * This method defines what extension / format to use in the output image, using the following criteria:
     *   1. Optimal image format for the requesting browser
     *   2. Source image format
     *   3. JPG
     *
     * @return string One image extension
     */
    protected function autoExtension(): string
    {
        // for now AUTO means webP, or ...
        if ($this->isWebPBrowserSupported()) {
            return self::EXT_WEBP;
        }

        // fall back to input extension, which falls back to jpg
        return $this->inputImageExtension();
    }

    /**
     * get the extension of the input image asociated with this entity
     * @return string   defaults to `jpg`
     */
    protected function inputImageExtension(): string
    {
        $resolvedExtension = $this->extensionByMimeType($this->inputImage->sourceImageMimeType());

        return $resolvedExtension ? $resolvedExtension : self::EXT_JPG;
    }

    /**
     * given a mime-type this returns the extension associated to it
     *
     * @param  string $mimeType mime-type
     *
     * @return string           extension OR empty string
     */
    protected function extensionByMimeType(string $mimeType): string
    {
        $mimeToExtensions = [
            self::PNG_MIME_TYPE => self::EXT_PNG,
            self::WEBP_MIME_TYPE => self::EXT_WEBP,
            self::JPEG_MIME_TYPE => self::EXT_JPG,
            self::GIF_MIME_TYPE => self::EXT_GIF,
        ];

        return array_key_exists($mimeType, $mimeToExtensions) ? $mimeToExtensions[$mimeType] : '';
    }

    /**
     * Return boolean stating if WebP image format is supported; following these conditions:
     *  - The request is specifically expecting a webP response, independent of the browser's capabilities
     *  OR:
     *  - The browser sent headers explicitly stating it supports webp (absolute requirement)
     *
     * @return bool
     */
    public function isOutputWebP(): bool
    {
        return $this->outputImageExtension == self::EXT_WEBP;
    }

    /**
     * @return bool
     */
    public function isOutputGif(): bool
    {
        return $this->outputImageExtension == self::EXT_GIF;
    }

    /**
     * @return bool
     */
    public function isOutputPng(): bool
    {
        return $this->outputImageExtension == self::EXT_PNG;
    }

    /**
     * @return bool
     */
    public function isInputGif(): bool
    {
        return $this->inputImage->sourceImageMimeType() == self::GIF_MIME_TYPE;
    }

    /**
     * @return bool
     */
    public function isOutputMozJpeg(): bool
    {
        return $this->extractKey('mozjpeg') == 1 &&
            (!$this->isOutputPng() || $this->outputImageExtension == self::EXT_JPG) &&
            (!$this->isOutputGif());
    }

    /**
     * This just checks if the browser requesting the asset explicitly supports WebP
     * @return boolean
     */
    public function isWebPBrowserSupported(): bool
    {
        return in_array(self::WEBP_MIME_TYPE, Request::createFromGlobals()->getAcceptableContentTypes());
    }
}
