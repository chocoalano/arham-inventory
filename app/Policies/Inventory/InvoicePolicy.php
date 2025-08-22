<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\Invoice;
use App\Models\User;

class InvoicePolicy
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
     * Lihat daftar invoice.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('viewAny-invoice');
    }

    /**
     * Lihat invoice tertentu.
     * Izinkan jika punya permission ATAU (opsional) invoice milik dirinya.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        $isOwner = isset($invoice->user_id) && $invoice->user_id === $user->id;

        return $user->hasPermissionTo('view-invoice') || $isOwner;
    }

    /**
     * Membuat invoice.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-invoice');
    }

    /**
     * Update invoice.
     * Izinkan jika punya permission ATAU (opsional) invoice milik dirinya.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        $isOwner = isset($invoice->user_id) && $invoice->user_id === $user->id;

        return $user->hasPermissionTo('update-invoice') || $isOwner;
    }

    /**
     * Hapus invoice.
     * (Opsional) tambahkan guard agar tidak bisa hapus invoice final/paid jika diperlukan.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Contoh guard opsional:
        // if (in_array($invoice->status, ['paid', 'posted', 'final'])) {
        //     return false;
        // }

        return $user->hasPermissionTo('delete-invoice');
    }
}
