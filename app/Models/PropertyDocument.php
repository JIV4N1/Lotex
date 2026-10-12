<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyDocument extends Model
{
    protected $fillable = ['property_id', 'title', 'path', 'type', 'issuer', 'issued_at', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'issued_at' => 'date'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
