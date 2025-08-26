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
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Superadmin'])) {
            return true;
        }

        return null;
    }

    /**
     * Lihat daftar gudang (Index/List).
     */
    public function viewAny(User $user): bool
    {
        return $this->perm($user, 'viewAny-warehouse');
    }

    /**
     * Lihat detail gudang (View).
     */
    public function view(User $user, Warehouse $warehouse): bool
    {
        return $this->perm($user, 'view-warehouse') || $this->isOwner($user, $warehouse);
    }

    /**
     * Membuat gudang baru (Create).
     */
    public function create(User $user): bool
    {
        return $this->perm($user, 'create-warehouse');
    }

    /**
     * Update gudang (Edit).
     */
    public function update(User $user, Warehouse $warehouse): bool
    {
        return $this->perm($user, 'update-warehouse') || $this->isOwner($user, $warehouse);
    }

    /**
     * Hapus (soft delete) gudang (Delete).
     * Ditolak jika masih punya relasi penting (stok/transaksi/pergerakan).
     */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        if (! $this->canDeleteWarehouse($warehouse)) {
            return false;
        }

        return $this->perm($user, 'delete-warehouse');
    }

    /**
     * Hapus massal (soft delete) (Bulk Delete).
     * Catatan: pemeriksaan relasi per-record sebaiknya dilakukan pada proses eksekusi.
     */
    public function deleteAny(User $user): bool
    {
        return $this->perm($user, 'deleteAny-warehouse');
    }

    /**
     * Restore (dari soft delete).
     */
    public function restore(User $user, Warehouse $warehouse): bool
    {
        return $this->perm($user, 'restore-warehouse');
    }

    /**
     * Restore massal.
     */
    public function restoreAny(User $user): bool
    {
        return $this->perm($user, 'restoreAny-warehouse');
    }

    /**
     * Hapus permanen (force delete).
     * Ditolak jika masih punya relasi penting.
     */
    public function forceDelete(User $user, Warehouse $warehouse): bool
    {
        if (! $this->canDeleteWarehouse($warehouse)) {
            return false;
        }

        return $this->perm($user, 'forceDelete-warehouse');
    }

    /**
     * Hapus permanen massal.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $this->perm($user, 'forceDeleteAny-warehouse');
    }

    /**
     * Replicate (duplikasi record).
     */
    public function replicate(User $user, Warehouse $warehouse): bool
    {
        return $this->perm($user, 'replicate-warehouse') || $this->isOwner($user, $warehouse);
    }

    /**
     * Export data (custom).
     */
    public function export(User $user): bool
    {
        return $this->perm($user, 'export-warehouse');
    }

    /**
     * Import data (custom).
     */
    public function import(User $user): bool
    {
        return $this->perm($user, 'import-warehouse');
    }

    /* =========================
     * Helpers
     * ========================= */

    private function perm(User $user, string $permission): bool
    {
        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
            return true;
        }

        return $user->can($permission);
    }

    private function isOwner(User $user, Warehouse $warehouse): bool
    {
        return (isset($warehouse->user_id) && (int) $warehouse->user_id === (int) $user->id)
            || (isset($warehouse->manager_id) && (int) $warehouse->manager_id === (int) $user->id);
    }

    /**
     * Cek apakah gudang aman dihapus/force delete.
     * - Tidak punya stok terkait
     * - Tidak tersangkut transaksi/movement
     * (Defensif: hanya cek relasi yang tersedia).
     */
    private function canDeleteWarehouse(Warehouse $warehouse): bool
    {
        // Relasi stok (mis. warehouseVariantStocks / stocks)
        if (method_exists($warehouse, 'variantStocks') && $warehouse->variantStocks()->exists()) {
            return false;
        }
        if (method_exists($warehouse, 'stocks') && $warehouse->stocks()->exists()) {
            return false;
        }

        // Relasi transaksi sebagai sumber/tujuan
        if (method_exists($warehouse, 'sourceTransactions') && $warehouse->sourceTransactions()->exists()) {
            return false;
        }
        if (method_exists($warehouse, 'destinationTransactions') && $warehouse->destinationTransactions()->exists()) {
            return false;
        }

        // Relasi inventory movements (from/to)
        if (method_exists($warehouse, 'fromMovements') && $warehouse->fromMovements()->exists()) {
            return false;
        }
        if (method_exists($warehouse, 'toMovements') && $warehouse->toMovements()->exists()) {
            return false;
        }

        return true;
    }
}
