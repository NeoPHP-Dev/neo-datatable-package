<?php

declare(strict_types=1);

namespace Vendor\NeoPHP\DataTablePackage\Service;

use Neo\Core\Database\Pagination\Paginator;

readonly class DataTableResult
{
    public function __construct(
        public array $columns,
        public Paginator $paginator,
        public string $search,
        public ?string $sort,
        public string $direction,
    ) {
    }
}