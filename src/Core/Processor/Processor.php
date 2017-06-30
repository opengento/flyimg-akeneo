<?php

namespace Core\Processor;

use Core\Entity\Image;
use Core\Exception\AppException;

/**
 * Class Processor
 * @package Core\Service
 */
class Processor
{
    /** Bin path */
    protected const MOZJPEG_COMMAND = '/opt/mozjpeg/bin/cjpeg';
    protected const IM_CONVERT_COMMAND = '/usr/bin/convert';
    protected const IM_IDENTITY_COMMAND = '/usr/bin/identify';
    protected const CWEBP_COMMAND = '/usr/bin/cwebp';

    protected const IM_MOGRIFY_COMMAND = '/usr/bin/mogrify';
    protected const FACEDETECT_COMMAND = '/usr/local/bin/facedetect';

    /** Image options excluded from IM command */
    const EXCLUDED_IM_OPTIONS = ['quality', 'mozjpeg', 'refresh', 'webp-lossless'];

    /**
     * @param string $commandStr
     *
     * @return array
     * @throws \Exception
     */
    protected function execute(string $commandStr): array
    {
        exec($commandStr, $output, $code);
        if (count($output) === 0) {
            $outputError = $code;
        } else {
            $outputError = implode(PHP_EOL, $output);
        }

        if ($code !== 0) {
            throw new AppException(
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
     * @param Image $image
     *
     * @return string
     */
    public function getImageIdentity(Image $image): string
    {
        $output = $this->execute(self::IM_IDENTITY_COMMAND." ".$image->getNewFilePath());

        return !empty($output[0]) ? $output[0] : "";
    }
}
