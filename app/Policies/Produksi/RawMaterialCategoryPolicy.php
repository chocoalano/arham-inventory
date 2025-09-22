<?php

namespace App\Policies\Produksi;

use App\Models\RawMaterial\RawMaterialCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RawMaterialCategoryPolicy
{
    /**
     * Global override: Superadmin selalu boleh.
     * return true => bypass semua ability
     * return null => lanjut ke method ability spesifik
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['Superadmin'])) {
            return true;
        }

        return null;
    }

    /**
     * Lihat daftar user.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('viewAny-category_material');
    }

    /**
     * Lihat detail user tertentu (instance) atau class-based.
     * - Class-based: $model === null
     * - Instance-based: $model instanceof User
     */
    public function view(User $user, ?RawMaterialCategory $model = null): bool
    {
        if ($model === null) {
            // class-based check, mis. Gate::authorize('view', User::class)
            return $user->hasPermissionTo('view-category_material');
        }

        // instance-based check
        return $user->hasPermissionTo('view-category_material') || $user->id === $model->id;
    }

    /**
     * Membuat user baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-category_material');
    }

    /**
     * Update user.
     * - Class-based (null): izinkan jika punya permission update-user
     * - Instance-based: izinkan jika punya permission atau mengedit dirinya sendiri
     */
    public function update(User $user, ?RawMaterialCategory $model = null): bool
    {
        if ($model === null) {
            // class-based check, mis. Gate::authorize('update', User::class)
            return $user->hasPermissionTo('update-category_material');
        }

        return $user->hasPermissionTo('update-category_material') || $user->id === $model->id;
    }

    /**
     * Hapus user.
     * - Class-based (null): cukup cek permission delete-user
     * - Instance-based: cegah user menghapus dirinya sendiri
     */
    public function delete(User $user, ?RawMaterialCategory $model = null): bool
    {
        if ($model === null) {
            // class-based check, mis. Gate::authorize('delete', User::class)
            return $user->hasPermissionTo('delete-category_material');
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermissionTo('delete-category_material');
    }
}
