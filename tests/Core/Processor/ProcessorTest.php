<?php

namespace Tests\Core\Processor;

use Core\Exception\ExecFailedException;
use Core\Processor\Processor;

/**
 * Class ProcessorTest
 * @package Tests\Core\Processor
 */
class ProcessorTest extends \PHPUnit_Framework_TestCase
{

    public function testExecuteSuccess()
    {
        $processor = new Processor();
        $output = $processor->execute(Processor::IM_CONVERT_COMMAND.' --version');
        $this->assertNotEmpty($output);
    }

    public function testExecuteFail()
    {
        $this->expectException(ExecFailedException::class);
        $processor = new Processor();
        $output = $processor->execute(Processor::IM_CONVERT_COMMAND.' --invalid-option');
        $this->assertNotEmpty($output);
    }
}
