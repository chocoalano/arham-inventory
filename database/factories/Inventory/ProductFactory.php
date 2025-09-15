<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductImage;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $brands = ['Miwa', 'Sativa', 'Kopiko', 'Nirvana', 'Aurora'];
        $models = ['Classic', 'Premium', 'Signature', 'Lite', 'Pro'];

        $name = $this->faker->randomElement([
            'Matcha Powder 1kg', 'Taro Milk 1kg', 'Chocolate Drink 1kg',
            'Vanilla Latte 500g', 'Strawberry Smoothie 1kg'
        ]);

        return [
            'supplier_id' => $this->faker->boolean(80) ? Supplier::factory() : null,
            'sku'         => strtoupper($this->faker->unique()->bothify('PRD-#####')),
            'name'        => $name,
            'model'       => $this->faker->optional()->randomElement($models),
            'brand'       => $this->faker->optional()->randomElement($brands),
            'description' => $this->faker->optional()->paragraph(),
            'is_active'   => true,
        ];
    }

    /** Cepat bikin produk dengan N varian & M gambar */
    public function withVariants(int $count = 3): self
    {
        return $this->afterCreating(function (Product $product) use ($count) {
            ProductVariant::factory($count)->for($product)->create();
        });
    }

    public function withImages(int $count = 2): self
    {
        return $this->afterCreating(function (Product $product) use ($count) {
            ProductImage::factory($count)->for($product)->create();
        });
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }
}
