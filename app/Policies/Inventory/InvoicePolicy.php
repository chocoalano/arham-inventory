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
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Superadmin'])) {
            return true;
        }

        return null;
    }

    /* =========================
     * Core abilities
     * ========================= */

    /** List / Index */
    public function viewAny(User $user): bool
    {
        return $this->perm($user, 'viewAny-invoice');
    }

    /** View detail */
    public function view(User $user, Invoice $invoice): bool
    {
        return $this->perm($user, 'view-invoice') || $this->isOwner($user, $invoice);
    }

    /** Create */
    public function create(User $user): bool
    {
        return $this->perm($user, 'create-invoice');
    }

    /** Update / Edit */
    public function update(User $user, Invoice $invoice): bool
    {
        if (! $this->canMutateInvoice($invoice)) {
            return false;
        }

        return $this->perm($user, 'update-invoice') || $this->isOwner($user, $invoice);
    }

    /** Delete (soft delete) */
    public function delete(User $user, Invoice $invoice): bool
    {
        if (! $this->canMutateInvoice($invoice)) {
            return false;
        }

        return $this->perm($user, 'delete-invoice');
    }

    /** Bulk delete (soft delete) */
    public function deleteAny(User $user): bool
    {
        return $this->perm($user, 'deleteAny-invoice');
    }

    /** Restore (from soft delete) */
    public function restore(User $user, Invoice $invoice): bool
    {
        return $this->perm($user, 'restore-invoice');
    }

    /** Bulk restore */
    public function restoreAny(User $user): bool
    {
        return $this->perm($user, 'restoreAny-invoice');
    }

    /** Force delete (permanent) */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        if (! $this->canMutateInvoice($invoice)) {
            return false;
        }

        return $this->perm($user, 'forceDelete-invoice');
    }

    /** Bulk force delete (permanent) */
    public function forceDeleteAny(User $user): bool
    {
        return $this->perm($user, 'forceDeleteAny-invoice');
    }

    /** Replicate (duplicate record) */
    public function replicate(User $user, Invoice $invoice): bool
    {
        return $this->perm($user, 'replicate-invoice') || $this->isOwner($user, $invoice);
    }

    /** Export (custom) */
    public function export(User $user): bool
    {
        return $this->perm($user, 'export-invoice');
    }

    /** Import (custom) */
    public function import(User $user): bool
    {
        return $this->perm($user, 'import-invoice');
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
     * Owner/penanggung jawab invoice (opsional).
     * Aktif jika model punya kolom user_id / created_by.
     */
    private function isOwner(User $user, Invoice $invoice): bool
    {
        return (isset($invoice->user_id) && (int) $invoice->user_id === (int) $user->id)
            || (isset($invoice->created_by) && (int) $invoice->created_by === (int) $user->id);
    }

    /**
     * Guard opsional untuk mencegah perubahan pada invoice tertentu.
     * Sesuaikan dengan field yang Anda gunakan:
     *  - is_paid (bool)
     *  - status (mis. 'posted','final','closed','cancelled')
     *  - locked/reconciled_at (bila ada)
     *  - ada payment terkait (opsional)
     */
    private function canMutateInvoice(Invoice $invoice): bool
    {
        // Terkunci jika sudah lunas
        if (isset($invoice->is_paid) && (bool) $invoice->is_paid === true) {
            return false;
        }

        // Kunci berdasarkan status final
        if (isset($invoice->status) && in_array($invoice->status, ['posted', 'final', 'closed', 'cancelled'], true)) {
            return false;
        }

        // Jika Anda ingin kunci ketika sudah ada pembayaran terkait:
        if (method_exists($invoice, 'payments') && $invoice->payments()->exists()) {
            // Komentari baris berikut jika masih ingin bisa mengubah meski ada payment.
            // return false;
        }

        // Flag/kolom pengunci lain (opsional):
        if (isset($invoice->locked) && (bool) $invoice->locked === true) {
            return false;
        }
        if (isset($invoice->reconciled_at) && ! is_null($invoice->reconciled_at)) {
            return false;
        }

        return true;
    }
}
