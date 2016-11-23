<?php

namespace League\Tactician\Bundle\Middleware;

use League\Tactician\Middleware;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityMiddleware implements Middleware
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker = null)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param object $command
     * @param callable $next
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if (null === $this->authorizationChecker) {
            throw new \Exception(
                "The Security Middleware requires the authorization checker service (@security.authorization_checker) to be present and configured." .
                "Please active security extension in your config."
            );
        }

        if ($this->authorizationChecker->isGranted('handle', $command)) {
            return $next($command);
        } else {
            throw new AccessDeniedException(
                sprintf('The current user is not allowed to handle command of type \'%s\'', get_class($command))
            );
        }
    }
}

