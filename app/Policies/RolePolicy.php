<?php

namespace App\Policies;

use App\Models\RBAC\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Admin bypass semua ability.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['admin'])) {
            return true;
        }

        return null;
    }

    /**
     * Lihat daftar role.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('viewAny-role');
    }

    /**
     * Lihat detail role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('view-role');
    }

    /**
     * Membuat role baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-role');
    }

    /**
     * Update role.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('update-role');
    }

    /**
     * Hapus role.
     * (Opsional) Lindungi role tertentu seperti 'admin'.
     */
    public function delete(User $user, Role $role): bool
    {
        // Lindungi role 'admin' agar tidak terhapus
        if (strtolower($role->name) === 'admin') {
            return false;
        }

        // (Opsional) Cegah hapus jika role masih dipakai user
        // Jika di model Role ada relasi users(): BelongsToMany
        if (method_exists($role, 'users') && $role->users()->exists()) {
            return false;
        }

        return $user->hasPermissionTo('delete-role');
    }
}
