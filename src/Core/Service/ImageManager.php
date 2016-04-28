<?php

namespace Core\Service;

use League\Flysystem\Filesystem;
use pastuhov\Command\Command;
use Monolog\Logger;


class ImageManager
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var array
     */
    protected $params;

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
        $this->params = $params;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * @param array $options
     * @param $sourceFile
     * @return string
     */
    public function process($options, $sourceFile)
    {
        $options = $this->parseOptions($options);
        $newFileName = md5(implode('.', $options) . $sourceFile);

//        if (!$this->filesystem->has($newFileName)) {
        $this->saveNewFile($sourceFile, $newFileName, $options);
//        }
        return $this->filesystem->read($newFileName);
    }

    /**
     * TODO Parse all options
     * @param $options
     * @return array
     */
    private function parseOptions($options)
    {
        $defaultOptions = $this->params['default_options'];
        $optionsKeys = $this->params['options_keys'];
        $optionsUrl = explode($this->params['options_separator'], $options);
        $options = [];
        foreach ($optionsUrl as $option) {
            $optArray = explode('_', $option);
            if (key_exists($optArray[0], $optionsKeys) && !empty($optionsKeys[$optArray[0]])) {
                $options[$optionsKeys[$optArray[0]]] = $optArray[1];
            }
        }
        return array_merge($defaultOptions, $options);
    }

    /**
     * @param $sourceFile
     * @param $newFileName
     * @param $options
     * @throws \Exception
     */
    private function saveNewFile($sourceFile, $newFileName, $options)
    {
        extract($options);
        $samplingFactor = $options['sampling-factor'];
        $newFilePath = TMP_DIR . $newFileName;

        $tmpFile = $this->saveTmpFile($sourceFile);

        $command = $params = [];
        $command[] = "/usr/bin/convert {tmpFile}";
        $command[] = "-extent {size}";
        $params['tmpFile'] = $tmpFile;
        $params['size'] = $height . 'x' . $width;

        if (!empty($strip)) {
            $command[] = "-strip ";
        }

        if (!empty($gravity)) {
            $command[] = "-gravity {gravity}";
            $params['gravity'] = $gravity;
        }

        if (!empty($thread)) {
            $command[] = "-limit thread {thread}";
            $params['thread'] = $thread;
        }

        if (!empty($samplingFactor)) {
            $command[] = "-sampling-factor {sampling-factor}";
            $params['sampling-factor'] = $samplingFactor;
        }

        if (!empty($unsharp)) {
            $command[] = "-unsharp {unsharp}";
            $params['unsharp'] = $unsharp;
        }

        if (!empty($filter)) {
            $command[] = "-filter {filter}";
            $params['filter'] = $filter;
        }


        if (!empty($scale)) {
            $command[] = "-scale {scale}";
            $params['scale'] = $scale;
        }

        if (!empty($thumbnail)) {
            $command[] = "-thumbnail {thumbnail}";
            $params['thumbnail'] = $thumbnail;
        }

        if (!empty($resize)) {
            $command[] = '-resize {resize}';
            $params['resize'] = $resize;
        }

        if (!empty($crop)) {
            $command[] = '-crop {crop}';
            $params['crop'] = $crop;
        }

        if (!empty($background)) {
            $command[] = '-background {background}';
            $params['background'] = $background;
        }

        if (is_executable($this->params['mozjpeg_path']) && $mozjpeg == 1) {
            $command[] = "TGA:- | {mozJpegExecutable} -quality {quality} -outfile {newFilePath} -targa";
            $params['mozJpegExecutable'] = $this->params['mozjpeg_path'];
        } else {
            $command[] = "-quality {quality} {newFilePath}";
        }

        $params['quality'] = $quality;
        $params['newFilePath'] = $newFilePath;
        $commandStr = implode(' ', $command);
        $this->logger->addInfo('CMD : ' . $commandStr);
        $this->logger->addInfo('PARAMS : ' . implode(', ', $params));
        try {
            Command::exec(
                $commandStr,
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


    /**
     * Save given image in tmp file and return the path
     * @param $fileUrl
     * @return string
     * @throws \Exception
     */
    private function saveTmpFile($fileUrl)
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