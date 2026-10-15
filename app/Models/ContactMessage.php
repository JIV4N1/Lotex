<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'message', 'property_id', 'submission_token', 'status', 'notes'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class)->withTrashed();
    }
}
