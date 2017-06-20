<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\SecurityMiddlewarePass;
use League\Tactician\Bundle\Middleware\SecurityMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SecurityMiddlewarePassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->register('security.authorization_checker');

        (new SecurityMiddlewarePass())->process($container);

        $definition = $container->getDefinition(SecurityMiddlewarePass::SERVICE_ID);
        $this->assertSame(SecurityMiddleware::class, $definition->getClass());
        $this->assertEquals([new Reference('security.authorization_checker')], $definition->getArguments());
    }

    public function testProcessReturnsIfValidatorDoesNotExist()
    {
        $container = new ContainerBuilder();

        (new SecurityMiddlewarePass())->process($container);

        $this->assertFalse($container->hasDefinition(SecurityMiddlewarePass::SERVICE_ID));
    }
}
