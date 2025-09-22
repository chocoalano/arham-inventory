<?php

namespace App\Policies\Accounting;

use App\Models\Finance\ProductAccountLink;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductAccountLinkPolicy
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
        return $user->hasPermissionTo('viewAny-product_link');
    }

    /**
     * Lihat detail user tertentu (instance) atau class-based.
     * - Class-based: $model === null
     * - Instance-based: $model instanceof User
     */
    public function view(User $user, ?ProductAccountLink $model = null): bool
    {
        if ($model === null) {
            // class-based check, mis. Gate::authorize('view', User::class)
            return $user->hasPermissionTo('view-product_link');
        }

        // instance-based check
        return $user->hasPermissionTo('view-product_link') || $user->id === $model->id;
    }

    /**
     * Membuat user baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-product_link');
    }

    /**
     * Update user.
     * - Class-based (null): izinkan jika punya permission update-user
     * - Instance-based: izinkan jika punya permission atau mengedit dirinya sendiri
     */
    public function update(User $user, ?ProductAccountLink $model = null): bool
    {
        if ($model === null) {
            // class-based check, mis. Gate::authorize('update', User::class)
            return $user->hasPermissionTo('update-product_link');
        }

        return $user->hasPermissionTo('update-product_link') || $user->id === $model->id;
    }

    /**
     * Hapus user.
     * - Class-based (null): cukup cek permission delete-user
     * - Instance-based: cegah user menghapus dirinya sendiri
     */
    public function delete(User $user, ?ProductAccountLink $model = null): bool
    {
        if ($model === null) {
            // class-based check, mis. Gate::authorize('delete', User::class)
            return $user->hasPermissionTo('delete-product_link');
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermissionTo('delete-product_link');
    }
}
