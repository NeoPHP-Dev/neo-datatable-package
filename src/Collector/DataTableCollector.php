<?php

declare(strict_types=1);

namespace Vendor\NeoPHP\DataTablePackage\Collector;

use Neo\Core\Profiler\Interface\CollectorInterface;
use Vendor\NeoPHP\DataTablePackage\Service\DataTableFactory;

final class DataTableCollector implements CollectorInterface
{
    public function getName(): string
    {
        return 'datatable';
    }

    public function collect(): array
    {
        $tables = DataTableFactory::getTables();

        return [
            'total' => count($tables),
            'tables' => $tables,
        ];
    }

    public function inToolbar(): bool
    {
        return false;
    }

    public function inProfiler(): bool
    {
        return true;
    }

    public function toolbarData(): array
    {
        return [
            'label' => 'DataTable',
            'value' => '',
            'badge' => null,
        ];
    }

    public function profilerData(): array
    {
        $data = $this->collect();

        if ($data['total'] === 0) {
            return [
                'title' => 'DataTable',
                'badge' => null,
                'blocks' => [
                    [
                        'type' => 'kv',
                        'section' => null,
                        'rows' => [
                            ['label' => 'Status', 'value' => 'No data table was built during this request.'],
                        ],
                    ],
                ],
            ];
        }

        return [
            'title' => 'DataTable',
            'badge' => null,
            'metrics' => [
                ['label' => 'Tables built', 'value' => (string) $data['total']],
            ],
            'blocks' => [
                [
                    'type' => 'table',
                    'section' => null,
                    'columns' => ['Entity', 'Search', 'Sort', 'Page', 'Per page', 'Total items', 'Duration'],
                    'rows' => array_map(
                        static fn (array $t) => [
                            $t['entityClass'],
                            $t['search'] !== '' ? $t['search'] : '—',
                            $t['sort'] !== null ? $t['sort'] . ' ' . $t['direction'] : '—',
                            (string) $t['page'],
                            (string) $t['perPage'],
                            (string) $t['totalItems'],
                            $t['duration'] . ' ms',
                        ],
                        $data['tables']
                    ),
                ],
            ],
        ];
    }
}