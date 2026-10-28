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
        return Property::query()->publiclyVisible()
            ->with(['lotification.zone.municipality.state', 'lotification.images', 'lotification.documents', 'zone.municipality.state', 'images', 'documents' => fn ($q) => $q->where('is_public', true)]);
    }

    public function all(): Collection
    {
        return $this->query()->latest()->get()->map(fn ($p) => $this->present($p));
    }

    public function zones(): Collection
    {
        return Zone::whereHas('properties', fn ($q) => $q->publiclyVisible())->distinct()->orderBy('name')->pluck('name');
    }

    public function filtered(array $filters): Collection
    {
        $query = $this->query()->where('is_lotification', false)->where(fn ($q) => $q->whereNull('lotification_id')->orWhere('availability_status', 'disponible'));
        if (! empty($filters['zone'])) {
            $query->where(fn ($q) => $q->whereHas('zone', fn ($z) => $z->where('name', $filters['zone']))->orWhereHas('lotification.zone', fn ($z) => $z->where('name', $filters['zone'])));
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

        $results = $query->orderByDesc('id')->get()->map(fn ($p) => $p->lotification_id ? $p->lotification : $p)->unique('id')->map(fn ($p) => $this->present($p));

        return match ($filters['sort'] ?? 'recent') {
            'price_asc' => $results->sortBy('price')->values(),
            'price_desc' => $results->sortByDesc('price')->values(),
            default => $results->sortByDesc('date')->values(),
        };
    }

    public function featured(): Collection
    {
        return $this->filtered([])->where('featured', true)->values();
    }

    public function related(array $property): Collection
    {
        return $this->filtered([])->reject(fn ($p) => $p['slug'] === $property['slug'] || $p['slug'] === ($property['lotification_slug'] ?? null))->take(3)->values();
    }

    public function find(string $slug): ?array
    {
        $property = $this->query()->where('slug', $slug)->first();

        return $property ? $this->present($property) : null;
    }

    public function present(Property $p): array
    {
        $p = $p->effective();
        $available = $p->is_lotification ? $p->lots()->publiclyVisible()->where('availability_status', 'disponible')->get() : collect();
        $images = $p->images->map(fn ($image) => ['src' => route('property-images.show', $image), 'alt' => $image->alt_text])->all();

        return [
            'is_lotification' => (bool) $p->is_lotification, 'lotification_slug' => $p->lotification?->slug, 'lotification_title' => $p->lotification?->title, 'block' => $p->block, 'lot_number' => $p->lot_number, 'available_lots' => $available->count(),
            'slug' => $p->slug, 'code' => $p->code, 'title' => $p->title,
            'zone' => $p->zone->name, 'municipality' => $p->zone->municipality->name, 'state' => $p->zone->municipality->state->name,
            'price' => $p->is_lotification ? $available->min('price_mxn') : $p->price_mxn, 'area' => $p->is_lotification ? $available->min('area_m2') : $p->area_m2, 'frontage' => $p->frontage_m, 'depth' => $p->depth_m,
            'price_per_m2' => $p->price_per_m2, 'availability' => $p->is_lotification ? ($available->isNotEmpty() ? 'disponible' : 'vendido') : $p->availability_status->value,
            'featured' => $p->is_featured, 'date' => $p->created_at->toISOString(),
            'images' => $images ?: [['src' => asset('images/property-placeholder.svg'), 'alt' => 'Fotografía pendiente']],
            'site_plan' => ($plan = $p->images->firstWhere('is_site_plan', true)) ? ['src'=>route('property-images.show', $plan), 'alt'=>$plan->alt_text] : null,
            'has_images' => count($images) > 0, 'description' => $p->description, 'services' => $p->services ?? [],
            'access' => $p->access ?: 'Por confirmar', 'topography' => $p->topography ?: 'Por confirmar',
            'land_use' => $p->land_use ?: 'Por confirmar', 'documents' => $p->documentary_summary ?: 'Por confirmar',
            'payment_terms' => $p->payment_terms ?: 'Por confirmar', 'reference_address' => $p->reference_address,
            'latitude' => $p->latitude, 'longitude' => $p->longitude,
            'document_files' => $p->documents->where('is_public', true)->map(fn ($d) => [
                'title' => $d->title, 'type' => $d->type, 'issuer' => $d->issuer,
                'issued_at' => $d->issued_at?->format('d/m/Y'), 'url' => route('property-documents.show', $d),
            ])->all(),
        ];
    }
}
