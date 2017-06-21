<?php

namespace League\Tactician\Bundle\Middleware;

use League\Tactician\Exception\Exception;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidCommandException extends \Exception implements Exception
{
    /**
     * @var object
     */
    protected $command;

    /**
     * @var ConstraintViolationListInterface
     */
    protected $violations;

    /**
     * @param object $command
     * @param ConstraintViolationListInterface $violations
     *
     * @return static
     */
    public static function onCommand($command, ConstraintViolationListInterface $violations)
    {
        $exception = new static(
            'Validation failed for ' . get_class($command) .
            ' with ' . $violations->count() . ' violation(s).'
        );

        $exception->command    = $command;
        $exception->violations = $violations;

        return $exception;
    }

    /**
     * @return object
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getViolations()
    {
        return $this->violations;
    }
}
