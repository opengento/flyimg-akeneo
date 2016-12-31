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
     * Save new FileName based on source file and list of options
     *
     * @param $sourceFile
     * @param $newFileName
     * @param $options
     * @throws \Exception
     */
    public function saveNewFile($sourceFile, $newFileName, $options)
    {
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
        $this->filesystem->write($newFileName, stream_get_contents(fopen($newFilePath, 'r')));
        unlink($tmpFile);
        unlink($newFilePath);
    }

    /**
     * Generate Command string bases on options
     *
     * @param $options
     * @param $tmpFile
     * @param $newFilePath
     * @return string
     *
     * TODO: move the geometry logic to it's own function
     */
    public function generateCmdString($newFilePath, $tmpFile, $options)
    {
        $refresh = $this->extractByKey($options, 'refresh');
        $quality = $this->extractByKey($options, 'quality');
        $strip = $this->extractByKey($options, 'strip');
        $mozJPEG = $this->extractByKey($options, 'mozjpeg');
        $thread = $this->extractByKey($options, 'thread');
        $targetWidth = $this->extractByKey($options, 'width');
        $targetHeight = $this->extractByKey($options, 'height');
        $resize = $this->extractByKey($options, 'resize');
        $thumbnail = $this->extractByKey($options, 'thumbnail');
        $crop = $this->extractByKey($options, 'crop');

        // getting the original dimensions is not used so far.
        //$originalDimensions = $this->getImgSize($tmpFile);

        $size = '';

        if ($targetWidth) {
            $size .= (string) $targetWidth;
        }
        if ($targetHeight) {
            $size .= (string) 'x' . $targetHeight;
        }
        
        // When width and height a whole bunch of special cases must be taken into consideration.
        // resizing constraints (< > ^ !) can only be applied to geometry with both width AND height
        $preserveNaturalSize = $this->extractByKey($options, 'preserve-natural-size');
        $preserveAspectRatio = $this->extractByKey($options, 'preserve-aspect-ratio');
        $gravityValue = $this->extractByKey($options, 'gravity');
        $extent = '';
        $gravity = '';
        if($targetWidth && $targetHeight) {
            $extent = ' -extent ' . $size;
            $gravity = ' -gravity ' . $gravityValue;
            $resizingConstraints = '';
            $resizingConstraints .= $preserveNaturalSize ? '\>' : '';
            if($crop) {
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

        // we dafault to thumbnail 
        $resizeOperator = $resize ? 'resize' : 'thumbnail';
        $command = [];
        $command[] = "/usr/bin/convert " . $tmpFile . ' -' . $resizeOperator . ' ' . $size . $gravity . $extent .' -colorspace sRGB';

        if (!empty($thread)) {
            $command[] = "-limit thread " . escapeshellarg($thread);
        }

        // strip is added internally by ImageMagik when using -thumbmail
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

        // if there's a request to refresh, we will assume it's for debugging purposes and we will send back a header with the parsed im command that we are executing.
        if($refresh) {
            header('src-img-size: ' . implode(' x ', $this->getImgSize($tmpFile)));
            header('im-command: ' . $commandStr);
        }

        return $commandStr;
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

    public function getImgSize($imgPath) {
        exec("/usr/bin/convert " . $imgPath . ' -ping -format "%w x %h" info:', $output, $code);
        return explode(' x ', $output[0]);
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
