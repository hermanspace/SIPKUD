<?php

namespace App\Policies;

use App\Models\Pengumuman;
use App\Models\User;

class PengumumanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasKabupatenScope();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pengumuman $pengumuman): bool
    {
        return $user->hasKabupatenScope();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasKabupatenScope();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pengumuman $pengumuman): bool
    {
        return $user->hasKabupatenScope();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pengumuman $pengumuman): bool
    {
        return $user->hasKabupatenScope();
    }
}
