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
        $this->expectExceptionMessage("Restricted domains enabled, the domain your fetching from is not allowed:");
        $this->app['params'] = array_merge($this->app['params'], ['restricted_domains' => true]);
        $securityHandler = new SecurityHandler($this->app['params']);

        $securityHandler->checkRestrictedDomains(parent::PNG_TEST_IMAGE);
    }

    /**
     *
     */
    public function testCheckSecurityHashFail()
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage(
            "Security Key enabled: Requested URL doesn't match with the hashed Security key !"
        );
        $this->app['params'] = array_merge(
            $this->app['params'],
            ['security_key' => 'TestSecurityKey', 'security_iv' => 'TestSecurityIVXXXX']
        );
        $securityHandler = new SecurityHandler($this->app['params']);
        $options = parent::OPTION_URL;
        $imageSrc = parent::JPG_TEST_IMAGE;
        $securityHandler->checkSecurityHash($options, $imageSrc);
    }

    /**
     *
     */
    public function testCheckSecurityIvMissing()
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage("Security iv is not set in parameters.yml (security_iv)");
        $this->app['params'] = array_merge(
            $this->app['params'],
            ['security_key' => 'TestSecurityKey', 'security_iv' => '']
        );
        $securityHandler = new SecurityHandler($this->app['params']);
        $options = parent::OPTION_URL;
        $imageSrc = parent::JPG_TEST_IMAGE;
        $securityHandler->checkSecurityHash($options, $imageSrc);
    }

    /**
     *
     */
    public function testCheckSecurityHashSuccess()
    {
        $this->app['params'] = array_merge(
            $this->app['params'],
            ['security_key' => 'TestSecurityKey', 'security_iv' => 'TestSecurityIVXXXX']
        );
        $securityHandler = new SecurityHandler($this->app['params']);
        $options = parent::OPTION_URL;
        $imageSrc = parent::JPG_TEST_IMAGE;
        $hash = $securityHandler->encrypt($options.'/'.$imageSrc);
        list($hashedOptions, $hashedImageSrc) = $securityHandler->checkSecurityHash($hash, $imageSrc);
        $this->assertEquals($hashedOptions, $options);
        $this->assertEquals($hashedImageSrc, $imageSrc);
    }

    /**
     *
     */
    public function testEncryptionDecryption()
    {
        $this->app['params'] = array_merge(
            $this->app['params'],
            ['security_key' => 'TestSecurityKey', 'security_iv' => 'TestSecurityIVXXXX']
        );
        $securityHandler = new SecurityHandler($this->app['params']);
        $randomString = str_shuffle('AKALEOCJCNXMSOLWO5#KXMw');
        $hashedString = $securityHandler->encrypt($randomString);
        $decryptedString = $securityHandler->decrypt($hashedString);
        $this->assertEquals($decryptedString, $randomString);
    }
}
