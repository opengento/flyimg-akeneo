<?php

namespace Core\Service;

use League\Flysystem\Filesystem;

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
     * ImageManager constructor.
     *
     * @param array $params
     * @param Filesystem $filesystem
     */
    public function __construct($params, Filesystem $filesystem)
    {
        $this->params = $params;
        $this->filesystem = $filesystem;
    }

    /**
     * Process give source file with given options
     *
     * @param array $options
     * @param $sourceFile
     * @return string
     * @throws \Exception
     */
    public function process($options, $sourceFile)
    {
        //check restricted_domains is enabled
        if ($this->params['restricted_domains'] &&
            is_array($this->params['whitelist_domains']) &&
            !in_array(parse_url($sourceFile, PHP_URL_HOST), $this->params['whitelist_domains'])
        ) {
            throw  new \Exception('Restricted domains enabled, the domain your fetching from is not allowed: ' . parse_url($sourceFile, PHP_URL_HOST));

        }

        $options = $this->parseOptions($options);
        $newFileName = md5(implode('.', $options) . $sourceFile);

        if ($this->filesystem->has($newFileName) && $options['refresh']) {
            $this->filesystem->delete($newFileName);
        }
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
    public function parseOptions($options)
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
     * Extract a value from given array and unset it.
     *
     * @param $array
     * @param $key
     * @return null
     */
    public function extractByKey(&$array, $key)
    {
        $value = null;
        if (isset($array[$key])) {
            $value = $array[$key];
            unset($array[$key]);
        }
        return $value;
    }

    /**
     * Save new FileName based on source file and list of options
     *
     * @param $sourceFile
     * @param $newFileName
     * @param $options
     * @throws \Exception
     */
    public function saveNewFile($sourceFile, $newFileName, $options)
    {
        $refresh = $this->extractByKey($options, 'refresh');
        $faceCrop = $this->extractByKey($options, 'face-crop');
        $faceCropPosition = $this->extractByKey($options, 'face-crop-position');

        $newFilePath = TMP_DIR . $newFileName;

        $tmpFile = $this->saveToTemporaryFile($sourceFile);
        $commandStr = $this->generateCmdString($newFilePath, $tmpFile, $options);

        exec($commandStr, $output, $code);
        if (count($output) === 0) {
            $output = $code;
        } else {
            $output = implode(PHP_EOL, $output);
        }

        if ($code !== 0) {
            throw new \Exception("Command failed. The exit code: " . $output . "<br>The last line of output: " . $commandStr);
        }
        //Add Debug Header in case refresh option = 1
        if ($refresh) {
            $this->sendDebugHeader($commandStr, $newFilePath);
        }

        if ($faceCrop) {
            $newFilePath = $this->generateFaceCrop($newFilePath, $faceCropPosition);
        }

        $this->filesystem->write($newFileName, stream_get_contents(fopen($newFilePath, 'r')));
        unlink($tmpFile);
        unlink($newFilePath);
    }

    /**
     * Face detection cropping
     * 
     * @param string $newFilePath
     * @param int $faceCropPosition
     * @return string
     */
    public function generateFaceCrop($newFilePath, $faceCropPosition = 0)
    {
        $commandStr = "facedetect '$newFilePath'";
        exec($commandStr, $output, $code);
        if (!empty($output[$faceCropPosition]) && $code == 0) {
            $positions = explode(" ", $output[$faceCropPosition]);
            if (count($positions) == 4) {
                list($x, $y, $w, $h) = $positions;
                $cropCmdStr = "/usr/bin/convert '$newFilePath' -crop ${w}x${h}+${x}+${y} $newFilePath";
                exec($cropCmdStr, $output, $code);
            }
        }
        return $newFilePath;
    }

    /**
     * Generate Command string bases on options
     *
     * @param $options
     * @param $tmpFile
     * @param $newFilePath
     * @return string
     */
    public function generateCmdString($newFilePath, $tmpFile, $options)
    {
        $strip = $this->extractByKey($options, 'strip');
        $thread = $this->extractByKey($options, 'thread');
        $resize = $this->extractByKey($options, 'resize');
        $quality = $this->extractByKey($options, 'quality');
        $mozJPEG = $this->extractByKey($options, 'mozjpeg');


        list($size, $extent, $gravity) = $this->generateSize($options);

        // we default to thumbnail
        $resizeOperator = $resize ? 'resize' : 'thumbnail';
        $command = [];
        $command[] = "/usr/bin/convert " . $tmpFile . ' -' . $resizeOperator . ' ' . $size . $gravity . $extent . ' -colorspace sRGB';

        if (!empty($thread)) {
            $command[] = "-limit thread " . escapeshellarg($thread);
        }

        // strip is added internally by ImageMagick when using -thumbnail
        if (!empty($strip)) {
            $command[] = "-strip ";
        }

        foreach ($options as $key => $value) {
            if (!empty($value)) {
                $command[] = "-{$key} " . escapeshellarg($value);
            }
        }

        $command = $this->checkMozJpeg($command, $newFilePath, $quality, $mozJPEG);

        $commandStr = implode(' ', $command);

        return $commandStr;
    }

    /**
     * Size and Crop logic
     *
     * @param $options
     * @return array
     */
    private function generateSize(&$options)
    {
        $targetWidth = $this->extractByKey($options, 'width');
        $targetHeight = $this->extractByKey($options, 'height');
        $crop = $this->extractByKey($options, 'crop');

        $size = '';

        if ($targetWidth) {
            $size .= (string)$targetWidth;
        }
        if ($targetHeight) {
            $size .= (string)'x' . $targetHeight;
        }

        // When width and height a whole bunch of special cases must be taken into consideration.
        // resizing constraints (< > ^ !) can only be applied to geometry with both width AND height
        $preserveNaturalSize = $this->extractByKey($options, 'preserve-natural-size');
        $preserveAspectRatio = $this->extractByKey($options, 'preserve-aspect-ratio');
        $gravityValue = $this->extractByKey($options, 'gravity');
        $extent = '';
        $gravity = '';

        if ($targetWidth && $targetHeight) {
            $extent = ' -extent ' . $size;
            $gravity = ' -gravity ' . $gravityValue;
            $resizingConstraints = '';
            $resizingConstraints .= $preserveNaturalSize ? '\>' : '';
            if ($crop) {
                $resizingConstraints .= '^';
                //$extent .= '+repage';// still need to solve the combination of ^ , -extent and +repage . Will need to do calculations with the original image dimentions vs. the target dimentions.
            } else {
                $extent .= '+repage ';
            }
            $resizingConstraints .= $preserveAspectRatio ? '' : '!';
            $size .= $resizingConstraints;
        } else {
            $size .= $preserveNaturalSize ? '\>' : '';
        }

        return [$size, $extent, $gravity];
    }


    /**
     * Check MozJpeg configuration if it's enabled and append it to main convert command
     *
     * @param $command
     * @param $newFilePath
     * @param $quality
     * @param $mozJPEG
     * @return array
     */
    private function checkMozJpeg($command, $newFilePath, $quality, $mozJPEG)
    {
        if (is_executable($this->params['mozjpeg_path']) && $mozJPEG == 1) {
            $command[] = "TGA:- | " . escapeshellarg($this->params['mozjpeg_path']) . " -quality " . escapeshellarg($quality) . " -outfile " . escapeshellarg($newFilePath) . " -targa";
        } else {
            $command[] = "-quality " . escapeshellarg($quality) . " " . escapeshellarg($newFilePath);
        }
        return $command;
    }

    /**
     * Save given image to temporary file and return the path
     *
     * @param $fileUrl
     * @return string
     * @throws \Exception
     */
    public function saveToTemporaryFile($fileUrl)
    {
        if (!$resource = @fopen($fileUrl, "r")) {
            throw  new \Exception('Error occurred while trying to read the file Url : ' . $fileUrl);
        }
        $content = "";
        while ($line = fread($resource, 1024)) {
            $content .= $line;
        }
        $tmpFile = TMP_DIR . uniqid("", true);
        file_put_contents($tmpFile, $content);
        return $tmpFile;
    }

    /**
     * If there's a request to refresh,
     * We will assume it's for debugging purposes and we will send back a header with the parsed im command that we are executing.
     *
     * @param $commandStr
     * @param $tmpFile
     */
    private function sendDebugHeader($commandStr, $tmpFile)
    {
        header('im-identify: ' . $this->getImgSize($tmpFile));
        header('im-command: ' . $commandStr);
    }

    /**
     * Get the image size
     *
     * @param $imgPath
     * @return string
     */
    public function getImgSize($imgPath)
    {
        exec('/usr/bin/identify ' . $imgPath, $output);
        return !empty($output[0]) ? $output[0] : "";
    }
}
