<?php declare(strict_types=1);

namespace MateuszMesek\DatabaseDataTransfer\Api\Command;

use MateuszMesek\DatabaseDataTransfer\Api\Data\TableInterface;

interface TransferDataInterface
{
    public function execute(TableInterface $target, TableInterface $source): void;
}
