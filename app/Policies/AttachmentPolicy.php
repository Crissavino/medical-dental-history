<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function view(User $user, Attachment $attachment): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->hasRole('admin', 'dentist');
    }
}
