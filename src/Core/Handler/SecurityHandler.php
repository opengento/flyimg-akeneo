<?php

namespace Core\Handler;

use Core\Exception\SecurityException;

/**
 * Class SecurityHandler
 * @package Core\Service
 */
class SecurityHandler
{
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
     * Process Security checks.
     *
     * @param string $imageSource
     */
    public function processChecks(string $imageSource)
    {
        $this->checkRestrictedDomains($imageSource);
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
}
