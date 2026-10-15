<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitRequest;

class VisitRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, VisitRequest $record): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, VisitRequest $record): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, VisitRequest $record): bool
    {
        return false;
    }
}
