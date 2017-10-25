<?php

namespace Core\Processor;

use Core\Entity\Command;
use Core\Entity\Image\OutputImage;
use Core\Entity\ImageMetaInfo;

/**
 * Class ImageProcessor
 * @package Core\Processor
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
     * Basic source image info parsed from IM identify command
     * @var ImageMetaInfo
     */
    protected $sourceImageInfo;

    /**
     * OptionsBag from the request
     * @var \Core\Entity\OptionsBag
     */
    protected $options;

    /**
     * stores/caches image related data like dimensions
     * @var array
     */
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
        $this->sourceImageInfo = $outputImage->getInputImage()->sourceImageInfo();
        $this->options = $outputImage->getInputImage()->optionsBag();
        $command = $this->generateCmdString($outputImage);
        $this->execute($command);

        return $outputImage;
    }

    /**
     * Generate Command string bases on options
     *
     * @param OutputImage $outputImage
     *
     * @return Command
     */
    public function generateCmdString(OutputImage $outputImage): Command
    {
        // we will categorize the operation in this method and call the adecuate functions depending on the parameters
        // first we get the data we need
        $width = $this->options->getOption('width');
        $height = $this->options->getOption('height');
        $crop = $this->options->getOption('crop');

        $command = new Command(self::IM_CONVERT_COMMAND);

        // if width AND height AND crop are defined we need check further to define the type of operation we will do
        $size = '';
        if ($width && $height && $crop) {
            $size = $this->generateCropSize();
        } elseif ($width || $height) {
            $size = $this->generateSimpleSize();
        }

        if ($outputImage->isInputGif()) {
            $command->addArgument('-coalesce');
        }

        $command->addArgument($this->getSourceImagePath($outputImage));
        $command->addArgument($size);
        $command->addArgument(' -colorspace sRGB');

        //Rotate option
        if (!empty($this->options->getOption('rotate'))) {
            $command->addArgument("-rotate ".escapeshellarg($this->options->getOption('rotate')));
        }

        // strip is added internally by ImageMagick when using -thumbnail
        if (!empty($outputImage->extractKey('strip'))) {
            $command->addArgument("-strip");
        }

        if (!empty($outputImage->extractKey('thread'))) {
            $command->addArgument("-limit thread ".escapeshellarg($outputImage->extractKey('thread')));
        }

        $command->addArgument($this->calculateQuality($outputImage));

        $outputImage->setCommandString($command);

        return $command;
    }

    /**
     * IF we crop we need to know if the source image is bigger or smaller than the target size.
     * @return string command section for the resizing.
     *
     * note: The shorthand version of resize to fill space will always fill the space even if image is bigger
     */
    protected function generateCropSize(): string
    {
        $this->updateTargetDimensions();
        $command = [];
        $command[] = $this->getResizeOperator();
        $command[] = $this->getDimensions().'^';
        $command[] = '-gravity '.$this->options->getOption('gravity');
        $command[] = '-extent '.$this->getDimensions();

        return implode(' ', $command);
    }

    /**
     * IF we simply resize we let IM deal with the calculations
     * @return string command section for the resizing.
     */
    protected function generateSimpleSize(): string
    {
        $command = [];
        $command[] = $this->getResizeOperator();
        $command[] = $this->getDimensions().
            ($this->options->getOption('preserve-natural-size') ? escapeshellarg('>') : '');

        return implode(' ', $command);
    }

    /**
     * Gets the source image path and adds any extra modifiers to the string
     *
     * @param  OutputImage $outputImage
     *
     * @return string                   Path of the source file to be used in the conversion command
     */
    protected function getSourceImagePath(OutputImage $outputImage): string
    {
        $tmpFileName = $this->sourceImageInfo->path();

        //Check the source image is gif
        if ($outputImage->isInputGif()) {
            $frame = $this->options->getOption('gif-frame');

            // set the frame if the output image is not gif (to get ony one  frame)
            if ($outputImage->getOutputImageExtension() !== OutputImage::EXT_GIF) {
                $tmpFileName .= '['.escapeshellarg($frame).']';
            }
        }

        return $tmpFileName;
    }

    /**
     * Apply the Quality processor based on options
     *
     * @param OutputImage $outputImage
     *
     * @return string
     */
    protected function calculateQuality(OutputImage $outputImage): string
    {
        $quality = $outputImage->extractKey('quality');
        $parameter = '';

        /** WebP format */
        if (is_executable(self::CWEBP_COMMAND) && $outputImage->isOutputWebP()) {
            $lossLess = $outputImage->extractKey('webp-lossless') ? 'true' : 'false';
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
     * This works as a cache for calculations
     *
     * @param  string   $key       the key with wich we store a calculated value
     * @param  callback $calculate function that returns a calculated value
     *
     * @return string|mixed
     */
    protected function getGeometry($key, $calculate): string
    {
        if (isset($this->geometry[$key])) {
            return $this->geometry[$key];
        }
        $this->geometry[$key] = call_user_func($calculate);

        return $this->geometry[$key];
    }

    /**
     * @return string
     */
    protected function getDimensions(): string
    {
        return $this->getGeometry(
            'dimensions',
            function () {
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
            }
        );
    }

    /**
     * @return string
     */
    protected function getResizeOperator(): string
    {
        return $this->getGeometry(
            'resizeOperator',
            function () {
                return $this->options->getOption('resize') ? '-resize' : '-thumbnail';
            }
        );
    }

    /**
     *
     */
    protected function updateTargetDimensions(): void
    {
        if (!$this->options->getOption('preserve-natural-size')) {
            return;
        }

        $targetWidth = $this->options->getOption('width');
        $targetHeight = $this->options->getOption('height');
        $originalWidth = $this->sourceImageInfo->dimensions()['width'];
        $originalHeight = $this->sourceImageInfo->dimensions()['height'];

        if ($originalWidth < $targetWidth) {
            $this->options->setOption('width', $originalWidth);
        }

        if ($originalHeight < $targetHeight) {
            $this->options->setOption('height', $originalHeight);
        }
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
        $targetWidth = $outputImage->extractKey('width');
        $targetHeight = $outputImage->extractKey('height');

        $size = $extent = '';
        if ($targetWidth) {
            $size .= (string)escapeshellarg($targetWidth);
        }
        if ($targetHeight) {
            $size .= (string)'x'.escapeshellarg($targetHeight);
        }

        // When width and height a whole bunch of special cases must be taken into consideration.
        // resizing constraints (< > ^ !) can only be applied to geometry with both width AND height
        $preserveNaturalSize = $outputImage->extractKey('preserve-natural-size');
        $preserveAspectRatio = $outputImage->extractKey('preserve-aspect-ratio');

        if ($targetWidth && $targetHeight) {
            if ($preserveNaturalSize) {
                // here we will compare source image dimensions to target dimensions and adjust
            }
            $gravity = ' -gravity '.escapeshellarg($outputImage->extractKey('gravity'));
            $resizingConstraints = '';
            if ($outputImage->extractKey('crop')) {
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
}
