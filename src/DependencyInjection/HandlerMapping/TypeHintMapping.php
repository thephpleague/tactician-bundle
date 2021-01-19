<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\HandlerMapping;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use ReflectionClass;
use function method_exists;

/**
 * Routes commands based on typehints in the handler.
 *
 * If your handler has a public method with a single, non-scalar, no-interface type hinted
 * parameter, we'll assume that typehint is a command and route it to this
 * service definition as the handler.
 *
 * So, a class like this:
 *
 * class MyHandler
 * {
 *     public function handle(RegisterUser $command) {...}
 *     private function foobar(SomeObject $obj) {...}
 *     public function checkThings(OtherObject $obj, WhatObject $obj2)
 *     public function setADependency(ManagerInterface $interface) {...}
 * }
 *
 * would have RegisterUser routed to it, but not SomeObject (because it's
 * used in a private method), not OtherObject or WhatObject (because they
 * don't appear as the only parameter) and not setADependency (because it
 * has an interface type hinted parameter).
 */
final class TypeHintMapping extends TagBasedMapping
{
    protected function isSupported(ContainerBuilder $container, Definition $definition, array $tagAttributes): bool
    {
        return isset($tagAttributes['typehints']) && $tagAttributes['typehints'] === true;
    }

    protected function findCommandsForService(ContainerBuilder $container, Definition $definition, array $tagAttributes): array
    {
        $results = [];

        $reflClass = new ReflectionClass($container->getParameterBag()->resolveValue($definition->getClass()));

        foreach ($reflClass->getMethods() as $method) {

            if (!$method->isPublic()
                || $method->isConstructor()
                || $method->isStatic()
                || $method->isAbstract()
                || $method->isVariadic()
                || $method->getNumberOfParameters() !== 1
            ) {
                continue;
            }

            $parameter = $method->getParameters()[0];
            if (!$parameter->hasType()
                || $parameter->getType() instanceof \ReflectionUnionType
                || $parameter->getType()->isBuiltin()
                || (new ReflectionClass($parameter->getType()->getName()))->isInterface()
            ) {
                continue;
            }

            $type = $parameter->getType();
            if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
                $results[] = $type->getName();
            } else {
                $results[] = (string)$type;
            }

        }

        return $results;
    }
}
