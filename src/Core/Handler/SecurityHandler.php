<?php

namespace Core\Handler;

use Core\Exception\SecurityException;

/**
 * Class SecurityHandler
 * @package Core\Service
 */
class SecurityHandler
{

    const ENCRYPT_METHOD = "AES-256-CBC";

    /** @var array */
    protected $defaultParams;

    /**
     * SecurityHandler constructor.
     *
     * @param array $defaultParams
     */
    public function __construct(array $defaultParams)
    {
        $this->defaultParams = $defaultParams;
    }

    /**
     * Check Restricted Domain enabled
     *
     * @param string $imageSource
     *
     * @throws SecurityException
     */
    public function checkRestrictedDomains(string $imageSource)
    {
        if ($this->defaultParams['restricted_domains'] &&
            is_array($this->defaultParams['whitelist_domains']) &&
            !in_array(parse_url($imageSource, PHP_URL_HOST), $this->defaultParams['whitelist_domains'])
        ) {
            throw  new SecurityException(
                'Restricted domains enabled, the domain your fetching from is not allowed: '.
                parse_url($imageSource, PHP_URL_HOST)
            );
        }
    }

    /**
     * @param string $options
     * @param string $imageSrc
     *
     * @throws SecurityException
     */
    public function checkSecurityHash(string &$options, string &$imageSrc)
    {
        if (empty($this->defaultParams['security_key'])) {
            return;
        }
        if (empty($this->defaultParams['security_iv'])) {
            throw  new SecurityException(
                'Security iv is not set in parameters.yml (security_iv)'
            );
        }
        $decryptedHash = $this->decrypt($options);
        if (empty($decryptedHash)) {
            throw  new SecurityException(
                "Security Key enabled: Requested URL doesn't match with the hashed Security key !"
            );
        };

        list($explodedOptions, $explodedImageSrc) = explode('/', $decryptedHash, 2);

        if (empty($explodedImageSrc) || empty($explodedOptions)) {
            throw  new SecurityException(
                "Something went wrong when decrypting the hashed URL: ".
                $options
            );
        }
        $options = $explodedOptions;
        $imageSrc = $explodedImageSrc;
    }

    /**
     * @param $string
     *
     * @return string
     */
    public function encrypt(string $string): string
    {
        list($key, $iv) = $this->createHash();
        $output = base64_encode(openssl_encrypt($string, self::ENCRYPT_METHOD, $key, 0, $iv));

        return $output;
    }

    /**
     * @param $string
     *
     * @return string
     */
    public function decrypt(string $string): string
    {
        list($key, $iv) = $this->createHash();
        $output = openssl_decrypt(base64_decode($string), self::ENCRYPT_METHOD, $key, 0, $iv);

        return $output;
    }

    /**
     * @return array
     */
    protected function createHash()
    {
        $secretKey = $this->defaultParams['security_key'];
        $secretIv = $this->defaultParams['security_iv'];
        // hash
        $key = hash('sha256', $secretKey);

        //initialization vector(IV) - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secretIv), 0, 16);

        return [$key, $iv];
    }
}
