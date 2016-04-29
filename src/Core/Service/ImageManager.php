<?php

namespace Core\Service;

use League\Flysystem\Filesystem;
use Monolog\Logger;

/**
 * Class ImageManager
 * @package Core\Service
 */
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
     * ImageManager constructor.
     *
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
     * Process give source file with given options
     *
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
     * Parse options: match options keys and merge default options with given ones
     *
     * @param $options
     * @return array
     */
    private function parseOptions($options)
    {
        $defaultOptions = $this->params['default_options'];
        $optionsKeys = $this->params['options_keys'];
        $optionsSeparator = !empty($this->params['options_separator']) ? $this->params['options_separator'] : ',';
        $optionsUrl = explode($optionsSeparator, $options);
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
     * Save new FileName based on source file and list of options
     *
     * @param $sourceFile
     * @param $newFileName
     * @param $options
     * @throws \Exception
     */
    private function saveNewFile($sourceFile, $newFileName, $options)
    {
        $newFilePath = TMP_DIR . $newFileName;
        $tmpFile = $this->saveToTemporaryFile($sourceFile);
        $commandStr = $this->generateCmdString($newFilePath, $tmpFile, $options);
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
            $this->logger->addInfo('CMD outout : ' . $output);
            $this->filesystem->write($newFileName, stream_get_contents(fopen($newFilePath, 'r')));
            unlink($tmpFile);
            unlink($newFilePath);

        } catch (\Exception $e) {
            var_dump('Exception: ' . $e->getMessage());
            exit;
        }
    }

    /**
     * Generate Command string bases on options
     *
     * @param $options
     * @param $tmpFile
     * @param $newFilePath
     * @return string
     */
    private function generateCmdString($newFilePath, $tmpFile, $options)
    {
        $quality = $this->extractByKey($options, 'quality');
        $strip = $this->extractByKey($options, 'strip');
        $mozJPEG = $this->extractByKey($options, 'mozjpeg');
        $thread = $this->extractByKey($options, 'thread');
        $size = $this->extractByKey($options, 'width') . 'x' . $this->extractByKey($options, 'height');

        $command = [];
        $command[] = "/usr/bin/convert " . $tmpFile . "'[" . escapeshellarg($size) . "]'";
        $command[] = "-extent " . escapeshellarg($size);

        if (!empty($thread)) {
            $command[] = "-limit thread " . escapeshellarg($thread);
        }

        if (!empty($strip)) {
            $command[] = "-strip ";
        }

        foreach ($options as $key => $value) {
            if (!empty($value)) {
                $command[] = "-{$key} " . escapeshellarg($value);
            }
        }

        if (is_executable($this->params['mozjpeg_path']) && $mozJPEG == 1) {
            $command[] = "TGA:- | " . escapeshellarg($this->params['mozjpeg_path']) . " -quality " . escapeshellarg($quality) . " -outfile " . escapeshellarg($newFilePath) . " -targa";
        } else {
            $command[] = "-quality " . escapeshellarg($quality) . " " . escapeshellarg($newFilePath);
        }

        $commandStr = implode(' ', $command);
        return $commandStr;
    }

    /**
     * Extract a value from given array and unset it.
     *
     * @param $array
     * @param $key
     * @return null
     */
    private function extractByKey(&$array, $key)
    {
        $value = null;
        if (isset($array[$key])) {
            $value = $array[$key];
            unset($array[$key]);
        }
        return $value;
    }

    /**
     * Save given image to temporary file and return the path
     *
     * @param $fileUrl
     * @return string
     * @throws \Exception
     */
    private function saveToTemporaryFile($fileUrl)
    {
        if (!$resource = @fopen($fileUrl, "r")) {
            throw  new \Exception('Error occured while trying to read the file Url : ' . $fileUrl);
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