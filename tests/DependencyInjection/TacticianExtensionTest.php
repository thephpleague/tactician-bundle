<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection;

use League\Tactician\Bundle\DependencyInjection\TacticianExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class TacticianExtensionTest extends AbstractExtensionTestCase
{
    /**
     * Return an array of container extensions you need to be registered for each test (usually just the container
     * extension you are testing.
     *
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions() : array
    {
        return [
            new TacticianExtension()
        ];
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

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('tactician.middleware.security_voter', 1, $securitySettings);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('tactician.middleware.security_voter', 'security.voter');
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

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('tactician.middleware.security_voter', 1, []);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('tactician.middleware.security_voter', 'security.voter');
    }

    public function testVoterIsNotLoadedWithoutSecurityMiddleware()
    {
        $this->load();

        $this->assertContainerBuilderNotHasService('tactician.middleware.security_voter');
    }

    public function testLoggerMiddlewareIsCreated()
    {
        $this->load();

        $this->assertContainerBuilderHasService('tactician.middleware.logger');
        $this->assertContainerBuilderHasService('tactician.logger.class_properties_formatter');
        $this->assertContainerBuilderHasService('tactician.logger.class_name_formatter');
    }
}
