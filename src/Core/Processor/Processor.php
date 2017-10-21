<?php

namespace Core\Processor;

use Core\Entity\Image\OutputImage;
use Core\Exception\ExecFailedException;

/**
 * Class Processor
 * @package Core\Processor
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
    public function imageIdentityInformation(OutputImage $image): string
    {
        $output = $this->execute(self::IM_IDENTITY_COMMAND." ".$image->outputImagePath());

        return !empty($output[0]) ? $output[0] : "";
    }
}
