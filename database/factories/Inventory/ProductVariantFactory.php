<?php

namespace Database\Factories\Inventory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /** Pilihan readable untuk UI */
    private array $colors = ['Original', 'Matcha', 'Taro', 'Chocolate', 'Strawberry'];
    private array $sizes  = ['250g','500g','1kg','5kg'];

    /** Cache alokasi pasangan (color,size) per product dalam 1 proses seeding */
    private static array $allocCache = []; // [product_id => ['color|||size' => true]]

    public function definition(): array
    {
        $cost  = $this->faker->numberBetween(15000, 90000);
        $price = (int) round($cost * $this->faker->randomFloat(2, 1.2, 2.0), 0);

        return [
            // Jika Anda selalu memanggil ->for($product), biarkan null di sini juga tidak masalah.
            'product_id'  => Product::factory(),
            'sku_variant' => 'VR-' . Str::upper(Str::ulid()),
            // placeholder; akan dipilih unik di afterMaking saat product_id sudah ada
            'color'       => 'Original',
            'size'        => '250g',
            'barcode'     => null,
            'cost_price'  => $cost,
            'price'       => $price,
            'status'      => 'active',
        ];
    }

    public function inactive(): self { return $this->state(['status' => 'inactive']); }
    public function discontinued(): self { return $this->state(['status' => 'discontinued']); }

    public function configure()
    {
        return $this->afterMaking(function (ProductVariant $variant) {
            // Pastikan kombinasi dipilih SEBELUM insert
            $pid = $variant->product_id;

            if ($pid) {
                [$color, $size] = $this->pickUniqueColorSizeSafe($pid);
                $variant->color = $color;
                $variant->size  = $size;
            }

            // Barcode opsional 60%: deterministik dari sku_variant + cek DB
            if ($this->faker->boolean(60)) {
                $variant->barcode = $this->makeUniqueEan13FromSeed($variant->sku_variant);
            } else {
                $variant->barcode = null;
            }
        })
        ->afterCreating(function (ProductVariant $variant) {
            $pid = $variant->product_id;
            if (!$pid) return;

            // Safeguard tambahan (jarang terpicu, hanya jika terjadi race kondisi luar)
            if ($this->comboExists($pid, $variant->color, $variant->size, $variant->id)) {
                [$color, $size] = $this->pickUniqueColorSizeSafe($pid);
                $variant->color = $color;
                $variant->size  = $size;
                $variant->save();
            }

            if (!empty($variant->barcode) && $this->barcodeExists($variant->barcode, $variant->id)) {
                $variant->barcode = $this->makeUniqueEan13FromSeed($variant->sku_variant . '|retry|' . Str::random(4));
                $variant->save();
            }
        });
    }

    /**
     * Pilih pasangan (color,size) yang belum ada di DB & belum dialokasikan di proses ini.
     * Menandai alokasi di cache agar batch paralel tidak saling tabrak.
     */
    private function pickUniqueColorSizeSafe(int $productId): array
    {
        // siapkan cache
        if (!isset(self::$allocCache[$productId])) self::$allocCache[$productId] = [];

        // pasangan yang sudah ada di DB (termasuk yang soft-deleted pun tetap menghambat unique index DB)
        $usedDb = DB::table('product_variants')
            ->where('product_id', $productId)
            ->select('color', 'size')
            ->get()
            ->map(fn($r) => "{$r->color}|||{$r->size}")
            ->toArray();

        // bangun kandidat
        $candidates = [];
        foreach ($this->colors as $c) {
            foreach ($this->sizes as $s) {
                $candidates[] = [$c, $s];
            }
        }

        // filter yang belum dipakai DB & belum dialokasikan di proses ini
        $available = array_values(array_filter($candidates, function ($pair) use ($usedDb, $productId) {
            $key = $pair[0] . '|||' . $pair[1];
            return !in_array($key, $usedDb, true) && empty(self::$allocCache[$productId][$key]);
        }));

        if (!empty($available)) {
            [$color, $size] = $this->faker->randomElement($available);
            self::$allocCache[$productId]["$color|||$size"] = true;
            return [$color, $size];
        }

        // Fallback: semua kombinasi habis → buat size unik dengan suffix
        $color = $this->faker->randomElement($this->colors);
        $size  = $this->faker->randomElement($this->sizes) . '-' . Str::upper(Str::random(3));
        // tandai juga di cache untuk konsistensi proses
        self::$allocCache[$productId]["$color|||$size"] = true;
        return [$color, $size];
    }

    /** Cek kombinasi sudah ada (kecuali diri sendiri). */
    private function comboExists(int $productId, string $color, string $size, ?int $exceptId = null): bool
    {
        $q = DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('color', $color)
            ->where('size', $size);
        if ($exceptId) $q->where('id', '!=', $exceptId);
        return $q->exists();
    }

    /** Cek barcode sudah dipakai (kecuali diri sendiri). */
    private function barcodeExists(string $barcode, ?int $exceptId = null): bool
    {
        $q = DB::table('product_variants')
            ->where('barcode', $barcode)
            ->whereNotNull('barcode');
        if ($exceptId) $q->where('id', '!=', $exceptId);
        return $q->exists();
    }

    /** Buat EAN-13 valid & unik dari seed stabil (sku_variant) + cek DB. */
    private function makeUniqueEan13FromSeed(string $seed): string
    {
        $ean13 = $this->buildEan13($seed);
        $tries = 0;
        while ($this->barcodeExists($ean13) && $tries < 3) {
            $ean13 = $this->buildEan13($seed . '|salt|' . Str::random(4));
            $tries++;
        }
        return $ean13;
    }

    private function buildEan13(string $seed): string
    {
        // 899 (GS1 ID) + 9 digit dari seed + check digit
        $num = $this->numericFromSeed($seed);
        $base12 = '899' . str_pad($num, 9, '0', STR_PAD_LEFT);
        return $base12 . $this->ean13CheckDigit($base12);
    }

    /** Ubah seed menjadi 9 digit numerik stabil. */
    private function numericFromSeed(string $seed): string
    {
        // Gunakan hash hex, ambil digit saja, pad ke 9 digit
        $hex = hash('crc32b', $seed);
        $digits = preg_replace('/\D/', '', $hex);
        return str_pad(substr($digits, 0, 9), 9, '7');
    }

    /** Hitung check digit EAN-13. $base12 harus 12 digit numerik. */
    private function ean13CheckDigit(string $base12): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $base12[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $mod = $sum % 10;
        return $mod === 0 ? 0 : 10 - $mod;
    }
}
