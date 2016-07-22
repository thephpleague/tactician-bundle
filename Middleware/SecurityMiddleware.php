<?php

namespace League\Tactician\Bundle\Middleware;

use League\Tactician\Exception\InvalidMiddlewareException;
use League\Tactician\Middleware;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class SecurityMiddleware implements Middleware
{
    /**
     * Access denied behavior to drop the command if not allowed.
     */
    const DROP_COMMAND = 1;

    /**
     * Access denied behavior to throw an AccessDenied exception if not allowed.
     * Default behavior.
     */
    const THROW_ACCESS_DENIED_EXCEPTION = 2;

    /**
     * @var int
     */
    private $accessDeniedBehavior;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, $accessDeniedBehavior = self::THROW_ACCESS_DENIED_EXCEPTION) {
        $this->authorizationChecker = $authorizationChecker;
        $this->accessDeniedBehavior = $accessDeniedBehavior;

        if ($this->accessDeniedBehavior !== static::DROP_COMMAND && $this->accessDeniedBehavior !== static::THROW_ACCESS_DENIED_EXCEPTION) {
            throw new InvalidMiddlewareException(
                sprintf('The security middleware requires a valid accessDeniedBehavior, \'%s\' is not valid.', $this->accessDeniedBehavior)
            );
        }
    }

    /**
     * @param object $command
     * @param callable $next
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if ($this->authorizationChecker->isGranted('handle', $command)) {
            return $next($command);
        } elseif ($this->accessDeniedBehavior === static::THROW_ACCESS_DENIED_EXCEPTION) {
            throw new AccessDeniedException(
                sprintf('The current user is not allowed to handle command of type \'%s\'', get_class($command))
            );
        }
    }
}

