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

    protected $geometry;

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
        // we will categorize the operation in this method and call the adecuate functions depending on the parameters
        // 
        // first we get the data we need
        $width = $this->options->getOption('width');
        $height = $this->options->getOption('height');
        $crop = $this->options->getOption('crop');

        $command = [];
        $command[] = self::IM_CONVERT_COMMAND;

        // if width AND height AND crop are defined we need to do further checks to define the type of operation we will do
        if ($width && $height &&  $crop) {
            $size = $this->generateCropSize();
        } else if($width || $height) {
            $size = $this->generateSimpleSize();
        }
        
        
        $strip = $outputImage->extract('strip');
        $thread = $outputImage->extract('thread');
        $resize = $outputImage->extract('resize');
        $frame = $outputImage->extract('gif-frame');

        //list($size, $extent, $gravity) = $this->generateSize($outputImage);

        // we default to thumbnail
        //$resizeOperator = $resize ? 'resize' : 'thumbnail';
        
        $tmpFileName = $outputImage->getInputImage()->getSourceImagePath();

        //Check the source image is gif
        if ($outputImage->isInputGif()) {
            $command[] = '-coalesce';
            if ($outputImage->getOutputImageExtension() != OutputImage::EXT_GIF) {
                $tmpFileName .= '['.escapeshellarg($frame).']';
            }
        }

        $command[] = " " . $tmpFileName;
        $command[] = $size;
        $command[] = ' -colorspace sRGB';

        foreach ($outputImage->getInputImage()->getOptionsBag() as $key => $value) {
            if (!empty($value) && !in_array($key, self::EXCLUDED_IM_OPTIONS)) {
                //$command[] = "-{$key} ".escapeshellarg($value);
            }
        }

        // strip is added internally by ImageMagick when using -thumbnail
        if (!empty($strip)) {
            $command[] = "-strip ";
        }

        if (!empty($thread)) {
            $command[] = "-limit thread ".escapeshellarg($thread);
        }

        $command[] = $this->calculateQuality($outputImage);

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
    protected function calculateQuality(OutputImage $outputImage): string
    {
        $quality = $outputImage->extract('quality');
        $parameter = '';

        /** WebP format */
        if (is_executable(self::CWEBP_COMMAND) && $outputImage->isOutputWebP()) {
            $lossLess = $outputImage->extract('webp-lossless') ? 'true' : 'false';
            $parameter = "-quality ".escapeshellarg($quality).
                " -define webp:lossless=".$lossLess." ".escapeshellarg($outputImage->getOutputImagePath());
        } /** MozJpeg compression */
        elseif (is_executable(self::MOZJPEG_COMMAND) && $outputImage->isOutputMozJpeg()) {
            $parameter = "TGA:- | ".escapeshellarg(self::MOZJPEG_COMMAND)
                ." -quality ".escapeshellarg($quality)
                ." -outfile ".escapeshellarg($outputImage->getOutputImagePath())
                ." -targa";
        } /** default ImageMagick compression */
        else {
            $parameter = "-quality ".escapeshellarg($quality).
                " ".escapeshellarg($outputImage->getOutputImagePath());
        }

        return $parameter;
    }

    /**
     * IF we crop we need to know if the source image is bigger or smaller than the target size.
     * @return string command section for the resizing.
     */
    protected function generateCropSize(): string
    {
        $command = [];

        $gravity = $this->options->getOption('gravity');
        $command[] = '-gravity ' . $gravity;
        $command[] = '-crop ' . $this->options->getOption('width') . 'x' .$this->options->getOption('height') . '+0+0';

    }

    /**
     * IF we simply resize we let IM deal with the calculations
     * @return string command section for the resizing.
     */
    protected function generateSimpleSize(): string
    {
        $command = [];
        $command[] = $this->getResizeOperator();
        $command[] = $this->getDimensions();
        return implode(' ', $command);
    }

    /**
     * This works as a cache for calculations
     * @param  string $key       the key with wich we store a calculated value
     * @param  func   $calculate function that returns a calculated value
     * @return string|mixed
     */
    protected function getGeometry($key, $calculate):string
    {
        if(isset($this->geometry[$key])) {
            return $this->geometry[$key];
        }
        $this->geometry[$key] = call_user_func($calculate);

        return $this->geometry[$key];
    }

    protected function getDimensions():string
    {
        return $this->getGeometry('dimensions', function () {
            $targetWidth = $this->options->getOption('width');
            $targetHeight = $this->options->getOption('height');

            $dimensions = '';
            if ($targetWidth) {
                $dimensions .= (string)escapeshellarg($targetWidth);
            }
            if ($targetHeight) {
                $dimensions .= (string)'x'.escapeshellarg($targetHeight);
            }
            return $dimensions;
        });
    }

    protected function getResizeOperator():string
    {
        return $this->getGeometry('resizeOperator', function () {
            return $this->options->getOption('resize') ? '-resize' : '-thumbnail';
        });
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
