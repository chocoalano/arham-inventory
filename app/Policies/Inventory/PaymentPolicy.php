<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Admin bypass semua ability.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['Superadmin'])) {
            return true;
        }

        return null;
    }

    /**
     * Lihat daftar payment.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('viewAny-payment');
    }

    /**
     * Lihat payment tertentu.
     * Izinkan jika punya permission ATAU (opsional) payment milik dirinya.
     */
    public function view(User $user, Payment $payment): bool
    {
        $isOwner = (isset($payment->user_id) && $payment->user_id === $user->id)
            || (isset($payment->created_by) && $payment->created_by === $user->id);

        return $user->hasPermissionTo('view-payment') || $isOwner;
    }

    /**
     * Membuat payment.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-payment');
    }

    /**
     * Update payment.
     * Izinkan jika punya permission ATAU (opsional) payment milik dirinya.
     * (Opsional) Larang update jika status sudah final/posted/settled.
     */
    public function update(User $user, Payment $payment): bool
    {
        // Guard opsional: payment final tidak bisa diubah
        if (isset($payment->status) && in_array($payment->status, ['posted', 'settled', 'final'], true)) {
            return false;
        }

        $isOwner = (isset($payment->user_id) && $payment->user_id === $user->id)
            || (isset($payment->created_by) && $payment->created_by === $user->id);

        return $user->hasPermissionTo('update-payment') || $isOwner;
    }

    /**
     * Hapus payment.
     * (Opsional) Larang hapus jika status sudah final/posted/settled.
     */
    public function delete(User $user, Payment $payment): bool
    {
        if (isset($payment->status) && in_array($payment->status, ['posted', 'settled', 'final'], true)) {
            return false;
        }

        return $user->hasPermissionTo('delete-payment');
    }
}
