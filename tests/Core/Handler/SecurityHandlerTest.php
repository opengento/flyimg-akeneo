<?php

namespace Tests\Core\Service;

use Core\Entity\AppParameters;
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
        $appParameters = clone $this->app['params'];
        $appParameters->set('restricted_domains', true);
        $securityHandler = new SecurityHandler($appParameters);

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
        $appParameters = clone $this->app['params'];
        $appParameters->set('security_key', 'TestSecurityKey');
        $appParameters->set('security_iv', 'TestSecurityIVXXXX');
        $securityHandler = new SecurityHandler($appParameters);
        $options = parent::OPTION_URL;
        $imageSrc = parent::JPG_TEST_IMAGE;
        $securityHandler->checkSecurityHash($options, $imageSrc);
    }

    /**
     *
     */
    public function testSecurityKeyMissing()
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage(
            "security_key in empty im parameters.yml!"
        );
        $appParameters = clone $this->app['params'];
        $appParameters->set('security_key', '');
        $securityHandler = new SecurityHandler($appParameters);
        $hash = $securityHandler->encrypt( parent::OPTION_URL.'/'. parent::JPG_TEST_IMAGE);
    }

    /**
     *
     */
    public function testCheckSecurityIvMissing()
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage("Security iv is not set in parameters.yml (security_iv)");
        $appParameters = clone $this->app['params'];
        $appParameters->set('security_key', 'TestSecurityKey');
        $appParameters->set('security_iv', '');
        $securityHandler = new SecurityHandler($appParameters);
        $options = parent::OPTION_URL;
        $imageSrc = parent::JPG_TEST_IMAGE;
        $securityHandler->checkSecurityHash($options, $imageSrc);
    }

    /**
     *
     */
    public function testCheckSecurityHashSuccess()
    {
        $appParameters = clone $this->app['params'];
        $appParameters->set('security_key', 'TestSecurityKey');
        $appParameters->set('security_iv', 'TestSecurityIVXXXX');
        $securityHandler = new SecurityHandler($appParameters);
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
        $appParameters = clone $this->app['params'];
        $appParameters->set('security_key', 'TestSecurityKey');
        $appParameters->set('security_iv', 'TestSecurityIVXXXX');

        $securityHandler = new SecurityHandler($appParameters);
        $randomString = str_shuffle('AKALEOCJCNXMSOLWO5#KXMw');
        $hashedString = $securityHandler->encrypt($randomString);
        $decryptedString = $securityHandler->decrypt($hashedString);
        $this->assertEquals($decryptedString, $randomString);
    }
}
