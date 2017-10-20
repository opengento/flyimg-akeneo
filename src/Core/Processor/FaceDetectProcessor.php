<?php

namespace Core\Processor;

use Core\Entity\Image\InputImage;

/**
 * Class FaceDetectProcessor
 * @package Core\Service
 */
class FaceDetectProcessor extends Processor
{
    /**
     * Face detection cropping
     *
     * @param InputImage $inputImage
     * @param int        $faceCropPosition
     */
    public function cropFaces(InputImage $inputImage, int $faceCropPosition = 0)
    {
        if (!is_executable(self::FACEDETECT_COMMAND)) {
            return;
        }
        $commandStr = self::FACEDETECT_COMMAND." ".$inputImage->getSourceImagePath();
        $output = $this->execute($commandStr);
        if (empty($output[$faceCropPosition])) {
            return;
        }
        $geometry = explode(" ", $output[$faceCropPosition]);
        if (count($geometry) == 4) {
            [$geometryX, $geometryY, $geometryW, $geometryH] = $geometry;
            $cropCmdStr =
                self::IM_CONVERT_COMMAND.
                " '{$inputImage->getSourceImagePath()}' -crop {$geometryW}x{$geometryH}+{$geometryX}+{$geometryY} ".
                $inputImage->getSourceImagePath();
            $this->execute($cropCmdStr);
        }
    }

    /**
     * Blurring Faces
     *
     * @param InputImage $inputImage
     */
    public function blurFaces(InputImage $inputImage)
    {
        if (!is_executable(self::FACEDETECT_COMMAND)) {
            return;
        }
        $commandStr = self::FACEDETECT_COMMAND." ".$inputImage->getSourceImagePath();
        $output = $this->execute($commandStr);
        if (empty($output)) {
            return;
        }
        foreach ((array)$output as $outputLine) {
            $geometry = explode(" ", $outputLine);
            if (count($geometry) == 4) {
                [$geometryX, $geometryY, $geometryW, $geometryH] = $geometry;
                $cropCmdStr = self::IM_MOGRIFY_COMMAND.
                    " -gravity NorthWest -region {$geometryW}x{$geometryH}+{$geometryX}+{$geometryY} ".
                    "-scale '10%' -scale '1000%' ".
                    $inputImage->getSourceImagePath();
                $this->execute($cropCmdStr);
            }
        }
    }
}
