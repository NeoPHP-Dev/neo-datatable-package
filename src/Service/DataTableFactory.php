<?php

declare(strict_types=1);

namespace Vendor\NeoPHP\DataTablePackage\Service;

use Neo\Core\Database\DatabaseManager;
use Neo\Core\Database\ORM\Persistence\EntityManager;
use Neo\Core\Database\ORM\Query\QueryBuilder;
use Neo\Core\Database\Pagination\Paginator;

class DataTableFactory
{
    public function __construct(
        private EntityManager $em,
        private DatabaseManager $db,
    ) {}

    public function createFromEntity(
        string $entityClass,
        array $queryParams,
        array $columns,
        array $searchableFields = [],
        int $perPage = 20,
    ): DataTableResult {
        $metadata = $this->em->getClassMetadata($entityClass);

        $search = trim($queryParams['search'] ?? '');
        $sort = $queryParams['sort'] ?? null;
        $direction = strtolower($queryParams['direction'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        $page = max(1, (int) ($queryParams['page'] ?? 1));

        $query = QueryBuilder::for($this->db, $metadata->table);

        if ($search !== '' && $searchableFields !== []) {
            foreach ($searchableFields as $i => $field) {
                $column = $metadata->getColumnName($field);
                $like = '%' . $search . '%';

                if ($i === 0) {
                    $query->where($column, 'LIKE', $like);
                } else {
                    $query->orWhere($column, 'LIKE', $like);
                }
            }
        }

        if ($sort !== null && $this->isSortable($columns, $sort)) {
            $sortColumn = $metadata->getColumnName($sort);
            $query->orderBy($sortColumn, $direction);
        }

        $paginator = $query->paginate($page, $perPage);

        $rows = array_map(
            fn(array $row) => $this->rowToDisplay($row, $metadata, $columns),
            $paginator->getItems()
        );

        $displayPaginator = new Paginator(
            $rows,
            $paginator->getTotalItems(),
            $paginator->getCurrentPage(),
            $paginator->getPerPage(),
        );

        return new DataTableResult(
            columns: array_map(
                fn(array $c) => ['key' => $c['key'], 'label' => $c['label'], 'sortable' => $c['sortable'] ?? false],
                $columns
            ),
            paginator: $displayPaginator,
            search: $search,
            sort: $sort,
            direction: strtolower($direction),
        );
    }

    private function isSortable(array $columns, string $field): bool
    {
        foreach ($columns as $column) {
            if ($column['key'] === $field) {
                return $column['sortable'] ?? false;
            }
        }

        return false;
    }

    private function rowToDisplay(array $row, $metadata, array $columns): array
    {
        $display = [];

        foreach ($columns as $column) {
            $columnName = $metadata->getColumnName($column['key']);
            $display[$column['key']] = $row[$columnName] ?? null;
        }

        return $display;
    }
}