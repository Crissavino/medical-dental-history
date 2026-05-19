<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\Treatment;
use App\Models\User;

class TreatmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function view(User $user, Treatment $treatment): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function create(User $user, ?Encounter $encounter = null): bool
    {
        if ($encounter && $encounter->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function update(User $user, Treatment $treatment): bool
    {
        if ($treatment->encounter && $treatment->encounter->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function delete(User $user, Treatment $treatment): bool
    {
        if ($treatment->encounter && $treatment->encounter->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist');
    }
}
