<?php declare(strict_types=1);

namespace MateuszMesek\DatabaseDataTransfer\Api\Data;

interface TableInterface
{
    public function getName(): string;

    public function getConnectionName(): string;
}
