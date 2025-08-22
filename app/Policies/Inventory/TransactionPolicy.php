<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\Transaction;
use App\Models\User;

class TransactionPolicy
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
     * Lihat daftar transaksi.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('viewAny-transaction');
    }

    /**
     * Lihat transaksi tertentu.
     * Izinkan jika punya permission ATAU (opsional) transaksi milik dirinya.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        $isOwner = (isset($transaction->user_id) && $transaction->user_id === $user->id)
            || (isset($transaction->created_by) && $transaction->created_by === $user->id);

        return $user->hasPermissionTo('view-transaction') || $isOwner;
    }

    /**
     * Membuat transaksi.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-transaction');
    }

    /**
     * Update transaksi.
     * (Opsional) Larang update jika status sudah final/posted/settled.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        if (isset($transaction->status) && in_array($transaction->status, ['posted', 'settled', 'final'], true)) {
            return false;
        }

        $isOwner = (isset($transaction->user_id) && $transaction->user_id === $user->id)
            || (isset($transaction->created_by) && $transaction->created_by === $user->id);

        return $user->hasPermissionTo('update-transaction') || $isOwner;
    }

    /**
     * Hapus transaksi.
     * (Opsional) Larang hapus jika status sudah final/posted/settled.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        if (isset($transaction->status) && in_array($transaction->status, ['posted', 'settled', 'final'], true)) {
            return false;
        }

        return $user->hasPermissionTo('delete-transaction');
    }
}
