<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\HandlerMapping;

use League\Tactician\Bundle\DependencyInjection\InvalidCommandBusId;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class Routing
{
    /**
     * [
     *     'busId_1' => [
     *         'My\Command\Name1' => 'some.service.id',
     *         'My\Other\Command' => 'some.service.id.or.same.one'
     *     ],
     *     'busId_2' => [
     *         'Legacy\App\Command1' => 'some.old.handler',
     *         ...
     *     ],
     * ]
     *
     * @var array
     */
    private $mapping = [];

    public function __construct(array $validBusIds)
    {
        foreach ($validBusIds as $validBusId) {
            $this->mapping[$validBusId] = [];
        }
    }

    public function routeToBus($busId, $commandClassName, $serviceId)
    {
        $this->assertValidBusId($busId);
        $this->assertValidCommandFQCN($commandClassName, $serviceId);

        $this->mapping[$busId][$commandClassName] = $serviceId;
    }

    public function routeToAllBuses($commandClassName, $serviceId)
    {
        $this->assertValidCommandFQCN($commandClassName, $serviceId);

        foreach($this->mapping as $busId => $mapping) {
            $this->mapping[$busId][$commandClassName] = $serviceId;
        }
    }

    public function commandToServiceMapping(string $busId): array
    {
        $this->assertValidBusId($busId);
        return $this->mapping[$busId];
    }

    private function assertValidBusId(string $busId)
    {
        if (!isset($this->mapping[$busId])) {
            throw InvalidCommandBusId::ofName($busId, array_keys($this->mapping));
        }
    }

    /**
     * @param $commandClassName
     * @param $serviceId
     */
    protected function assertValidCommandFQCN($commandClassName, $serviceId)
    {
        if (!class_exists($commandClassName)) {
            throw new InvalidArgumentException("Can not route $commandClassName to $serviceId, class $commandClassName does not exist!");
        }
    }
}