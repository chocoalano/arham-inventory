<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\Payment;
use App\Models\User;

class PaymentPolicy
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
        return $this->perm($user, 'viewAny-payment');
    }

    /** View detail */
    public function view(User $user, Payment $payment): bool
    {
        return $this->perm($user, 'view-payment') || $this->isOwner($user, $payment);
    }

    /** Create */
    public function create(User $user): bool
    {
        return $this->perm($user, 'create-payment');
    }

    /** Update / Edit */
    public function update(User $user, Payment $payment): bool
    {
        // Jika ingin dikunci pada kondisi tertentu, aktifkan guard ini:
        if (! $this->canMutatePayment($payment)) {
            return false;
        }

        return $this->perm($user, 'update-payment') || $this->isOwner($user, $payment);
    }

    /** Delete (soft delete) */
    public function delete(User $user, Payment $payment): bool
    {
        if (! $this->canMutatePayment($payment)) {
            return false;
        }

        return $this->perm($user, 'delete-payment');
    }

    /** Bulk delete (soft delete) */
    public function deleteAny(User $user): bool
    {
        return $this->perm($user, 'deleteAny-payment');
    }

    /** Restore (from soft delete) */
    public function restore(User $user, Payment $payment): bool
    {
        return $this->perm($user, 'restore-payment');
    }

    /** Bulk restore */
    public function restoreAny(User $user): bool
    {
        return $this->perm($user, 'restoreAny-payment');
    }

    /** Force delete (permanent) */
    public function forceDelete(User $user, Payment $payment): bool
    {
        if (! $this->canMutatePayment($payment)) {
            return false;
        }

        return $this->perm($user, 'forceDelete-payment');
    }

    /** Bulk force delete (permanent) */
    public function forceDeleteAny(User $user): bool
    {
        return $this->perm($user, 'forceDeleteAny-payment');
    }

    /** Replicate (duplicate record) */
    public function replicate(User $user, Payment $payment): bool
    {
        return $this->perm($user, 'replicate-payment') || $this->isOwner($user, $payment);
    }

    /** Export (custom) */
    public function export(User $user): bool
    {
        return $this->perm($user, 'export-payment');
    }

    /** Import (custom) */
    public function import(User $user): bool
    {
        return $this->perm($user, 'import-payment');
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
     * Owner/penanggung jawab payment (opsional).
     * Aktif jika model punya kolom user_id / created_by.
     */
    private function isOwner(User $user, Payment $payment): bool
    {
        return (isset($payment->user_id) && (int) $payment->user_id === (int) $user->id)
            || (isset($payment->created_by) && (int) $payment->created_by === (int) $user->id);
    }

    /**
     * Guard opsional untuk mencegah perubahan pada payment tertentu.
     * - Contoh: jika payment sudah direkonsiliasi/terkunci.
     * - Karena skema Anda tidak menunjukkan field status, cek defensif terhadap kemungkinan atribut/relasi.
     *   Silakan sesuaikan dengan field yang Anda gunakan (mis. reconciled_at, locked, posted, dsb).
     */
    private function canMutatePayment(Payment $payment): bool
    {
        // Misal: kunci jika ada flag/kolom tertentu
        if (isset($payment->locked) && (bool) $payment->locked === true) {
            return false;
        }
        if (isset($payment->reconciled_at) && ! is_null($payment->reconciled_at)) {
            return false;
        }

        // Jika ingin mengunci ketika invoice terkait sudah "closed" atau "is_paid" true:
        if (method_exists($payment, 'invoice') && $payment->relationLoaded('invoice') ? $payment->invoice : $payment->invoice()->first()) {
            $invoice = $payment->invoice ?? $payment->invoice()->first();
            if ($invoice) {
                // Contoh kebijakan: jika invoice sudah "closed/final", jangan ubah payment
                if (isset($invoice->status) && in_array($invoice->status, ['closed', 'final'], true)) {
                    return false;
                }
                // Jika ingin mengunci ketika invoice ditandai lunas:
                // if (isset($invoice->is_paid) && (bool) $invoice->is_paid === true) {
                //     return false;
                // }
            }
        }

        return true;
    }
}
