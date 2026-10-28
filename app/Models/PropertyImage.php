<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyImage extends Model
{
    protected $fillable = ['property_id', 'path', 'alt_text', 'position', 'is_site_plan'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_site_plan' => 'boolean'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
