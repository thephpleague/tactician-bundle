<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection;

use League\Tactician\Bundle\DependencyInjection\TacticianExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

final class TacticianExtensionTest extends AbstractExtensionTestCase
{
    /** {@inheritDoc} */
    protected function getContainerExtensions() : array
    {
        return [new TacticianExtension()];
    }
}
