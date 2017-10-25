<?php

namespace Core\Entity\Image;

use Core\Entity\OptionsBag;
use Core\Exception\ReadFileException;
use Core\Entity\ImageMetaInfo;

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

    /** @var ImageMetaInfo */
    protected $sourceImageInfo;

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
        $this->sourceImageInfo = new ImageMetaInfo($this->sourceImagePath);
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
        if (file_exists($this->sourceImagePath())) {
            unlink($this->sourceImagePath());
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function extractKey(string $key): string
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
    public function optionsBag(): OptionsBag
    {
        return $this->optionsBag;
    }

    /**
     * @return string
     */
    public function sourceImageUrl(): string
    {
        return $this->sourceImageUrl;
    }

    /**
     * @return string
     */
    public function sourceImagePath(): string
    {
        return $this->sourceImagePath;
    }

    /**
     * @return string
     */
    public function sourceImageMimeType(): string
    {
        if (isset($this->sourceImageMimeType)) {
            return $this->sourceImageMimeType;
        }

        $this->sourceImageMimeType = $this->sourceImageInfo->mimeType();
        return $this->sourceImageMimeType;
    }

    public function sourceImageInfo()
    {
        return $this->sourceImageInfo;
    }
}
