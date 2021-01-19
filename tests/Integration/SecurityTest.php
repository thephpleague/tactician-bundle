<?php

namespace League\Tactician\Bundle\Tests\Integration;

use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use stdClass;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Role\Role;

/**
 * Integration test for security middleware.
 *
 * @author Ron Rademaker
 *
 * @runTestsInSeparateProcesses
 */
class SecurityTest extends IntegrationTest
{
    public function testCanBootKernelWithSecurityMiddleware()
    {
        $this->loadSecurityConfiguration();

        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.security
EOF
        );
        static::$kernel->boot();
        $this->assertTrue(true);
    }

    public function testCanNotBootKernelIfLoadingSecurityMiddlewareWithoutSecurityBeingTurnedOn()
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.security
EOF
        );
        static::$kernel->boot();
    }

    public function testCanBootKernelWithoutSecurityOrSecurityMiddleware()
    {
        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.command_handler
EOF
        );
        static::$kernel->boot();
        $this->assertTrue(true);
    }

    /**
     * @dataProvider provideTestData
     */
    public function testSecurityMiddleware($command, string $role, string $expectedExceptionClassName = null)
    {
        if ($expectedExceptionClassName) {
            $this->expectException($expectedExceptionClassName);
        }

        $this->loadSecurityConfiguration();
        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.security
security:
    League\Tactician\Bundle\Tests\Fake\FakeCommand:
        - 'ROLE_ADMIN'
EOF
        );

        static::$kernel->boot();
        $this->setUserRole($role);

        static::$kernel->getContainer()->get('tactician.commandbus.default')->handle($command);
    }

    /**
     * Gets test data for security middleware integration test.
     *
     * @return array
     */
    public function provideTestData(): array
    {
        return [
            'Role may handle the command' => [new FakeCommand(), 'ROLE_ADMIN'],
            'Test role hierarchy' => [new FakeCommand(), 'ROLE_SUPER_ADMIN'],
            'Role may not handle the command' => [new FakeCommand(), 'ROLE_USER', AccessDeniedException::class],
            'Deny access if command is not in the mapping' => [new stdClass(), 'ROLE_SUPER_ADMIN', AccessDeniedException::class],
        ];
    }

    /**
     * Security configuration.
     */
    private function loadSecurityConfiguration()
    {
        $this->givenConfig('security', <<< 'EOF'
access_denied_url: /

role_hierarchy:
    ROLE_ADMIN:       ROLE_USER
    ROLE_SUPER_ADMIN: ROLE_ADMIN

providers:
    my_in_memory_provider:
        memory:

firewalls:
    main:
        anonymous: ~
        http_basic: ~
EOF
        );
    }

    /**
     * @param string $role
     */
    protected function setUserRole(string $role)
    {
        static::$kernel->getContainer()
            ->get('security.token_storage')
            ->setToken(
                new AnonymousToken('test', 'anon', [$role])
            );
    }
}
