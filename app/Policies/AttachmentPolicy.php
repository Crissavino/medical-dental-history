<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Encounter;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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

    public function create(User $user, ?Model $attachable = null): bool
    {
        if ($attachable instanceof Encounter && $attachable->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist', 'assistant');
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        $owner = $attachment->attachable;
        if ($owner instanceof Encounter && $owner->isLocked()) {
            return false;
        }
        return $user->hasRole('admin', 'dentist');
    }
}
