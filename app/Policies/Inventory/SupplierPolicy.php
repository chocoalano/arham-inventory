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
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Superadmin'])) {
            return true;
        }

        return null;
    }

    /**
     * Lihat daftar supplier (Index/List).
     */
    public function viewAny(User $user): bool
    {
        return $this->perm($user, 'viewAny-supplier');
    }

    /**
     * Lihat detail supplier (View).
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $this->perm($user, 'view-supplier') || $this->isOwner($user, $supplier);
    }

    /**
     * Membuat supplier baru (Create).
     */
    public function create(User $user): bool
    {
        return $this->perm($user, 'create-supplier');
    }

    /**
     * Update supplier (Edit).
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $this->perm($user, 'update-supplier') || $this->isOwner($user, $supplier);
    }

    /**
     * Hapus (soft delete) supplier (Delete).
     * Ditolak jika masih punya relasi penting (produk/varian/transaksi).
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        if (! $this->canDeleteSupplier($supplier)) {
            return false;
        }

        return $this->perm($user, 'delete-supplier');
    }

    /**
     * Hapus massal (soft delete) (Bulk Delete).
     * Catatan: pengecekan relasi per-record sebaiknya dilakukan saat eksekusi bulk.
     */
    public function deleteAny(User $user): bool
    {
        return $this->perm($user, 'deleteAny-supplier');
    }

    /**
     * Restore dari soft delete (Restore).
     */
    public function restore(User $user, Supplier $supplier): bool
    {
        return $this->perm($user, 'restore-supplier');
    }

    /**
     * Restore massal.
     */
    public function restoreAny(User $user): bool
    {
        return $this->perm($user, 'restoreAny-supplier');
    }

    /**
     * Hapus permanen (force delete).
     * Ditolak jika masih punya relasi penting.
     */
    public function forceDelete(User $user, Supplier $supplier): bool
    {
        if (! $this->canDeleteSupplier($supplier)) {
            return false;
        }

        return $this->perm($user, 'forceDelete-supplier');
    }

    /**
     * Hapus permanen massal.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $this->perm($user, 'forceDeleteAny-supplier');
    }

    /**
     * Replicate (duplikasi record).
     */
    public function replicate(User $user, Supplier $supplier): bool
    {
        return $this->perm($user, 'replicate-supplier') || $this->isOwner($user, $supplier);
    }

    /**
     * Export data (custom).
     */
    public function export(User $user): bool
    {
        return $this->perm($user, 'export-supplier');
    }

    /**
     * Import data (custom).
     */
    public function import(User $user): bool
    {
        return $this->perm($user, 'import-supplier');
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

    /**
     * Opsional: jika model ada kolom user_id / created_by sebagai penanggung jawab.
     */
    private function isOwner(User $user, Supplier $supplier): bool
    {
        return (isset($supplier->user_id) && (int) $supplier->user_id === (int) $user->id)
            || (isset($supplier->created_by) && (int) $supplier->created_by === (int) $user->id);
    }

    /**
     * Cek apakah supplier aman dihapus/force delete.
     * - Tidak punya produk yang masih aktif/ada
     * - (Opsional) tidak terkait transaksi lain
     * Defensif: hanya cek relasi yang ada.
     */
    private function canDeleteSupplier(Supplier $supplier): bool
    {
        // Relasi products
        if (method_exists($supplier, 'products') && $supplier->products()->exists()) {
            return false;
        }

        // Jika ada relasi-relasi lain yang mengikat, tambahkan di sini:
        // if (method_exists($supplier, 'transactions') && $supplier->transactions()->exists()) {
        //     return false;
        // }

        return true;
    }
}
