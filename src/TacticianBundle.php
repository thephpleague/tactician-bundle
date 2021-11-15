<?php

namespace League\Tactician\Bundle;

use League\Tactician\Bundle\DependencyInjection\HandlerMapping\ClassNameMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\CompositeMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\HandlerMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\TypeHintMapping;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler;
use League\Tactician\Bundle\DependencyInjection\TacticianExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class TacticianBundle extends Bundle
{
    /**
     * @var HandlerMapping
     */
    private $handlerMapping;

    public function __construct(HandlerMapping $handlerMapping = null)
    {
        if ($handlerMapping === null) {
            $handlerMapping = static::defaultMappingStrategy();
        }

        $this->handlerMapping = $handlerMapping;
    }


    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new Compiler\DoctrineMiddlewarePass());
        $container->addCompilerPass(new Compiler\ValidatorMiddlewarePass());
        $container->addCompilerPass(new Compiler\SecurityMiddlewarePass());
        $container->addCompilerPass(new Compiler\CommandHandlerPass($this->handlerMapping));
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new TacticianExtension();
    }

    public static function defaultMappingStrategy(): HandlerMapping
    {
        return new CompositeMapping(new TypeHintMapping(), new ClassNameMapping());
    }
}
