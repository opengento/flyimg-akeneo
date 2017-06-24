<?php

namespace Core\Entity;

use Core\Exception\InvalidArgumentException;
use Core\Exception\ReadFileException;
use Core\Traits\ParserTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Image
 * @package Core\Entity
 */
class Image
{
    use ParserTrait;

    /** Content TYPE */
    const WEBP_MIME_TYPE = 'image/webp';
    const JPEG_MIME_TYPE = 'image/jpeg';
    const PNG_MIME_TYPE = 'image/png';
    const GIF_MIME_TYPE = 'image/gif';

    /** Extension output */
    const EXT_AUTO = 'auto';
    const EXT_PNG = 'png';
    const EXT_WEBP = 'webp';
    const EXT_JPG = 'jpg';
    const EXT_GIF = 'gif';

    /** @var array */
    protected $options = [];

    /** @var string */
    protected $sourceFile;

    /** @var string */
    protected $newFileName;

    /** @var string */
    protected $newFilePath;

    /** @var string */
    protected $temporaryFile;

    /** @var string */
    protected $sourceMimeType;

    /** @var string */
    protected $outputExtension;

    /** @var string */
    protected $commandString;

    /** @var array */
    protected $defaultParams;

    /** @var string */
    protected $content;

    /**
     * Image constructor.
     *
     * @param array  $options
     * @param string $sourceFile
     */
    public function __construct(array $options, string $sourceFile)
    {
        $this->options = $options;
        $this->sourceFile = $sourceFile;

        $this->saveToTemporaryFile();
        $this->generateFilesName();
        $this->generateFileExtension();
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    /**
     * @param string $sourceFile
     */
    public function setSourceFile(string $sourceFile)
    {
        $this->sourceFile = $sourceFile;
    }

    /**
     * @return string
     */
    public function getNewFileName(): string
    {
        return $this->newFileName;
    }

    /**
     * @param string $newFileName
     */
    public function setNewFileName(string $newFileName)
    {
        $this->newFileName = $newFileName;
    }

    /**
     * @return string
     */
    public function getNewFilePath(): string
    {
        return $this->newFilePath;
    }

    /**
     * @param string $newFilePath
     */
    public function setNewFilePath(string $newFilePath)
    {
        $this->newFilePath = $newFilePath;
    }

    /**
     * @return string
     */
    public function getTemporaryFile(): string
    {
        return $this->temporaryFile;
    }

    /**
     * @param string $commandStr
     */
    public function setCommandString(string $commandStr)
    {
        $this->commandString = $commandStr;
    }

    public function getCommandString(): string
    {
        return $this->commandString;
    }

    /**
     * Save given image to temporary file and return the path
     *
     * @throws \Exception
     */
    protected function saveToTemporaryFile()
    {
        if (!$resource = @fopen($this->getSourceFile(), "r")) {
            throw  new ReadFileException(
                'Error occurred while trying to read the file Url : '
                .$this->getSourceFile()
            );
        }
        $content = "";
        while ($line = fread($resource, 1024)) {
            $content .= $line;
        }
        $this->temporaryFile = TMP_DIR.uniqid("", true);
        file_put_contents($this->temporaryFile, $content);
        $this->sourceMimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->temporaryFile);
    }

    /**
     * Remove the generated files
     */
    public function unlinkUsedFiles()
    {
        if (file_exists($this->getTemporaryFile())) {
            unlink($this->getTemporaryFile());
        }
        if (file_exists($this->getNewFilePath())) {
            unlink($this->getNewFilePath());
        }
    }

    /**
     * Generate files name + files path
     */
    protected function generateFilesName()
    {
        $hashedOptions = $this->options;
        unset($hashedOptions['refresh']);
        $this->newFileName = md5(implode('.', $hashedOptions).$this->sourceFile);
        $this->newFilePath = TMP_DIR.$this->newFileName;

        if ($this->options['refresh']) {
            $this->newFilePath .= uniqid("-", true);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function generateFileExtension()
    {
        $outputExtension = $this->extract('output');
        if ($outputExtension == self::EXT_AUTO) {
            $this->outputExtension = self::EXT_JPG;
            if ($this->isPngSupport()) {
                $this->outputExtension = self::EXT_PNG;
            }
            if ($this->isWebPSupport() || $this->getSourceMimeType() === self::WEBP_MIME_TYPE) {
                $this->outputExtension = self::EXT_WEBP;
            }
            if ($this->isGifSupport()) {
                $this->outputExtension = self::EXT_GIF;
            }
        } else {
            if (!in_array(
                $outputExtension,
                [self::EXT_PNG, self::EXT_JPG, self::EXT_GIF, self::EXT_JPG, self::EXT_WEBP]
            )
            ) {
                throw new InvalidArgumentException("Invalid file output requested");
            }
            $this->outputExtension = $outputExtension;
        }
        $fileExtension = '.'.$this->outputExtension;
        $this->newFilePath .= $fileExtension;
        $this->newFileName .= $fileExtension;
    }

    /**
     * Return bollean stating if WebP image format is supported; following these conditions:
     *  - The request is specifically expecting a webP response, independent of the browser's capabilities
     *  OR both:
     *  - The browser sent headers explicitly stating it supports webp (absolute requirement)
     *  AND
     *  - The app config/parameters.yml states that auto webP serving is enabled
     *
     * @return bool
     */
    public function isWebPSupport(): bool
    {
        return $this->outputExtension == self::EXT_WEBP
            || (in_array(self::WEBP_MIME_TYPE, Request::createFromGlobals()->getAcceptableContentTypes())
                && $this->defaultParams['auto_webp_enabled']);
    }

    /**
     * @return bool
     */
    public function isGifSupport(): bool
    {
        return $this->getSourceMimeType() == self::GIF_MIME_TYPE;
    }

    /**
     * @return bool
     */
    public function isPngSupport(): bool
    {
        return $this->getSourceMimeType() == self::PNG_MIME_TYPE;
    }

    /**
     * @return bool
     */
    public function isMozJpegSupport(): bool
    {
        return $this->extract('mozjpeg') == 1 &&
            (!$this->isPngSupport() || $this->outputExtension == self::EXT_JPG) &&
            (!$this->isGifSupport()) &&
            ($this->getOutputExtension() != self::EXT_GIF);
    }

    /**
     * @return string
     */
    public function getResponseContentType(): string
    {
        if ($this->getOutputExtension() == self::EXT_WEBP) {
            return self::WEBP_MIME_TYPE;
        }
        if ($this->getOutputExtension() == self::EXT_PNG) {
            return self::PNG_MIME_TYPE;
        }
        if ($this->getOutputExtension() == self::EXT_GIF) {
            return self::GIF_MIME_TYPE;
        }

        return self::JPEG_MIME_TYPE;
    }

    /**
     * @return string
     */
    public function getSourceMimeType(): string
    {
        return $this->sourceMimeType;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getOutputExtension(): string
    {
        return $this->outputExtension;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function extract(string $key): string
    {
        return $this->extractByKey($key, $this->options);
    }
}
