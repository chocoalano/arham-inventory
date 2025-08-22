<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\ProductVariant;
use App\Models\User;

class ProductVariantPolicy
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
     * Lihat daftar product variant.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('viewAny-productVariant');
    }

    /**
     * Lihat product variant tertentu.
     */
    public function view(User $user, ProductVariant $productVariant): bool
    {
        return $user->hasPermissionTo('view-productVariant');
    }

    /**
     * Membuat product variant.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-productVariant');
    }

    /**
     * Update product variant.
     * (Opsional) Izinkan owner mengubah varian miliknya jika ada kolom owner.
     */
    public function update(User $user, ProductVariant $productVariant): bool
    {
        $isOwner = (isset($productVariant->user_id) && $productVariant->user_id === $user->id)
            || (isset($productVariant->created_by) && $productVariant->created_by === $user->id);

        return $user->hasPermissionTo('update-productVariant') || $isOwner;
    }

    /**
     * Hapus product variant.
     * (Opsional) Cegah hapus jika masih dipakai (punya stok/transaksi).
     */
    public function delete(User $user, ProductVariant $productVariant): bool
    {
        if (
            (method_exists($productVariant, 'stocks') && $productVariant->stocks()->exists()) ||
            (method_exists($productVariant, 'invoiceItems') && $productVariant->invoiceItems()->exists()) ||
            (method_exists($productVariant, 'orderItems') && $productVariant->orderItems()->exists())
        ) {
            return false;
        }

        return $user->hasPermissionTo('delete-productVariant');
    }
}
