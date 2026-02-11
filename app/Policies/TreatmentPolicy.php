<?php

namespace App\Policies;

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

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function update(User $user, Treatment $treatment): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function delete(User $user, Treatment $treatment): bool
    {
        return $user->hasRole('admin', 'dentist');
    }
}
