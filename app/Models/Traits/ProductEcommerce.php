<?php
namespace App\Models\Traits;

trait ProductEcommerce
{
    /**
     * Memformat jumlah pembayaran ke dalam mata uang yang mudah dibaca.
     *
     * @return string
     */
    public static function bangunKebijakan(): array
    {
        return [
            ['icon' => asset('ecommerce/images/icons/shield.webp'),  'teks' => 'Kebijakan Keamanan'],
            ['icon' => asset('ecommerce/images/icons/truck.webp'),   'teks' => 'Kebijakan Pengiriman'],
            ['icon' => asset('ecommerce/images/icons/compare.webp'), 'teks' => 'Kebijakan Pengembalian'],
        ];
    }
    public static function bangunHarga($minPrice, $maxCost): array
    {
        $fmt = fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

        if ($minPrice !== null && $maxCost !== null && $maxCost > 0 && $minPrice < $maxCost) {
            $persen = round((($maxCost - $minPrice) / $maxCost) * 100);
            return [
                'normal'        => (float) $maxCost,
                'normal_fmt'    => $fmt($maxCost),
                'diskon'        => (float) $minPrice,
                'diskon_fmt'    => $fmt($minPrice),
                'persen_diskon' => $persen,
                'label_diskon'  => "-{$persen}%",
            ];
        }

        $normal = $minPrice ?? $maxCost;
        return $normal !== null
            ? ['normal' => (float) $normal, 'normal_fmt' => $fmt($normal)]
            : [];
    }
}
