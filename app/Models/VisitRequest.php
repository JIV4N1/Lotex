<?php

namespace App\Models;

use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitRequest extends Model
{
    protected $fillable = ['property_id', 'name', 'phone', 'submission_token', 'status', 'assigned_to', 'appointment_at', 'notes', 'updated_by', 'lock_version'];

    protected function casts(): array
    {
        return ['status' => VisitStatus::class, 'appointment_at' => 'datetime', 'lock_version' => 'integer'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
