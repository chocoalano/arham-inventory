<?php

namespace Database\Seeders;

// Gunakan model seeder yang telah Anda buat
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan seeder aplikasi.
     */
    public function run(): void
    {
        // Panggil seeder lain yang dibutuhkan di sini.
        // Seeder ini akan membuat role, permission, dan user dummy.
        $this->call([
            RolePermissionSeeder::class,
            // InventorySeeder::class,
        ]);

        // Catatan: Jika Anda tidak memerlukan user tambahan,
        // Anda bisa menghapus atau mengomentari baris di bawah.
        // Jika tidak, Anda dapat mengaktifkannya kembali.

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
