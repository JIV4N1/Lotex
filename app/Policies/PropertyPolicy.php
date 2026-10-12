<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Model $record): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Model $record): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->is_admin;
    }

    public function restore(User $user, Model $record): bool
    {
        return $user->is_admin;
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }
}
