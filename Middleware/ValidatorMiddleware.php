<?php

namespace League\Tactician\Bundle\Middleware;

use League\Tactician\Middleware;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorMiddleware implements Middleware
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param ValidatorInterface | null $validator
     */
    public function __construct(ValidatorInterface $validator = null) {
        $this->validator = $validator;
    }

    /**
     * @param object $command
     * @param callable $next
     * @return mixed
     * @throws InvalidCommandException
     * @throws \Exception
     */
    public function execute($command, callable $next)
    {
        if ($this->validator === null) {
            throw new \Exception(
                "The Validator Middleware requires the Validator service (@validator) to be present and configured." .
                "Please configure it."
            );
        }

        $constraintViolations = $this->validator->validate($command);

        if (count($constraintViolations) > 0) {
            throw InvalidCommandException::onCommand($command, $constraintViolations);
        }

        return $next($command);
    }
}

