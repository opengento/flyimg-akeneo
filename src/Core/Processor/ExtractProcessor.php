<?php

namespace Core\Processor;

use Core\Entity\Image\InputImage;

/**
 * Class ExtractProcessor
 * @package Core\Service
 */
class ExtractProcessor extends Processor
{
    /**
     * Extract a portion of the image based on coordinates
     *
     * @param InputImage $inputImage
     * @param int        $topLeftX
     * @param int        $topLeftY
     * @param int        $bottomRightX
     * @param int        $bottomRightY
     */
    public function extract(InputImage $inputImage, int $topLeftX, int $topLeftY, int $bottomRightX, int $bottomRightY)
    {
        if (!is_executable(self::IM_CONVERT_COMMAND)) {
            return;
        }

        $geometryW = $bottomRightX - $topLeftX;
        $geometryH = $bottomRightY - $topLeftY;

        $cropCmdStr =
            self::IM_CONVERT_COMMAND.
            " '{$inputImage->getSourceImagePath()}' -crop {$geometryW}'x'{$geometryH}'+'{$topLeftX}'+'{$topLeftY} ".
            $inputImage->getSourceImagePath();
        $this->execute($cropCmdStr);
    }
}
