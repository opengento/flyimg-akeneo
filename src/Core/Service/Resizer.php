<?php

namespace Core\Service;


class Resizer
{
    /**
     * @var int
     */
    protected $quality;

    /**
     * @var string
     */
    protected $mozJpegExecutable;

    /**
     * Resizer constructor.
     * @param array $params
     */
    public function __construct($params)
    {
        $this->mozJpegExecutable = $params['mozjpeg_path'];
        $this->quality = $params['quality'];
    }

    /**
     */
    protected function resize($sourceFile, $outputFile, $options = [])
    {
        try {
            $fileInfo = pathinfo($sourceFile);
            $size = $options['size'];
            $unsharp = $options['unsharp'];

            $newFileName = $outputFile . "-" . uniqid("", true);
            $command = "/usr/bin/nice /usr/bin/convert {$sourceFile}'[{$size}]' -strip -limit thread 1 -gravity center -extent {$size} -sampling-factor 1x1 -unsharp {$unsharp} -filter Lanczos ";

            if (is_executable($this->mozJpegExecutable)) {
                $command .= "TGA:- | {$this->mozJpegExecutable} -quality ${$this->quality} -outfile ${newFileName} -targa";
            } else {
                $command .= "-quality {$this->quality} {$newFileName}";
            }

            exec($command);

        } catch (\Exception $e) {
        }
        return true;
    }
}