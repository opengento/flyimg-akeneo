<?php

namespace Tests\Core\Service;

use Core\Exception\SecurityException;
use Core\Handler\SecurityHandler;
use Tests\Core\BaseTest;

class SecurityHandlerTest extends BaseTest
{
    /**
     *
     */
    public function testRestrictedDomains()
    {
        $this->expectException(SecurityException::class);
        $this->app['params'] = array_merge($this->app['params'], ['restricted_domains' => true]);
        $securityHandler = new SecurityHandler($this->app['params']);

        $securityHandler->checkRestrictedDomains(parent::PNG_TEST_IMAGE);
    }
}
