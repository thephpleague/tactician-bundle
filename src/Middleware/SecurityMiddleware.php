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
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param object $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if ($this->authorizationChecker->isGranted('handle', $command)) {
            return $next($command);
        }

        throw new AccessDeniedException(
            sprintf('The current user is not allowed to handle command of type \'%s\'', get_class($command))
        );
    }
}

