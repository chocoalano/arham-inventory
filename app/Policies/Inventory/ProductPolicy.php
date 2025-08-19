<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Admin bypass semua ability.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['admin'])) {
            return true;
        }

        return null;
    }

    /**
     * Lihat daftar produk.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('viewAny-product');
    }

    /**
     * Lihat produk tertentu.
     */
    public function view(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('view-product');
    }

    /**
     * Membuat produk baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-product');
    }

    /**
     * Update produk.
     * (Opsional) Izinkan owner mengubah produknya sendiri jika ada kolom owner.
     */
    public function update(User $user, Product $product): bool
    {
        $isOwner = (isset($product->user_id) && $product->user_id === $user->id)
            || (isset($product->created_by) && $product->created_by === $user->id);

        return $user->hasPermissionTo('update-product') || $isOwner;
    }

    /**
     * Hapus produk.
     * (Opsional) Cegah hapus jika masih dipakai (punya stok/transaksi).
     */
    public function delete(User $user, Product $product): bool
    {
        // Guard opsional: cegah hapus jika masih terkait data penting
        if (
            (method_exists($product, 'stocks') && $product->stocks()->exists()) ||
            (method_exists($product, 'invoiceItems') && $product->invoiceItems()->exists()) ||
            (method_exists($product, 'orderItems') && $product->orderItems()->exists())
        ) {
            return false;
        }

        return $user->hasPermissionTo('delete-product');
    }
}
