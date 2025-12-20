<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection;

use League\Tactician\Bundle\DependencyInjection\TacticianExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class TacticianExtensionTest extends TestCase
{
    private ContainerBuilder $container;
    private TacticianExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new TacticianExtension();

        // Add required service stubs that the extension depends on
        $this->container->setDefinition('logger', new Definition(LoggerInterface::class));
        $this->container->setDefinition('security.access.decision_manager', new Definition(AccessDecisionManagerInterface::class));
    }

    private function load(array $config = []): void
    {
        $this->extension->load([$config], $this->container);

        // Make tactician services public to prevent them from being inlined during compilation
        foreach ($this->container->getDefinitions() as $id => $definition) {
            if (str_starts_with($id, 'tactician.')) {
                $definition->setPublic(true);
            }
        }

        $this->container->compile();
    }

    public function testLoadSecurityConfiguration()
    {
        $securitySettings = ['Some\Command' => ['ROLE_USER'], 'Some\Other\Command' => ['ROLE_ADMIN']];

        $this->load([
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'tactician.middleware.security',
                        'tactician.middleware.command_handler',
                    ]
                ]
            ],
            'security' => $securitySettings
        ]);

        $this->assertTrue($this->container->hasDefinition('tactician.middleware.security_voter'));
        $definition = $this->container->getDefinition('tactician.middleware.security_voter');
        $this->assertEquals($securitySettings, $definition->getArgument(1));
        $this->assertTrue($definition->hasTag('security.voter'));
    }

    public function testDefaultSecurityConfigurationIsAllowNothing()
    {
        $this->load([
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'tactician.middleware.security',
                        'tactician.middleware.command_handler',
                    ]
                ]
            ]
        ]);

        $this->assertTrue($this->container->hasDefinition('tactician.middleware.security_voter'));
        $definition = $this->container->getDefinition('tactician.middleware.security_voter');
        $this->assertEquals([], $definition->getArgument(1));
        $this->assertTrue($definition->hasTag('security.voter'));
    }

    public function testVoterIsNotLoadedWithoutSecurityMiddleware()
    {
        $this->load();

        $this->assertFalse($this->container->hasDefinition('tactician.middleware.security_voter'));
    }

    public function testLoggerMiddlewareIsCreated()
    {
        $this->load();

        $this->assertTrue($this->container->hasDefinition('tactician.middleware.logger'));
        $this->assertTrue($this->container->hasDefinition('tactician.logger.class_properties_formatter'));
        $this->assertTrue($this->container->hasDefinition('tactician.logger.class_name_formatter'));
    }
}
