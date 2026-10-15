<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, ContactMessage $record): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, ContactMessage $record): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, ContactMessage $record): bool
    {
        return false;
    }
}
