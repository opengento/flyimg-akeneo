<?php

namespace Core\Entity\Image;

use Core\Exception\ExecFailedException;

/**
 * Fetches and stores image properties like mimetype, bit depth, weight and dimensions.
 */
class ImageMetaInfo
{
    public const IMAGE_PROP_FILE_FORMAT = 'format';
    public const IMAGE_PROP_DIMENSIONS = 'dimensions';
    public const IMAGE_PROP_CANVAS_COORD = 'canvas';
    public const IMAGE_PROP_COLOR_DEPTH = 'colorDepth';
    public const IMAGE_PROP_COLOR_PROFILE = 'colorProfile';
    public const IMAGE_PROP_FILE_WEIGHT = 'weight';
    
    /** @var string */
    protected $imagePath;

    /** @var string */
    protected $imageMimeType;

    /** @var array Associative array that holds basic image info. Consider in the future create an imageInfo class. */
    protected $imageInfo;

    /**
     * OutputImage constructor.
     *
     * @param string     $imagePath
     */
    public function __construct(string $imagePath)
    {
        $this->imagePath = $imagePath;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->imagePath;
    }

    /**
     * Returns the mime-type (not the extension) of a file or image i.e: image/png
     * @return string
     */
    public function getMimeType(): string
    {
        if (isset($this->imageMimeType)) {
            return $this->imageMimeType;
        }

        $this->imageMimeType = finfo_file(
            finfo_open(FILEINFO_MIME_TYPE),
            $this->imagePath
        );

        return $this->imageMimeType;
    }


    /**
     * @return string Image type as identified by IM like PNG, JPEG, GIF ...
     */
    public function getFormat(): string
    {
        return $this->getInfo()[self::IMAGE_PROP_FILE_FORMAT];
    }

    /**
     * @return string Image Canvas as identified by IM like: 70x46+0+0
     */
    public function getCanvas(): string
    {
        return $this->getInfo()[self::IMAGE_PROP_CANVAS_COORD];
    }

    /**
     * @return string Image bit depth as identified by IM like: 8-bit
     */
    public function getColorBitDepth(): string
    {
        return $this->getInfo()[self::IMAGE_PROP_COLOR_DEPTH];
    }

    /**
     * @return string Image color profile as identified by IM like: sRGB, aRGB
     */
    public function getColorProfile(): string
    {
        return $this->getInfo()[self::IMAGE_PROP_COLOR_PROFILE];
    }

    /**
     * @return string Image File weight as identified by IM like: 2.36KB
     */
    public function getFileWeight(): string
    {
        return $this->getInfo()[self::IMAGE_PROP_FILE_WEIGHT];
    }

    /**
     * Gets parsed image dimensions into width and height
     * @return array Associative array with `width` and `height` values in pixels
     */
    public function getDimensions(): array
    {
        $dimensions = $this->getInfo()[self::IMAGE_PROP_DIMENSIONS];
        $dimensions = explode('x', $dimensions);
        return [
            'width'  => $dimensions[0],
            'height' => $dimensions[1]
        ];
    }

    /**
     * get stored ImageInfo or fetch it and store it
     * @return array Associative array with basic information od the image
     */
    public function getInfo(): array
    {
        if(!empty($this->imageInfo)) {
            return $this->imageInfo;
        }

        $this->imageInfo = $this->getImageImIdentify();
        return $this->imageInfo;
    }

    /**
     * Returns an associative array with the info of this image's path.
     * In the future exec functionality to an \Core\Processor\Execution class
     * @return array
     * @throws \Exception
     */
    protected function getImageImIdentify(): array
    {
        exec('/usr/bin/identify ' . $this->getPath(), $output, $code);
        if (count($output) === 0) {
            $outputError = $code;
        } else {
            $outputError = implode(PHP_EOL, $output);
        }

        if ($code !== 0) {
            throw new ExecFailedException(
                "Command failed. The exit code: ".
                $outputError."<br>The last line of output: ".
                $commandStr
            );
        }

        $imageDetails = $this->parseImageInfoResponse($output);
        return $imageDetails;
    }

    /**
     * Parses the default output of imagemagik identify command
     * @param  array $output the STDOUT from executing an identify command
     * @return array         associative array with the info in there
     * @throws \Exception
     */
    protected function parseImageInfoResponse($output): array
    {
        if (!is_array($output) || empty($output)) {
            throw new Exception("Image identify failed", 1);
            return [];
        }

        $output = explode(' ', $output[0]);
        return [
            self::IMAGE_PROP_FILE_FORMAT   => $output[1],
            self::IMAGE_PROP_DIMENSIONS    => $output[2],
            self::IMAGE_PROP_CANVAS_COORD  => $output[3],
            self::IMAGE_PROP_COLOR_DEPTH   => $output[4],
            self::IMAGE_PROP_COLOR_PROFILE => $output[5],
            self::IMAGE_PROP_FILE_WEIGHT   => $output[6],
        ];
    }
}
