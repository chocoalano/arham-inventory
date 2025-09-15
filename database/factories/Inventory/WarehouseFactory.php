<?php

namespace Database\Factories\Inventory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Inventory\Warehouse; // sesuaikan namespace model-mu

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        $cities = [
            ['city' => 'Jakarta',  'province' => 'DKI Jakarta'],
            ['city' => 'Bandung',  'province' => 'Jawa Barat'],
            ['city' => 'Surabaya', 'province' => 'Jawa Timur'],
            ['city' => 'Semarang', 'province' => 'Jawa Tengah'],
            ['city' => 'Denpasar', 'province' => 'Bali'],
        ];
        $pick = $this->faker->randomElement($cities);

        // Kode unik lintas-run
        $code = 'WH-' . Str::upper(Str::ulid()); // panjang ±29 char, <= 32 OK

        return [
            'code'        => $code,                                   // unique
            'name'        => "Gudang {$pick['city']} {$code}",        // unique (ikut code)
            'address'     => $this->faker->streetAddress(),
            'district'    => 'Kec. ' . $this->faker->streetName(),
            'city'        => $pick['city'],
            'province'    => $pick['province'],
            'postal_code' => $this->faker->numerify('#####'),
            'lat'         => $this->faker->optional()->randomFloat(7, -8.90, 6.30),
            'lng'         => $this->faker->optional()->randomFloat(7, 95.00, 141.00),
            'phone'       => '08' . $this->faker->numerify('##########'),
            'is_active'   => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }
}
