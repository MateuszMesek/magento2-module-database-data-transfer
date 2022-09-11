<?php declare(strict_types=1);

namespace MateuszMesek\DatabaseDataTransfer\Data;

use MateuszMesek\DatabaseDataTransfer\Api\Data\TableInterface;

class Table implements TableInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $connectionName
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConnectionName(): string
    {
        return $this->connectionName;
    }
}
