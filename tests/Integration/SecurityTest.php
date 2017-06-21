<?php

namespace League\Tactician\Bundle\Tests\Integration;

use League\Tactician\Bundle\DependencyInjection\Compiler\UnknownMiddlewareException;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Role\Role;

/**
 * Integration test for security middleware.
 *
 * @author Ron Rademaker
 */
class SecurityTest extends IntegrationTest
{
    /**
     * Tests if the kernel is bootable with security middleware.
     *
     * @return void
     */
    public function testCanBootKernelWithSecurityMiddleware(): void
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

    /**
     * Tests if the kernel is not bootable without security settings (but with security middleware).
     *
     * @return void
     */
    public function testCanNotBootKernelWithoutSecurity(): void
    {
        $this->expectException(UnknownMiddlewareException::class);
        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.security
EOF
        );
        static::$kernel->boot();
    }

    /**
     * Tests if the kernel is bootable without security middleware and without security settings.
     */
    public function testCanBootKernelWithoutSecurity(): void
    {
        static::$kernel->boot();
        $this->assertTrue(true);
    }

    /**
     * Tests security middleware.
     *
     * @dataProvider provideTestData
     *
     * @param string $role
     * @param bool $allowed
     */
    public function testSecurityMiddleware(string $role, bool $allowed): void
    {
        if (false === $allowed) {
            $this->expectException(AccessDeniedException::class);
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
        static::$kernel->getContainer()->get('security.token_storage')->setToken(new AnonymousToken('test', 'anon', [new Role($role)]));
        static::$kernel->getContainer()->get('tactician.commandbus.default')->handle(new FakeCommand());

        $this->assertTrue($allowed);
    }

    /**
     * Gets test data for security middleware integration test.
     *
     * @return array
     */
    public function provideTestData(): array
    {
        return [
            'Role may handle the command' => ['ROLE_ADMIN', true],
            'Test role hierarchy' => ['ROLE_SUPER_ADMIN', true],
            'Role may not handle the command' => ['ROLE_USER', false],
        ];
    }

    /**
     * Security configuration.
     */
    private function loadSecurityConfiguration(): void
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
}
