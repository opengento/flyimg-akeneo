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
    protected $rootDir;

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
        $this->rootDir = $params['root_dir'];
    }

    /**
     * @param $sourceFile
     * @param array $options
     * @return string
     */
    public function resize($sourceFile, $options = [])
    {
        try {
            $tmpFile =  $this->rootDir . '/var/tmp/'. uniqid("", true);
            copy($sourceFile, $tmpFile);
            $size = $options['size'];
            $unsharp = $options['unsharp'];

            $newFileName = $this->rootDir . '/var/tmp/' . time() . '-' . uniqid("", true);
            $command = "/usr/bin/nice /usr/bin/convert {$tmpFile}'[{$size}]' -strip -limit thread 1 -gravity center -extent {$size} -sampling-factor 1x1 -unsharp {$unsharp} -filter Lanczos ";

            if (is_executable($this->mozJpegExecutable)) {
                $command .= "TGA:- | {$this->mozJpegExecutable} -quality {$this->quality} -outfile ${newFileName} -targa";
            } else {
                $command .= "-quality {$this->quality} {$newFileName}";
            }
            exec($command);

        } catch (\Exception $e) {
        }
        return $newFileName;
    }
}