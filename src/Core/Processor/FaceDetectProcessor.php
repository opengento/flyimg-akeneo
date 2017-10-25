<?php

namespace Core\Processor;

use Core\Entity\Command;
use Core\Entity\Image\InputImage;

/**
 * Class FaceDetectProcessor
 * @package Core\Processor
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
        $faceDetectCmd = new Command(self::FACEDETECT_COMMAND);
        $faceDetectCmd->addArgument($inputImage->sourceImagePath());
        $output = $this->execute($faceDetectCmd);
        if (empty($output[$faceCropPosition])) {
            return;
        }
        $geometry = explode(" ", $output[$faceCropPosition]);
        if (count($geometry) == 4) {
            [$geometryX, $geometryY, $geometryW, $geometryH] = $geometry;
            $cropCmd = new Command(self::IM_CONVERT_COMMAND);
            $cropCmd->addArgument($inputImage->sourceImagePath());
            $cropCmd->addArgument("-crop", "{$geometryW}x{$geometryH}+{$geometryX}+{$geometryY}");
            $cropCmd->addArgument($inputImage->sourceImagePath());
            $this->execute($cropCmd);
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
        $faceDetectCmd = new Command(self::FACEDETECT_COMMAND);
        $faceDetectCmd->addArgument($inputImage->sourceImagePath());
        $output = $this->execute($faceDetectCmd);
        if (empty($output)) {
            return;
        }
        foreach ((array)$output as $outputLine) {
            $geometry = explode(" ", $outputLine);
            if (count($geometry) == 4) {
                [$geometryX, $geometryY, $geometryW, $geometryH] = $geometry;

                $blurCmd = new Command(self::IM_MOGRIFY_COMMAND);
                $blurCmd->addArgument("-gravity", "NorthWest");
                $blurCmd->addArgument("-region", "{$geometryW}x{$geometryH}+{$geometryX}+{$geometryY}");
                $blurCmd->addArgument("-scale", "10%");
                $blurCmd->addArgument("-scale", "1000%");
                $blurCmd->addArgument($inputImage->sourceImagePath());
                $this->execute($blurCmd);
            }
        }
    }
}
