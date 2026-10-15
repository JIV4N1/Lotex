<?php

namespace App\Policies;

use App\Models\Advisor;
use App\Models\User;

class AdvisorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Advisor $advisor): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Advisor $advisor): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Advisor $advisor): bool
    {
        return $user->is_admin;
    }
}
