<?php

namespace App\Policies;

use App\Models\ChatInteraction;
use App\Models\User;

class ChatInteractionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, ChatInteraction $record): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ChatInteraction $record): bool
    {
        return false;
    }

    public function delete(User $user, ChatInteraction $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
