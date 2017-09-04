<?php

namespace Core\Entity\Image;

use Core\Entity\OptionsBag;
use Core\Exception\ReadFileException;
use Core\Exception\ExecFailedException;

class InputImage
{
    /** @var OptionsBag */
    protected $optionsBag;

    /** @var string */
    protected $sourceImageUrl;

    /** @var string */
    protected $sourceImagePath;

    /** @var string */
    protected $sourceImageMimeType;

    /** @var array Associative array that holds basic image info. Consider in the future create an imageInfo class. */
    protected $imageInfo;

    /**
     * OutputImage constructor.
     *
     * @param OptionsBag $optionsBag
     * @param string     $sourceImageUrl
     */
    public function __construct(OptionsBag $optionsBag, string $sourceImageUrl)
    {
        $this->optionsBag = $optionsBag;
        $this->sourceImageUrl = $sourceImageUrl;

        $this->sourceImagePath = TMP_DIR.'original-'.
            (md5(
                $optionsBag->get('face-crop-position').
                $this->sourceImageUrl
            ));
        $this->saveToTemporaryFile();

        $this->sourceImageMimeType = finfo_file(
            finfo_open(FILEINFO_MIME_TYPE),
            $this->sourceImagePath
        );
    }

    /**
     * Save given image to temporary file and return the path
     *
     * @throws \Exception
     */
    protected function saveToTemporaryFile()
    {
        if (file_exists($this->sourceImagePath) && !$this->optionsBag->get('refresh')) {
            return;
        }

        $opts = [
            'http' =>
                [
                    'method' => 'GET',
                    'max_redirects' => '0',
                ],
        ];
        $context = stream_context_create($opts);

        if (!$stream = @fopen($this->sourceImageUrl, 'r', false, $context)
        ) {
            throw  new ReadFileException(
                'Error occurred while trying to read the file Url : '
                .$this->sourceImageUrl
            );
        }
        $content = stream_get_contents($stream);
        fclose($stream);
        file_put_contents($this->sourceImagePath, $content);
    }

    /**
     * Remove Input Image
     */
    public function removeInputImage()
    {
        if (file_exists($this->getSourceImagePath())) {
            unlink($this->getSourceImagePath());
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function extract(string $key): string
    {
        $value = '';
        if ($this->optionsBag->has($key)) {
            $value = $this->optionsBag->get($key);
            $this->optionsBag->remove($key);
        }

        return is_null($value) ? '' : $value;
    }

    /**
     * @return OptionsBag
     */
    public function getOptionsBag(): OptionsBag
    {
        return $this->optionsBag;
    }

    /**
     * @return string
     */
    public function getSourceImageUrl(): string
    {
        return $this->sourceImageUrl;
    }

    /**
     * @return string
     */
    public function getSourceImagePath(): string
    {
        return $this->sourceImagePath;
    }

    /**
     * @return string
     */
    public function getSourceImageMimeType(): string
    {
        return $this->sourceImageMimeType;
    }

    /**
     * [getImageDimensions description]
     * @return [type] [description]
     */
    public function getImageDimensions()
    {
        $dimensions = $this->getImageInfo();
        $dimensions = explode('x', $dimensions);
        return [
            'width'  => $dimensions[0],
            'height' => $dimensions[1]
        ];
    }

    /**
     * get stored ImageInfo or fetch it and store it
     * @return array Associative array with basic information od the image
     */
    public function getImageInfo(): array
    {
        if(!empty($this->imageInfo)) {
            return $this->imageInfo;
        }

        $this->imageInfo = $this->getImageImIdentify();
        return $this->imageInfo;
    }

    /**
     * Returns an associative array with the info of an image in a given path.
     * @param  string $filePath
     * @return array
     */
    protected function getImageImIdentify(): array
    {
        $imageInfoResponse = $this->execute('/usr/bin/identify ' . $this->getSourceImagePath());
        $imageDetails = $this->parseImageInfoResponse($imageInfoResponse);
        return $imageDetails;
    }

    /**
     * @param string $commandStr
     *
     * @return array
     * @throws \Exception
     */
    public static function execute(string $commandStr): array
    {
        exec($commandStr, $output, $code);
        if (count($output) === 0) {
            $outputError = $code;
        } else {
            $outputError = implode(PHP_EOL, $output);
        }

        if ($code !== 0) {
            throw new ExecFailedException(
                "Command failed. The exit code: ".
                $outputError."<br>The last line of output: ".
                $commandStr
            );
        }

        return $output;
    }

    /**
     * Parses the default output of imagemagik identify command
     * @param  array $output the STDOUT from executing an identify command
     * @return array         associative array with the info in there
     * @throws \Exception
     */
    protected function parseImageInfoResponse($output): array
    {
        if (!is_array($output) || empty($output)) {
            throw new Exception("Image identify failed", 1);
            return [];
        }

        $output = explode(' ', $output[0]);
        return [
            'filePath'     => $output[0],
            'format'       => $output[1],
            'dimensions'   => $output[2],
            'canvas'       => $output[3],
            'colorDepth'   => $output[4],
            'colorProfile' => $output[5],
            'weight'       => $output[6],
        ];
    }
}
