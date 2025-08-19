<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Global override: admin selalu boleh.
     * Kembalikan true untuk mengizinkan semua ability.
     * Kembalikan null agar lanjut ke method ability spesifik.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['admin'])) {
            return true;
        }

        return null;
    }

    /**
     * Lihat daftar user.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('viewAny-user');
    }

    /**
     * Lihat detail user tertentu.
     * Izinkan kalau punya permission ATAU itu dirinya sendiri.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('view-user')
            || $user->id === $model->id;
    }

    /**
     * Membuat user baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-user');
    }

    /**
     * Update user tertentu.
     * Izinkan kalau punya permission ATAU update profil dirinya sendiri.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('update-user')
            || $user->id === $model->id;
    }

    /**
     * Hapus user tertentu.
     * (Opsional) Cegah user menghapus dirinya sendiri.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermissionTo('delete-user');
    }
}
