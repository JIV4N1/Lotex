<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\PublicationStatus;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'zone_id', 'code', 'slug', 'title', 'description', 'price_mxn', 'area_m2',
        'frontage_m', 'depth_m', 'reference_address', 'latitude', 'longitude',
        'services', 'access', 'topography', 'land_use', 'documentary_summary',
        'payment_terms', 'publication_status', 'availability_status', 'is_featured',
    ];

    protected $attributes = [
        'publication_status' => 'borrador',
        'availability_status' => 'disponible',
        'is_featured' => false,
        'services' => '[]',
    ];

    protected function casts(): array
    {
        return [
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
