<?php declare(strict_types=1);

namespace MateuszMesek\DatabaseDataTransfer\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use MateuszMesek\DatabaseDataTransfer\Api\Command\TransferDataInterface;
use MateuszMesek\DatabaseDataTransfer\Api\Data\TableInterface;

class TransferData implements TransferDataInterface
{
    public function __construct(
        private readonly ResourceConnection $resource
    )
    {
    }

    public function execute(TableInterface $target, TableInterface $source): void
    {
        $targetTableName = $target->getName();
        $sourceTableName = $source->getName();

        $dataColumns = $this->getDataColumns($targetTableName);
        $matchColumns = $this->getMatchColumns($targetTableName);

        $connection = $this->resource->getConnectionByName(
            $target->getConnectionName()
        );

        $queries = [];
        // upsert
        $joinConditions = [];
        $whereConditions = [];

        foreach ($matchColumns as $matchColumn) {
            $joinConditions[] = "target.$matchColumn = `source`.$matchColumn";
            $whereConditions[] = "NOT(target.$matchColumn <=> source.$matchColumn)";
        }

        $select = $connection->select()
            ->distinct()
            ->from(
                ['source' => $sourceTableName],
                $dataColumns
            )
            ->joinLeft(
                ['target' => $targetTableName],
                implode(' AND ', $joinConditions),
                []
            )
            ->where(implode(' OR ', $whereConditions))
        ;

        $queries[] = $connection->insertFromSelect(
            $select,
            $targetTableName,
            $dataColumns,
            AdapterInterface::INSERT_ON_DUPLICATE
        );

        // delete
        $joinConditions = [];
        $whereConditions = [];

        foreach ($matchColumns as $matchColumn) {
            $joinConditions[] = "`source`.$matchColumn = `target`.$matchColumn";
            $whereConditions[] = "source.$matchColumn IS NULL";
        }

        $select = $connection->select()
            ->distinct()
            ->from(
                ['target' => $targetTableName],
                $dataColumns
            )
            ->joinLeft(
                ['source' => $sourceTableName],
                implode(' AND ', $joinConditions),
                []
            )
            ->where(implode(' AND ', $whereConditions))
        ;

        $queries[] = $connection->deleteFromSelect(
            $select,
            'target'
        );

        foreach ($queries as $query) {
            $connection->query($query);
        }
    }

    private function getDataColumns(string $tableName): array
    {
        $connection = $this->resource->getConnection();

        $columns = array_filter(
            $connection->describeTable($tableName),
            static function (array $column) {
                return $column['IDENTITY'] === false;
            }
        );

        return array_keys($columns);
    }

    private function getMatchColumns(string $tableName): array
    {
        $connection = $this->resource->getConnection();

        $columns = $connection->describeTable($tableName);
        $indexes = $connection->getIndexList($tableName);

        $this->filterColumns($columns);

        $this->filterIndexes($indexes, array_keys($columns));
        $this->sortIndexes($indexes);

        $index = reset($indexes);

        return $index['fields'];
    }

    private function filterColumns(array &$columns): void
    {
        $columns = array_filter(
            $columns,
            static function (array $column) {
                return $column['IDENTITY'] === true;
            }
        );
    }

    private function filterIndexes(array &$indexes, array $columnNames): void
    {
        $indexes = array_filter(
            $indexes,
            static function (array $index) use ($columnNames) {
                if (!in_array($index['INDEX_TYPE'], ['unique', 'primary'])) {
                    return false;
                }

                return count(array_intersect($index['fields'], $columnNames)) === 0;
            }
        );
    }

    private function sortIndexes(array &$indexes): void
    {
        usort(
            $indexes,
            static function (array $a, array $b) {
                if ($a['INDEX_TYPE'] === $b['INDEX_TYPE']) {
                    $av = count($a['FIELDS']);
                    $bv = count($b['FIELDS']);

                    return ($av <=> $bv) * -1;
                }

                $av = match ($a['INDEX_TYPE']) {
                    'unique' => -1,
                    'primary' => 1
                };

                $bv = match ($b['INDEX_TYPE']) {
                    'unique' => -1,
                    'primary' => 1
                };

                return $av <=> $bv;
            }
        );
    }
}
