<?php

namespace App\Catalog;

use Illuminate\Support\Collection;

/** Temporary read-only source. Replace this class's data access when connecting real listings. */
class DemoCatalog
{
    public function all(): Collection
    {
        return collect(require resource_path('fixtures/properties.php'));
    }

    public function zones(): Collection
    {
        return $this->all()->pluck('zone')->unique()->sort()->values();
    }

    public function find(string $slug): ?array
    {
        return $this->all()->firstWhere('slug', $slug);
    }
}
