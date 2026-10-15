<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class ChatInteraction extends Model
{
    use Prunable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['question' => 'encrypted', 'answer' => 'encrypted', 'input_tokens' => 'integer',
            'output_tokens' => 'integer', 'total_tokens' => 'integer', 'duration_ms' => 'integer'];
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(config('chat.retention_days')));
    }

    public function scopeRetained(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(config('chat.retention_days')));
    }
}
