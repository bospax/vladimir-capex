<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class ParticularFilter extends QueryFilters
{
    protected array $allowedFilters = ['name', 'type_of_subcapex_id'];

	protected array $allowedSorts = ['created_at'];

    protected array $columnSearch = ['name', 'type_of_subcapex_id'];
}
