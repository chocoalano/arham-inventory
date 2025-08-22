<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\Warehouse;
use App\Models\User;

class WarehousePolicy
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
     * Lihat daftar gudang.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('viewAny-warehouse');
    }

    /**
     * Lihat detail gudang.
     */
    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermissionTo('view-warehouse');
    }

    /**
     * Membuat gudang baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-warehouse');
    }

    /**
     * Update gudang.
     * (Opsional) izinkan owner/penanggung jawab jika ada kolomnya.
     */
    public function update(User $user, Warehouse $warehouse): bool
    {
        $isOwner = (isset($warehouse->user_id) && $warehouse->user_id === $user->id)
            || (isset($warehouse->manager_id) && $warehouse->manager_id === $user->id);

        return $user->hasPermissionTo('update-warehouse') || $isOwner;
    }

    /**
     * Hapus gudang.
     * (Opsional) cegah hapus jika masih ada stok/relasi penting.
     */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        if (
            (method_exists($warehouse, 'stocks') && $warehouse->stocks()->exists())
        ) {
            return false;
        }

        return $user->hasPermissionTo('delete-warehouse');
    }
}
