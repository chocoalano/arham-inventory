<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        // Kode unik berbasis ULID (praktis unik lintas-run)
        $ulid = Str::ulid()->toBase32();                 // ~26 char, alfanumerik
        $code = 'SUP-' . strtoupper($ulid);

        // Nama perusahaan readable + sematkan code agar pasti unik
        $baseCompany = $this->faker->company();
        $name = "{$baseCompany} {$code}";

        // Email dibuat dari slug + code → unik tanpa unique()
        $slug  = Str::slug($baseCompany, '-');
        $email = "sales+{$slug}-{$ulid}@example.test";

        // Koordinat: hanya angka atau null (tidak pernah '?')
        $lat = $this->faker->boolean(35) ? $this->faker->randomFloat(7, -8.9000000, 6.3000000) : null;
        $lng = $this->faker->boolean(35) ? $this->faker->randomFloat(7,  95.0000000, 141.0000000) : null;

        return [
            'code'         => $code,   // UNIQUE
            'name'         => $name,   // UNIQUE (ikut code)
            'contact_name' => $this->faker->name(),
            'phone'        => '08' . $this->faker->numerify('##########'),
            'email'        => $email,
            'address'      => $this->faker->streetAddress(),
            'district'     => 'Kec. ' . $this->faker->streetName(),
            'city'         => $this->faker->city(),
            'province'     => 'Jawa ' . $this->faker->randomElement(['Barat','Tengah','Timur']),
            'postal_code'  => $this->faker->numerify('#####'),
            'lat'          => $lat,
            'lng'          => $lng,
            'is_active'    => true,
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Supplier $s) {
            // Safeguard ekstra: jika (sangat jarang) nama sudah ada, tambah suffix pendek
            if ($this->nameExists($s->name)) {
                $suffix = strtoupper(substr(md5($s->code), 0, 4));
                $s->name = "{$s->name}-{$suffix}";
            }
        });
    }

    private function nameExists(string $name): bool
    {
        return DB::table('suppliers')->where('name', $name)->exists();
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }
}
