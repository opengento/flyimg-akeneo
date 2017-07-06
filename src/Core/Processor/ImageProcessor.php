<?php

namespace Core\Processor;

use Core\Entity\Image;

/**
 * Class ImageProcessor
 * @package Core\Service
 */
class ImageProcessor extends Processor
{
    /**
     * Save new FileName based on source file and list of options
     *
     * @param Image $image
     *
     * @return Image
     * @throws \Exception
     */
    public function processNewImage(Image $image): Image
    {
        $this->generateCmdString($image);
        $this->execute($image->getCommandString());

        return $image;
    }

    /**
     * Generate Command string bases on options
     *
     * @param Image $image
     */
    public function generateCmdString(Image $image)
    {
        $strip = $image->extract('strip');
        $thread = $image->extract('thread');
        $resize = $image->extract('resize');
        $frame = $image->extract('gif-frame');

        list($size, $extent, $gravity) = $this->generateSize($image);

        // we default to thumbnail
        $resizeOperator = $resize ? 'resize' : 'thumbnail';
        $command = [];
        $command[] = self::IM_CONVERT_COMMAND;
        $tmpFileName = $image->getOriginalFile();

        //Check the image is gif
        if ($image->isGifSupport()) {
            $command[] = '-coalesce';
            if ($image->getOutputExtension() != Image::EXT_GIF) {
                $tmpFileName .= '['.escapeshellarg($frame).']';
            }
        }

        $command[] = " ".$tmpFileName;
        $command[] = ' -'.$resizeOperator.' '.
            $size.$gravity.$extent.
            ' -colorspace sRGB';

        foreach ($image->getOptions() as $key => $value) {
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

        $command = $this->applyQuality($image, $command);

        $commandStr = implode(' ', $command);
        $image->setCommandString($commandStr);
    }

    /**
     * Apply the Quality processor based on options
     *
     * @param Image $image
     * @param array $command
     *
     * @return array
     */
    protected function applyQuality(Image $image, array $command): array
    {
        $quality = $image->extract('quality');
        /** WebP format */
        if (is_executable(self::CWEBP_COMMAND) && $image->isWebPSupport()) {
            $lossLess = $image->extract('webp-lossless') ? 'true' : 'false';
            $command[] = "-quality ".escapeshellarg($quality).
                " -define webp:lossless=".$lossLess." ".escapeshellarg($image->getNewFilePath());
        } /** MozJpeg compression */
        elseif (is_executable(self::MOZJPEG_COMMAND) && $image->isMozJpegSupport()) {
            $command[] = "TGA:- | ".escapeshellarg(self::MOZJPEG_COMMAND)
                ." -quality ".escapeshellarg($quality)
                ." -outfile ".escapeshellarg($image->getNewFilePath())
                ." -targa";
        } /** default ImageMagick compression */
        else {
            $command[] = "-quality ".escapeshellarg($quality).
                " ".escapeshellarg($image->getNewFilePath());
        }

        return $command;
    }

    /**
     * Size and Crop logic
     *
     * @param Image $image
     *
     * @return array
     */
    protected function generateSize(Image $image): array
    {
        $targetWidth = $image->extract('width');
        $targetHeight = $image->extract('height');

        $size = $extent = '';
        if ($targetWidth) {
            $size .= (string)escapeshellarg($targetWidth);
        }
        if ($targetHeight) {
            $size .= (string)'x'.escapeshellarg($targetHeight);
        }

        // When width and height a whole bunch of special cases must be taken into consideration.
        // resizing constraints (< > ^ !) can only be applied to geometry with both width AND height
        $preserveNaturalSize = $image->extract('preserve-natural-size');
        $preserveAspectRatio = $image->extract('preserve-aspect-ratio');

        if ($targetWidth && $targetHeight) {
            $extent = ' -extent '.$size;
            $gravity = ' -gravity '.escapeshellarg($image->extract('gravity'));
            $resizingConstraints = '';
            if ($image->extract('crop')) {
                $resizingConstraints .= '^';
                /**
                 * still need to solve the combination of ^
                 * -extent and +repage . Will need to do calculations with the
                 * original image dimensions vs. the target dimensions.
                 */
            } else {
                $extent .= '+repage ';
            }
            $resizingConstraints .= $preserveAspectRatio ? '' : '!';
            $size .= $resizingConstraints;
        } else {
            $size .= $preserveNaturalSize ? '\>' : '';
            $gravity = '';
        }
        //In cas on png format, remove extent option
        if ($image->isPngSupport()) {
            $extent = '';
        }

        return [$size, $extent, $gravity];
    }
}
