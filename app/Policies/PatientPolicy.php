<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Patient $patient): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'dentist', 'receptionist');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin', 'dentist', 'receptionist');
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin', 'dentist');
    }

    public function export(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin', 'dentist');
    }
}
