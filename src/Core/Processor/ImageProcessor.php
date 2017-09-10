<?php

namespace Core\Processor;

use Core\Entity\Image\OutputImage;

/**
 * Class ImageProcessor
 * @package Core\Service
 * Is the above line still valid??
 *
 * In this class we separate requests in 3 types
 *  - Simple resize geometry, resolved with -thumbnail
 *      -- Only width or height
 *      -- Only width and height
 *  - Cropping
 *
 *  - Advanced requests
 */
class ImageProcessor extends Processor
{
    /**
     * This holds the width and height dimensions in pixels of the source image
     * @var array
     */
    protected $sourceDimensions = [];

    /**
     * Basic source image info parsed from IM identify command
     * @var array
     */
    protected $sourceInfo = [];

    /**
     * OptionsBag from the request
     * @var Core\Entity\OptionsBag
     */
    protected $options;

    /**
     * Save new FileName based on source file and list of options
     *
     * @param OutputImage $outputImage
     *
     * @return OutputImage
     * @throws \Exception
     */
    public function processNewImage(OutputImage $outputImage): OutputImage
    {
        $this->options = $outputImage->getInputImage()->getOptionsBag();
        $this->generateCmdString($outputImage);
        $this->execute($outputImage->getCommandString());

        return $outputImage;
    }

    /**
     * Generate Command string bases on options
     *
     * @param OutputImage $outputImage
     */
    public function generateCmdString(OutputImage $outputImage)
    {
        $strip = $outputImage->extract('strip');
        $thread = $outputImage->extract('thread');
        $resize = $outputImage->extract('resize');
        $frame = $outputImage->extract('gif-frame');

        list($size, $extent, $gravity) = $this->generateSize($outputImage);

        // we default to thumbnail
        $resizeOperator = $resize ? 'resize' : 'thumbnail';
        $command = [];
        $command[] = self::IM_CONVERT_COMMAND;
        $tmpFileName = $outputImage->getInputImage()->getSourceImagePath();

        //Check the source image is gif
        if ($outputImage->isInputGif()) {
            $command[] = '-coalesce';
            if ($outputImage->getOutputImageExtension() != OutputImage::EXT_GIF) {
                $tmpFileName .= '['.escapeshellarg($frame).']';
            }
        }

        $command[] = " " . $tmpFileName;
        $command[] = ' -' . $resizeOperator . ' ' .
            $size . $gravity . $extent .
            ' -colorspace sRGB';

        foreach ($outputImage->getInputImage()->getOptionsBag() as $key => $value) {
            if (!empty($value) && !in_array($key, self::EXCLUDED_IM_OPTIONS)) {
                $command[] = "-{$key} ".escapeshellarg($value);
            }
        }

        // strip is added internally by ImageMagick when using -thumbnail
        if (!empty($strip)) {
            $command[] = "-strip ";
        }

        if (!empty($thread)) {
            $command[] = "-limit thread ".escapeshellarg($thread);
        }

        $command = $this->applyQuality($outputImage, $command);

        $commandStr = implode(' ', $command);
        $outputImage->setCommandString($commandStr);
    }

    /**
     * Apply the Quality processor based on options
     *
     * @param OutputImage $outputImage
     * @param array       $command
     *
     * @return array
     */
    protected function applyQuality(OutputImage $outputImage, array $command): array
    {
        $quality = $outputImage->extract('quality');
        /** WebP format */
        if (is_executable(self::CWEBP_COMMAND) && $outputImage->isOutputWebP()) {
            $lossLess = $outputImage->extract('webp-lossless') ? 'true' : 'false';
            $command[] = "-quality ".escapeshellarg($quality).
                " -define webp:lossless=".$lossLess." ".escapeshellarg($outputImage->getOutputImagePath());
        } /** MozJpeg compression */
        elseif (is_executable(self::MOZJPEG_COMMAND) && $outputImage->isOutputMozJpeg()) {
            $command[] = "TGA:- | ".escapeshellarg(self::MOZJPEG_COMMAND)
                ." -quality ".escapeshellarg($quality)
                ." -outfile ".escapeshellarg($outputImage->getOutputImagePath())
                ." -targa";
        } /** default ImageMagick compression */
        else {
            $command[] = "-quality ".escapeshellarg($quality).
                " ".escapeshellarg($outputImage->getOutputImagePath());
        }

        return $command;
    }

    /**
     * Size and Crop logic
     *
     * @param OutputImage $outputImage
     *
     * @return array
     */
    protected function generateSize(OutputImage $outputImage): array
    {
        $targetWidth = $outputImage->extract('width');
        $targetHeight = $outputImage->extract('height');

        $size = $extent = '';
        if ($targetWidth) {
            $size .= (string)escapeshellarg($targetWidth);
        }
        if ($targetHeight) {
            $size .= (string)'x'.escapeshellarg($targetHeight);
        }

        // When width and height a whole bunch of special cases must be taken into consideration.
        // resizing constraints (< > ^ !) can only be applied to geometry with both width AND height
        $preserveNaturalSize = $outputImage->extract('preserve-natural-size');
        $preserveAspectRatio = $outputImage->extract('preserve-aspect-ratio');

        if ($targetWidth && $targetHeight) {
            if ($preserveNaturalSize) {
                // here we will compare source image dimensions to target dimensions and adjust
            }
            $gravity = ' -gravity '.escapeshellarg($outputImage->extract('gravity'));
            $resizingConstraints = '';
            if ($outputImage->extract('crop')) {
                $resizingConstraints .= '^';
                $extent = ' -extent '.$size;
                /**
                 * still need to solve the combination of ^
                 * -extent and +repage . Will need to do calculations with the
                 * original image dimensions vs. the target dimensions.
                 */
            } else {
                $extent .= ' +repage ';
            }
            $resizingConstraints .= $preserveAspectRatio ? '' : '!';
            $size .= $resizingConstraints;
        } else {
            $size .= $preserveNaturalSize ? '\>' : '';
            $gravity = '';
        }

        return [$size, $extent, $gravity];
    }

    /**
     * @return array
     */
    protected function getSourceImageDimensions()
    {
        if (!empty($this->sourceDimensions)) {
            return $this->sourceDimensions;
        }

        $this->sourceDimensions = $outputImage->getInputImage()->getImageDimensions();
        return $this->sourceDimensions;
    }

    /**
     * @return array Associative array with basic image
     */
    protected function getSourceImageInfo()
    {
        if (!empty($this->sourceInfo)) {
            return $this->sourceInfo;
        }

        $this->sourceInfo = $outputImage->getInputImage()->getImageInfo();
        return $this->sourceInfo;
    }
}
