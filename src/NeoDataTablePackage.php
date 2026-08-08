<?php

declare(strict_types=1);

namespace Vendor\NeoPHP\DataTablePackage;

use Neo\Core\Package\Abstract\AbstractPackage;

final class NeoDataTablePackage extends AbstractPackage
{
    public function getName(): string
    {
        return 'DataTable';
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}