<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\Supplier;
use App\Models\User;

class SupplierPolicy
{
    /**
     * Superadmin bypass semua ability.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['Superadmin'])) {
            return true;
        }

        return null;
    }

    /**
     * Lihat daftar supplier.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('viewAny-product');
    }

    /**
     * Lihat supplier tertentu.
     */
    public function view(User $user): bool
    {
        return $user->hasPermissionTo('view-product');
    }

    /**
     * Membuat supplier baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-product');
    }

    /**
     * Update supplier.
     * Opsional: izinkan owner mengubah data yang ia buat sendiri
     * jika model punya kolom user_id / created_by.
     */
    public function update(User $user): bool
    {
        return $user->hasPermissionTo('update-product');
    }

    /**
     * Hapus supplier.
     * Opsional: cegah hapus bila masih terkait data penting.
     */
    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('delete-product');
    }
}
