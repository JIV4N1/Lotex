<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\PublicationStatus;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Property extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Property $property) {
            if ($property->exists && $property->isDirty(['is_lotification', 'lotification_id']) && $property->lots()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['is_lotification' => 'Esta lotificación tiene lotes; no puede convertirse en terreno.']);
            }
            if ($property->lotification_id) {
                $parent = self::find($property->lotification_id);
                if (! $parent || ! $parent->is_lotification || $parent->id === $property->id || $property->is_lotification) {
                    throw ValidationException::withMessages(['lotification_id' => 'Selecciona una lotificación activa válida.']);
                }
                if (blank($property->block) || blank($property->lot_number)) {
                    throw ValidationException::withMessages(['lot_number' => 'Indica manzana y número de lote.']);
                }
                $property->zone_id = null;
                $property->title = Str::limit($parent->title, 150, '').' · Mz. '.$property->block.' · Lote '.$property->lot_number;
            } else {
                $property->block = null;
                $property->lot_number = null;
            }
            if ($property->is_lotification) {
                $property->price_mxn = null;
                $property->area_m2 = null;
            }
            if (blank($property->code)) {
                $property->code = 'HZ-'.Str::ulid();
            }
            if (blank($property->slug)) {
                $property->slug = (rtrim(substr(Str::slug($property->title), 0, 200), '-') ?: 'terreno').'-'.strtolower((string) Str::ulid());
            }
        });
    }

    protected $fillable = [
        'is_lotification', 'lotification_id', 'block', 'lot_number', 'zone_id', 'code', 'slug', 'title', 'description', 'price_mxn', 'area_m2',
        'frontage_m', 'depth_m', 'reference_address', 'latitude', 'longitude',
        'services', 'access', 'topography', 'land_use', 'documentary_summary',
        'payment_terms', 'publication_status', 'availability_status', 'is_featured',
    ];

    protected $attributes = [
        'is_lotification' => false,
        'publication_status' => 'borrador',
        'availability_status' => 'disponible',
        'is_featured' => false,
        'services' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'is_lotification' => 'boolean',
            'price_mxn' => 'decimal:2',
            'area_m2' => 'decimal:2',
            'frontage_m' => 'decimal:2',
            'depth_m' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'services' => 'array',
            'publication_status' => PublicationStatus::class,
            'availability_status' => AvailabilityStatus::class,
            'is_featured' => 'boolean',
        ];
    }

    public function lotification(): BelongsTo
    {
        return $this->belongsTo(self::class, 'lotification_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(self::class, 'lotification_id');
    }

    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('publication_status', 'publicado')->where(fn ($q) => $q->whereNull('lotification_id')->orWhereHas('lotification', fn ($p) => $p->where('is_lotification', true)->where('publication_status', 'publicado')));
    }

    public function effective(): self
    {
        if (! $this->lotification_id || ! $this->lotification) {
            return $this;
        }
        $copy = clone $this;
        $parent = $this->lotification;
        $copy->setRelation('zone', $parent->zone);
        foreach (['description', 'reference_address', 'latitude', 'longitude', 'access', 'topography', 'land_use', 'documentary_summary', 'payment_terms', 'services'] as $field) {
            if (blank($copy->$field)) {
                $copy->$field = $parent->$field;
            }
        }
        if ($copy->images->isEmpty()) {
            $copy->setRelation('images', $parent->images);
        }
        $copy->setRelation('documents', $copy->documents->merge($parent->documents)->unique('id'));

        return $copy;
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PropertyDocument::class);
    }

    public function visitRequests(): HasMany
    {
        return $this->hasMany(VisitRequest::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('position')->orderBy('id');
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(PropertyImage::class)->ofMany(['position' => 'min', 'id' => 'min']);
    }

    /** MXN/m², rounded half up to two decimal places; never persisted. */
    protected function pricePerM2(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->price_mxn === null || $this->area_m2 === null || BigDecimal::of($this->area_m2)->isLessThanOrEqualTo(0)) {
                return null;
            }

            return (string) BigDecimal::of($this->price_mxn)
                ->dividedBy($this->area_m2, 2, RoundingMode::HALF_UP);
        });
    }
}
