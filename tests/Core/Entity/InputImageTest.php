<?php

namespace Tests\Core\Entity;

use Core\Entity\InputImage;
use Core\Exception\ReadFileException;
use Tests\Core\BaseTest;

/**
 * @backupGlobals disabled
 */
class InputImageTest extends BaseTest
{

    /**
     * Test SaveToTemporaryFileException
     */
    public function testSaveToTemporaryFileException()
    {
        $this->expectException(ReadFileException::class);

        $inputImage = new InputImage(['output' => 'jpg'], parent::JPG_TEST_IMAGE.'--fail');
    }
}
