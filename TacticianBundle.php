<?php namespace League\Tactician\Bundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler;
use League\Tactician\Bundle\DependencyInjection\TacticianExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class TacticianBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new Compiler\DoctrineMiddlewarePass());
        $container->addCompilerPass(new Compiler\ValidatorMiddlewarePass());
        $container->addCompilerPass(new Compiler\SecurityMiddlewarePass());
        $container->addCompilerPass(new Compiler\CommandHandlerPass());
    }

    public function getContainerExtension()
    {
        return new TacticianExtension();
    }
}
