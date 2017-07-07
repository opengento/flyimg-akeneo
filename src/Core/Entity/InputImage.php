<?php

namespace Core\Entity;

use Core\Exception\ReadFileException;

class InputImage
{
    /** @var array */
    protected $options = [];

    /** @var string */
    protected $sourceImageUrl;

    /** @var string */
    protected $sourceImagePath;

    /** @var string */
    protected $sourceImageMimeType;

    /**
     * OutputImage constructor.
     *
     * @param array  $options
     * @param string $sourceImageUrl
     */
    public function __construct(array $options, string $sourceImageUrl)
    {
        $this->options = $options;
        $this->sourceImageUrl = $sourceImageUrl;

        $this->sourceImagePath = TMP_DIR.'original-'.(md5($options['face-crop-position'].$this->sourceImageUrl));
        $this->saveToTemporaryFile();
        $this->sourceImageMimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->sourceImagePath);
    }

    /**
     * Save given image to temporary file and return the path
     *
     * @throws \Exception
     */
    protected function saveToTemporaryFile()
    {
        if (file_exists($this->sourceImagePath) && !$this->options['refresh']) {
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
        if (isset($this->options[$key])) {
            $value = $this->options[$key];
            unset($this->options[$key]);
        }

        return $value;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
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
}
