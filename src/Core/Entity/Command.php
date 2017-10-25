<?php

namespace Core\Entity;

/**
 * Class Command
 * @package Core\Entity
 */
class Command
{
    /** @var string */
    protected $command;

    /** @var array */
    protected $arguments = [];

    /**
     * Command constructor.
     *
     * @param string $command
     */
    public function __construct(string $command)
    {
        $this->command = $command;
    }

    /**
     * @param string $argument
     */
    public function addArgument(string $argument)
    {
        $this->arguments[] = $argument;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->command. ' ' .implode(' ', $this->arguments);
    }
}
