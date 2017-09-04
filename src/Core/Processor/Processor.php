<?php

namespace Core\Processor;

use Core\Entity\Image\OutputImage;
use Core\Exception\ExecFailedException;

/**
 * Class Processor
 * @package Core\Service
 */
class Processor
{
    /** MozJPEG bin path */
    public const MOZJPEG_COMMAND = '/opt/mozjpeg/bin/cjpeg';

    /** ImageMagick bin path*/
    public const IM_CONVERT_COMMAND = '/usr/bin/convert';
    public const IM_IDENTITY_COMMAND = '/usr/bin/identify';
    public const IM_MOGRIFY_COMMAND = '/usr/bin/mogrify';

    /** CWEBP bin path */
    public const CWEBP_COMMAND = '/usr/bin/cwebp';

    /** FaceDetect bin path */
    public const FACEDETECT_COMMAND = '/usr/local/bin/facedetect';

    /** OutputImage options excluded from IM command */
    const EXCLUDED_IM_OPTIONS = ['quality', 'mozjpeg', 'refresh', 'webp-lossless'];

    /**
     * @param string $commandStr
     *
     * @return array
     * @throws \Exception
     */
    public function execute(string $commandStr): array
    {
        exec($commandStr, $output, $code);
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

        return $output;
    }

    /**
     * Get the image Identity information
     *
     * @param OutputImage $image
     *
     * @return string
     */
    public function getImageIdentity(OutputImage $image): string
    {
        $output = $this->execute(self::IM_IDENTITY_COMMAND." ".$image->getOutputImagePath());

        return !empty($output[0]) ? $output[0] : "";
    }

    /**
     * Get the image Identity information
     *
     * @param OutputImage $image
     *
     * @return string
     */
    public function getSourceImageInfo(OutputImage $image): string
    {
        $output = $this->execute(self::IM_IDENTITY_COMMAND." ".$image->getOutputImagePath());

        return !empty($output[0]) ? $output[0] : "";
    }

    /**
     * Parses the default output of imagemagik identify command
     * @param  array $output the STDOUT from executing an identify command
     * @return array         associative array with the info in there
     */
    protected function parseImageInfoResponse($output): array
    {
        if (!is_array($output) || empty($output)) {
            throw new Exception("Image identify failed", 1);
            return [];
        }

        $output = explode(' ', $output[0]);
        return [
            'filePath'     => $output[0],
            'format'       => $output[1],
            'dimensions'   => $output[2],
            'canvas'       => $output[3],
            'colorDepth'   => $output[4],
            'colorProfile' => $output[5],
            'weight'       => $output[6],
        ];
    }
}
