<?php

namespace League\Tactician\Bundle\Tests\Middleware;

use League\Tactician\Bundle\Middleware\SecurityMiddleware;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Unit test for the security middleware.
 *
 * @author Ron Rademaker
 */
class SecurityMiddlewareTest extends TestCase
{
    /**
     * Authorization checker mock.
     */
    private $authorizationChecker;

    /**
     * Set up.
     */
    public function setUp()
    {
        $this->authorizationChecker = Mockery::mock(AuthorizationCheckerInterface::class);
    }

    /**
     * Tests the command is handled if access is granted.
     */
    public function testAccessIsGranted()
    {
        $this->authorizationChecker->shouldReceive('isGranted')->andReturn(true);
        $middleware = new SecurityMiddleware($this->authorizationChecker);
        $handled = false;
        $middleware->execute(new FakeCommand(), function () use(&$handled) {
            $handled = true;
        });

        $this->assertTrue($handled);
    }

    /**
     * Tests the command is not handled if access is denied and an AccessDenied exception is thrown.
     */
    public function testAccessIsNotGranted()
    {
        $this->expectException(AccessDeniedException::class);
        $this->authorizationChecker->shouldReceive('isGranted')->andReturn(false);
        $middleware = new SecurityMiddleware($this->authorizationChecker);
        $handled = false;
        $middleware->execute(new FakeCommand(), function () use(&$handled) {
            $handled = true;
        });

        $this->assertFalse($handled);
    }
}
