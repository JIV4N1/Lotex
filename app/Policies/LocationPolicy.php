<?php

namespace App\Policies;

use App\Models\Municipality;
use App\Models\State;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;

class LocationPolicy extends PropertyPolicy
{
    public function delete(User $user, Model $record): bool
    {
        if (! $user->is_admin) {
            return false;
        }

        return match (true) {
            $record instanceof State => ! $record->municipalities()->exists(),
            $record instanceof Municipality => ! $record->zones()->exists(),
            $record instanceof Zone => ! $record->properties()->withTrashed()->exists(),
            default => false,
        };
    }
}
