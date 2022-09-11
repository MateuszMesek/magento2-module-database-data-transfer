<?php declare(strict_types=1);

namespace MateuszMesek\DatabaseDataTransfer\Api\Data;

use Magento\Framework\App\ResourceConnection;

interface TableFactoryInterface
{
    public function create(string $name, string $connectionName = ResourceConnection::DEFAULT_CONNECTION): TableInterface;
}
