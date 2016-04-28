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

        if (!$this->filesystem->has($newFileName)) {
            $this->saveNewFile($sourceFile, $newFileName, $options);
        }
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
        $command = [];

        $quality = $options['quality'];
        $strip = $options['strip'];
        $mozJPEG = $options['mozjpeg'];
        $thread = $options['thread'];

        $newFilePath = TMP_DIR . $newFileName;
        $tmpFile = $this->saveTmpFile($sourceFile);

        $size = $options['width'] . 'x' . $options['height'];
        $command[] = "/usr/bin/convert ".$tmpFile."'[".escapeshellarg($size)."]'";
        $command[] = "-extent ".escapeshellarg($size);

        unset(
            $options['width'],
            $options['height'],
            $options['quality'],
            $options['mozjpeg'],
            $options['thread'],
            $options['strip']
        );

        if (!empty($thread)) {
            $command[] = "-limit thread ". escapeshellarg($thread);
        }

        if (!empty($strip)) {
            $command[] = "-strip ";
        }

        foreach ($options as $key => $value) {
            if (!empty($value)) {
                $command[] = "-{$key} ". escapeshellarg($value);
            }
        }

        if (is_executable($this->params['mozjpeg_path']) && $mozJPEG == 1) {
            $command[] = "TGA:- | ".escapeshellarg($this->params['mozjpeg_path'])." -quality ".escapeshellarg($quality)." -outfile ".escapeshellarg($newFilePath)." -targa";
        } else {
            $command[] = "-quality ".escapeshellarg($quality)." ".escapeshellarg($newFilePath);
        }

        $commandStr = implode(' ', $command);
        $this->logger->addInfo('CMD : ' . $commandStr);
        try {

            exec($commandStr, $output, $code);
            if (count($output) === 0) {
                $output = $code;
            } else {
                $output = implode(PHP_EOL, $output);
            }

            if ($code !== 0) {
                throw new \Exception($output . ' Command line: ' . $commandStr);
            }

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