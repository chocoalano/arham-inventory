<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $samples = [
            ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'johndoe@example.com', 'phone' => '081234567890'],
            ['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'janedoe@example.com', 'phone' => '081234567891'],
            ['first_name' => 'Andi', 'last_name' => 'Saputra', 'email' => 'andi.saputra@example.com', 'phone' => '081234567892'],
            ['first_name' => 'Siti', 'last_name' => 'Nur', 'email' => 'siti.nur@example.com', 'phone' => '081234567893'],
            ['first_name' => 'Budi', 'last_name' => 'Santoso', 'email' => 'budi.santoso@example.com', 'phone' => '081234567894'],
        ];

        foreach ($samples as $row) {
            Customer::create([
                'uuid' => (string) Str::uuid(),
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'password' => Hash::make('password'),
                'billing_address' => [
                    'street' => 'Jl. Contoh No.1',
                    'city' => 'Jakarta',
                    'postal_code' => '12345',
                    'country' => 'ID'
                ],
                'shipping_address' => [
                    'street' => 'Jl. Contoh No.1',
                    'city' => 'Jakarta',
                    'postal_code' => '12345',
                    'country' => 'ID'
                ],
                'company' => null,
                'vat_number' => null,
                'total_spent' => 0,
                'orders_count' => 0,
                'loyalty_points' => 0,
                'preferred_payment_method' => null,
                'metadata' => [],
                'is_active' => true,
            ]);
        }
    }
}
