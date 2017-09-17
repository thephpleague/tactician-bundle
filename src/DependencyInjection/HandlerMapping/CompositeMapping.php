<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\HandlerMapping;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CompositeMapping implements HandlerMapping
{
    /**
     * @var HandlerMapping[]
     */
    private $strategies;

    public function __construct(HandlerMapping ...$strategies)
    {
        $this->strategies = $strategies;
    }

    public function build(ContainerBuilder $container, Routing $routing): Routing
    {
        foreach ($this->strategies as $strategy) {
            $routing = $strategy->build($container, $routing);
        }

        return $routing;
    }
}