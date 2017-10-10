<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\HandlerMapping;

use Symfony\Component\DependencyInjection\Definition;
use ReflectionClass;

/**
 * Routes commands based on typehints in the handler.
 *
 * If your handler has a public method with a single, non-scalar type hinted
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
 * }
 *
 * would have RegisterUser routed to it, but not SomeObject (because it's
 * used in a private method) and not OtherObject or WhatObject (because they
 * don't appear as the only parameter).
 */
final class TypeHintMapping extends TagBasedMapping
{
    protected function isSupported(Definition $definition, array $tagAttributes): bool
    {
        return isset($tagAttributes['typehints']) && $tagAttributes['typehints'] === true;
    }

    protected function findCommandsForService(Definition $definition, array $tagAttributes): array
    {
        $results = [];

        $reflClass = new ReflectionClass($definition->getClass());

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
            if (!$parameter->hasType() || $parameter->getType()->isBuiltin()) {
                continue;
            }

            $results[] = (string)$parameter->getType();
        }

        return $results;
    }
}
