<?php declare(strict_types=1);

namespace MateuszMesek\DatabaseDataTransfer\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use MateuszMesek\DatabaseDataTransfer\Api\Data\TableFactoryInterface;
use MateuszMesek\DatabaseDataTransfer\Api\Data\TableInterface;

class TableFactory implements TableFactoryInterface
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly string $instanceName = TableInterface::class
    )
    {
    }

    public function create(string $name, string $connectionName = ResourceConnection::DEFAULT_CONNECTION): TableInterface
    {
        return $this->objectManager->create(
            $this->instanceName,
            [
                'name' => $name,
                'connectionName' => $connectionName
            ]
        );
    }
}
