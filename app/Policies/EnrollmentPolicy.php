<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, Enrollment $enrollment): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Enrollment $enrollment): bool
    {
        return $actor->isAdmin();
    }
}
