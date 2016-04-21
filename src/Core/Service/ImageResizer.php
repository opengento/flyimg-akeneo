<?php

namespace Core\Service;

use League\Flysystem\Filesystem;
use pastuhov\Command\Command;
use Monolog\Logger;


class ImageResizer
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $mozJpegExecutable;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Resizer constructor.
     * @param array $params
     * @param Filesystem $filesystem
     * @param Logger $logger
     */
    public function __construct($params, Filesystem $filesystem, Logger $logger)
    {
        $this->mozJpegExecutable = $params['mozjpeg_path'];
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * @param $sourceFile
     * @param array $options
     * @return string
     */
    public function resize($sourceFile, $options = [])
    {
        $size = $options['width'] . 'x' . $options['height'];
        $unsharp = $options['unsharp'];
        $quality = $options['quality'];
        $newFileName = md5($sourceFile . $size . $unsharp . $quality);

        if (!$this->filesystem->has($newFileName)) {
            $newFilePath = TMP_DIR . $newFileName;

            $tmpFile = $this->saveTmpFile($sourceFile);

            $command = "/usr/bin/convert {tmpFile}'[{size}]' \\
            -strip -limit thread 1 -gravity center -extent {size} \\
            -sampling-factor 1x1 \\
            -unsharp {unsharp} -filter Lanczos ";
            $params = [
                'tmpFile' => $tmpFile,
                'size' => $size,
                'unsharp' => $unsharp,
            ];

            if (is_executable($this->mozJpegExecutable)) {
                $command .= "TGA:- | {mozJpegExecutable} -quality {quality} -outfile {newFilePath} -targa";
                $params['mozJpegExecutable'] = $this->mozJpegExecutable;
            } else {
                $command .= "-quality {quality} {newFilePath}";
            }
            $params['quality'] = $quality;
            $params['newFilePath'] = $newFilePath;
            try {
                Command::exec(
                    $command,
                    $params
                );

                $this->filesystem->write($newFileName, stream_get_contents(fopen($newFilePath, 'r')));
                unlink($tmpFile);
                unlink($newFilePath);

            } catch (\Exception $e) {
                var_dump('Exception: ' . $e->getMessage());
                exit;
            }
        }
        return $this->filesystem->read($newFileName);
    }

    /**
     * Save given image in tmp file and return the path
     * @param $fileUrl
     * @return string
     * @throws \Exception
     */
    public function saveTmpFile($fileUrl)
    {
        if (!$resource = @fopen($fileUrl, "r")) {
            throw  new \Exception('Error occured while trying to read the file Url');
        }
        $content = "";
        while ($line = fread($resource, 1024)) {
            $content .= $line;
        }
        $tmpFile = TMP_DIR . uniqid("", true);
        file_put_contents($tmpFile, $content);
        return $tmpFile;
    }
}