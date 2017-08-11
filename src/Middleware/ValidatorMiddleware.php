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
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param object $command
     * @param callable $next
     *
     * @return mixed
     *
     * @throws InvalidCommandException
     */
    public function execute($command, callable $next)
    {
        $constraintViolations = $this->validator->validate($command);

        if (count($constraintViolations) > 0) {
            throw InvalidCommandException::onCommand($command, $constraintViolations);
        }

        return $next($command);
    }
}

