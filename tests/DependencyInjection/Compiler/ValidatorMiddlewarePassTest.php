<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\ValidatorMiddlewarePass;
use League\Tactician\Bundle\Middleware\ValidatorMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ValidatorMiddlewarePassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->register('validator');

        (new ValidatorMiddlewarePass())->process($container);

        $definition = $container->getDefinition(ValidatorMiddlewarePass::SERVICE_ID);
        $this->assertSame(ValidatorMiddleware::class, $definition->getClass());
        $this->assertEquals([new Reference('validator')], $definition->getArguments());
    }

    public function testProcessReturnsIfAuthorizationCheckerDoesNotExist()
    {
        $container = new ContainerBuilder();

        (new ValidatorMiddlewarePass())->process($container);

        $this->assertFalse($container->hasDefinition(ValidatorMiddlewarePass::SERVICE_ID));
    }
}
