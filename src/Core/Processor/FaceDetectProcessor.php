<?php

namespace Core\Processor;

use Core\Entity\Image;

/**
 * Class FaceDetectProcessor
 * @package Core\Service
 */
class FaceDetectProcessor extends Processor
{
    /**
     * Face detection cropping
     *
     * @param Image $image
     * @param int   $faceCropPosition
     */
    public function cropFaces(Image $image, int $faceCropPosition = 0)
    {
        if (!is_executable(self::FACEDETECT_COMMAND)) {
            return;
        }
        $commandStr = self::FACEDETECT_COMMAND." ".$image->getOriginalFile();
        $output = $this->execute($commandStr);
        if (empty($output[$faceCropPosition])) {
            return;
        }
        $geometry = explode(" ", $output[$faceCropPosition]);
        if (count($geometry) == 4) {
            list($geometryX, $geometryY, $geometryW, $geometryH) = $geometry;
            $cropCmdStr =
                self::IM_CONVERT_COMMAND.
                " '{$image->getOriginalFile()}' -crop {$geometryW}x{$geometryH}+{$geometryX}+{$geometryY} ".
                $image->getOriginalFile();
            $this->execute($cropCmdStr);
        }
    }

    /**
     * Blurring Faces
     *
     * @param Image $image
     */
    public function blurFaces(Image $image)
    {
        if (!is_executable(self::FACEDETECT_COMMAND)) {
            return;
        }
        $commandStr = self::FACEDETECT_COMMAND." ".$image->getOriginalFile();
        $output = $this->execute($commandStr);
        if (empty($output)) {
            return;
        }
        foreach ((array)$output as $outputLine) {
            $geometry = explode(" ", $outputLine);
            if (count($geometry) == 4) {
                list($geometryX, $geometryY, $geometryW, $geometryH) = $geometry;
                $cropCmdStr = self::IM_MOGRIFY_COMMAND.
                    " -gravity NorthWest -region {$geometryW}x{$geometryH}+{$geometryX}+{$geometryY} ".
                    "-scale '10%' -scale '1000%' ".
                    $image->getOriginalFile();
                $this->execute($cropCmdStr);
            }
        }
    }
}
