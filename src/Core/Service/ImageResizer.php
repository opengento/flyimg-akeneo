<?php

namespace Core\Service;


class ImageResizer
{
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
        $this->rootDir = $params['root_dir'];
    }

    /**
     * @param $sourceFile
     * @param array $options
     * @return string
     */
    public function resize($sourceFile, $options = [])
    {
        $newFileName = null;
        try {
            $tmpFile = $this->rootDir . '/var/tmp/' . uniqid("", true);
            file_put_contents($tmpFile, file_get_contents($sourceFile));
            $size = $options['width'] . 'x' . $options['height'];
            $unsharp = $options['unsharp'];
            $quality = $options['quality'];

            $newFileName = $this->rootDir . '/var/tmp/' . time() . '-' . uniqid("", true) . '.jpeg';
            $command = "/usr/bin/nice /usr/bin/convert {$tmpFile}'[{$size}]' -strip -limit thread 1 -gravity center -extent {$size} -sampling-factor 1x1 -unsharp {$unsharp} -filter Lanczos ";

            if (is_executable($this->mozJpegExecutable)) {
                $command .= "TGA:- | {$this->mozJpegExecutable} -quality {$quality} -outfile ${newFileName} -targa";
            } else {
                $command .= "-quality {$quality} {$newFileName}";
            }
            exec($command);
            unlink($tmpFile);

        } catch (\Exception $e) {
        }
        return $newFileName;
    }
}