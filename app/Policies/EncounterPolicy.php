<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;

class EncounterPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Encounter $encounter): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function update(User $user, Encounter $encounter): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function delete(User $user, Encounter $encounter): bool
    {
        return $user->hasRole('admin', 'dentist');
    }
}
