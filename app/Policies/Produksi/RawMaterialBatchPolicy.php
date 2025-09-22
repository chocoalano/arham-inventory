<?php

namespace App\Policies\Produksi;

use App\Models\RawMaterial\RawMaterialBatch;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RawMaterialBatchPolicy
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
        return $user->hasPermissionTo('viewAny-batch_material');
    }

    /**
     * Lihat detail user tertentu (instance) atau class-based.
     * - Class-based: $model === null
     * - Instance-based: $model instanceof User
     */
    public function view(User $user, ?RawMaterialBatch $model = null): bool
    {
        if ($model === null) {
            // class-based check, mis. Gate::authorize('view', User::class)
            return $user->hasPermissionTo('view-batch_material');
        }

        // instance-based check
        return $user->hasPermissionTo('view-batch_material') || $user->id === $model->id;
    }

    /**
     * Membuat user baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-batch_material');
    }

    /**
     * Update user.
     * - Class-based (null): izinkan jika punya permission update-user
     * - Instance-based: izinkan jika punya permission atau mengedit dirinya sendiri
     */
    public function update(User $user, ?RawMaterialBatch $model = null): bool
    {
        if ($model === null) {
            // class-based check, mis. Gate::authorize('update', User::class)
            return $user->hasPermissionTo('update-batch_material');
        }

        return $user->hasPermissionTo('update-batch_material') || $user->id === $model->id;
    }

    /**
     * Hapus user.
     * - Class-based (null): cukup cek permission delete-user
     * - Instance-based: cegah user menghapus dirinya sendiri
     */
    public function delete(User $user, ?RawMaterialBatch $model = null): bool
    {
        if ($model === null) {
            // class-based check, mis. Gate::authorize('delete', User::class)
            return $user->hasPermissionTo('delete-batch_material');
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermissionTo('delete-batch_material');
    }
}
