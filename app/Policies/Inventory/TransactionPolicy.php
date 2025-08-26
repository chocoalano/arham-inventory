<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;

class TransactionPolicy
{
    /**
     * Roles/status constants & time windows.
     */
    private const ADMIN_ROLE = 'admin';

    // Status yang mengunci update (tidak boleh diubah).
    private const LOCKED_FOR_UPDATE = ['posted', 'cancelled'];

    // Status yang mengunci penghapusan permanen (force delete) / delete.
    private const LOCKED_FOR_DELETE = ['posted', 'settled', 'final'];

    // Batas waktu admin boleh update (≤ 1 jam sejak dibuat).
    private const ADMIN_UPDATE_WINDOW_MINUTES = 60;

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
     * Lihat daftar transaksi.
     */
    public function viewAny(User $user): bool
    {
        return $this->perm($user, 'viewAny-transaction');
    }

    /**
     * Lihat transaksi tertentu.
     * Izinkan jika punya permission ATAU pemilik data.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $this->perm($user, 'view-transaction') || $this->isOwner($user, $transaction);
    }

    /**
     * Membuat transaksi.
     */
    public function create(User $user): bool
    {
        return $this->perm($user, 'create-transaction');
    }

    /**
     * Update transaksi.
     * Aturan:
     * - Jika status termasuk LOCKED_FOR_UPDATE → tidak boleh siapapun.
     * - Jika punya role admin → hanya boleh bila usia data ≤ 1 jam sejak created_at.
     * - Selain admin → harus punya permission 'update-transaction' atau pemilik data.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        if ($this->statusIn($transaction, self::LOCKED_FOR_UPDATE)) {
            return false;
        }

        $isAdmin = $this->isAdmin($user);

        if ($isAdmin) {
            return $this->withinAdminWindow($transaction);
        }

        return $this->perm($user, 'update-transaction') || $this->isOwner($user, $transaction);
    }

    /**
     * Hapus (soft delete) transaksi.
     * Dilarang bila status termasuk LOCKED_FOR_DELETE.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        if ($this->statusIn($transaction, self::LOCKED_FOR_DELETE)) {
            return false;
        }

        return $this->perm($user, 'delete-transaction');
    }

    /**
     * Hapus massal (soft delete).
     * Catatan: enforcement status biasanya pada level query/aksi masing-masing record,
     * namun policy ini mengizinkan aksi bulk jika punya permission.
     */
    public function deleteAny(User $user): bool
    {
        return $this->perm($user, 'deleteAny-transaction');
    }

    /**
     * Restore (dari soft delete).
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        return $this->perm($user, 'restore-transaction');
    }

    /**
     * Restore massal.
     */
    public function restoreAny(User $user): bool
    {
        return $this->perm($user, 'restoreAny-transaction');
    }

    /**
     * Hapus permanen (force delete).
     * Dilarang bila status termasuk LOCKED_FOR_DELETE.
     * (Biasanya hanya untuk record yang sudah soft-deleted).
     */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        if ($this->statusIn($transaction, self::LOCKED_FOR_DELETE)) {
            return false;
        }

        return $this->perm($user, 'forceDelete-transaction');
    }

    /**
     * Hapus permanen massal.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $this->perm($user, 'forceDeleteAny-transaction');
    }

    /**
     * Replicate (duplikasi record).
     */
    public function replicate(User $user, Transaction $transaction): bool
    {
        return $this->perm($user, 'replicate-transaction') || $this->isOwner($user, $transaction);
    }

    /**
     * Export data (custom ability untuk Filament export).
     */
    public function export(User $user): bool
    {
        return $this->perm($user, 'export-transaction');
    }

    /**
     * Import data (custom ability).
     */
    public function import(User $user): bool
    {
        return $this->perm($user, 'import-transaction');
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

    private function isOwner(User $user, Transaction $transaction): bool
    {
        return (isset($transaction->user_id) && (int) $transaction->user_id === (int) $user->id)
            || (isset($transaction->created_by) && (int) $transaction->created_by === (int) $user->id);
    }

    private function isAdmin(User $user): bool
    {
        if (method_exists($user, 'hasRole')) {
            return (bool) $user->hasRole(self::ADMIN_ROLE);
        }
        // fallback lain jika ada atribut
        return isset($user->role) && $user->role === self::ADMIN_ROLE
            || isset($user->is_admin) && (bool) $user->is_admin;
    }

    private function withinAdminWindow(Transaction $transaction): bool
    {
        $createdAt = $transaction->created_at ?? null;

        if ($createdAt instanceof Carbon) {
            return $createdAt->gt(now()->subMinutes(self::ADMIN_UPDATE_WINDOW_MINUTES));
        }

        if (! is_null($createdAt)) {
            try {
                return Carbon::parse($createdAt)->gt(now()->subMinutes(self::ADMIN_UPDATE_WINDOW_MINUTES));
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }

    private function statusIn(Transaction $transaction, array $statuses): bool
    {
        $status = $transaction->status ?? null;
        return $status !== null && in_array($status, $statuses, true);
    }
}
