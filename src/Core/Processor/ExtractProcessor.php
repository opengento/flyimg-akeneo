<?php

namespace Core\Processor;

use Core\Entity\Command;
use Core\Entity\Image\InputImage;

/**
 * Class ExtractProcessor
 * @package Core\Processor
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
        $extractCmd = new Command(self::IM_CONVERT_COMMAND);
        $extractCmd->addArgument($inputImage->sourceImagePath());
        $extractCmd->addArgument(" -crop", "{$geometryW}x{$geometryH}+{$topLeftX}+{$topLeftY}");
        $extractCmd->addArgument($inputImage->sourceImagePath());
        $this->execute($extractCmd);
    }
}
