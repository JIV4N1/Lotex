<?php

namespace App\Catalog;

use App\Models\Property;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PropertyCatalog
{
    public function query(): Builder
    {
        return Property::query()->where('publication_status', 'publicado')
            ->with(['zone.municipality.state', 'images', 'documents' => fn ($q) => $q->where('is_public', true)]);
    }

    public function all(): Collection
    {
        return $this->query()->latest()->get()->map(fn ($p) => $this->present($p));
    }

    public function zones(): Collection
    {
        return Zone::whereHas('properties', fn ($query) => $query->where('publication_status', 'publicado'))->distinct()->orderBy('name')->pluck('name');
    }

    public function filtered(array $filters): Collection
    {
        $query = $this->query();
        if (! empty($filters['zone'])) {
            $query->whereHas('zone', fn ($zone) => $zone->where('name', $filters['zone']));
        }
        foreach (['min_price' => ['price_mxn', '>='], 'max_price' => ['price_mxn', '<='], 'min_area' => ['area_m2', '>='], 'max_area' => ['area_m2', '<=']] as $key => [$column, $operator]) {
            if (isset($filters[$key])) {
                $query->where($column, $operator, $filters[$key]);
            }
        }
        match ($filters['sort'] ?? 'recent') {
            'price_asc' => $query->orderBy('price_mxn'),
            'price_desc' => $query->orderByDesc('price_mxn'),
            default => $query->latest(),
        };

        return $query->orderByDesc('id')->get()->map(fn ($property) => $this->present($property));
    }

    public function featured(): Collection
    {
        return $this->query()->where('is_featured', true)->latest()->get()->map(fn ($property) => $this->present($property));
    }

    public function related(array $property): Collection
    {
        return $this->query()->where('slug', '!=', $property['slug'])
            ->orderByRaw('CASE WHEN zone_id IN (SELECT id FROM zones WHERE name = ?) THEN 0 ELSE 1 END', [$property['zone']])
            ->latest()->limit(3)->get()->map(fn ($item) => $this->present($item));
    }

    public function find(string $slug): ?array
    {
        $property = $this->query()->where('slug', $slug)->first();

        return $property ? $this->present($property) : null;
    }

    public function present(Property $p): array
    {
        $images = $p->images->map(fn ($image) => ['src' => route('property-images.show', $image), 'alt' => $image->alt_text])->all();

        return [
            'slug' => $p->slug, 'code' => $p->code, 'title' => $p->title,
            'zone' => $p->zone->name, 'municipality' => $p->zone->municipality->name, 'state' => $p->zone->municipality->state->name,
            'price' => $p->price_mxn, 'area' => $p->area_m2, 'frontage' => $p->frontage_m, 'depth' => $p->depth_m,
            'price_per_m2' => $p->price_per_m2, 'availability' => $p->availability_status->value,
            'featured' => $p->is_featured, 'date' => $p->created_at->toISOString(),
            'images' => $images ?: [['src' => asset('images/property-placeholder.svg'), 'alt' => 'Fotografía pendiente']],
            'has_images' => count($images) > 0, 'description' => $p->description, 'services' => $p->services ?? [],
            'access' => $p->access ?: 'Por confirmar', 'topography' => $p->topography ?: 'Por confirmar',
            'land_use' => $p->land_use ?: 'Por confirmar', 'documents' => $p->documentary_summary ?: 'Por confirmar',
            'payment_terms' => $p->payment_terms ?: 'Por confirmar', 'reference_address' => $p->reference_address,
            'latitude' => $p->latitude, 'longitude' => $p->longitude,
            'document_files' => $p->documents->map(fn ($d) => [
                'title' => $d->title, 'type' => $d->type, 'issuer' => $d->issuer,
                'issued_at' => $d->issued_at?->format('d/m/Y'), 'url' => route('property-documents.show', $d),
            ])->all(),
        ];
    }
}
