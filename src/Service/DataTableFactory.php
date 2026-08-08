<?php

declare(strict_types=1);

namespace Vendor\NeoPHP\DataTablePackage\Service;

use Neo\Core\Database\ORM\Persistence\EntityManager;
use Neo\Core\Database\Pagination\Paginator;

class DataTableFactory
{
    public function __construct(
        private EntityManager $em
    ) {
    }

    public function createFromEntity(
        string $entityClass,
        array $queryParams,
        array $columns,
        array $searchableFields = [],
        int $perPage = 20,
    ): DataTableResult
    {
        $repo = $this->em->getRepository($entityClass);
        $metadata = $this->em->getClassMetadata($entityClass);

        $search = trim($queryParams['search'] ?? '');
        $sort = $queryParams['sort'] ?? null;
        $direction = strtolower($queryParams['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $page = max(1, (int)($queryParams['page'] ?? 1));

        $criteria = [];

        if ($search !== '' && $searchableFields !== []) {
            $criteria['__search'] = ['fields' => $searchableFields, 'term' => $search];
        }

        $orderBy = $sort !== null && $this->isSortable($columns, $sort)
            ? [$sort => $direction]
            : [];

        $total = $repo->count($criteria === [] ? [] : $this->stripSearchMarker($criteria));

        $entities = $repo->findBy(
            $this->stripSearchMarker($criteria),
            $orderBy,
            $perPage,
            ($page - 1) * $perPage,
        );

        $rows = array_map(
            fn(object $entity) => $this->entityToRow($entity, $metadata, $columns),
            $entities
        );

        $paginator = new Paginator($rows, $total, $page, $perPage);

        return new DataTableResult(
            columns: array_map(
                fn(array $c) => ['key' => $c['key'], 'label' => $c['label'], 'sortable' => $c['sortable'] ?? false],
                $columns
            ),
            paginator: $paginator,
            search: $search,
            sort: $sort,
            direction: $direction,
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

    private function stripSearchMarker(array $criteria): array
    {
        unset($criteria['__search']);

        return $criteria;
    }

    private function entityToRow(object $entity, $metadata, array $columns): array
    {
        $row = [];

        foreach ($columns as $column) {
            $row[$column['key']] = $metadata->getFieldValue($entity, $column['key']);
        }

        return $row;
    }
}