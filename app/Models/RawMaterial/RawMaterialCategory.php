<?php

namespace App\Models\RawMaterial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RawMaterialCategory extends Model
{
    use HasFactory;

    protected $table = 'raw_material_categories';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'slug',
        'image_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Route model binding by slug.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Auto generate & keep slug unique.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            // Jika slug kosong → buat dari name
            if (blank($model->slug) && filled($model->name)) {
                $model->slug = static::generateUniqueSlug($model->name);
            }

            // Jika slug diisi manual → tetap dipastikan unik
            if ($model->isDirty('slug') && filled($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->slug, valueIsSlug: true);
            }
        });

        static::updating(function (self $model) {
            // Jika name berubah & slug tidak diubah manual, regen slug dari name
            if ($model->isDirty('name') && ! $model->isDirty('slug')) {
                $model->slug = static::generateUniqueSlug($model->name, valueIsSlug: false, ignoreId: $model->getKey());
            }

            // Jika slug diubah manual, tetap pastikan unik
            if ($model->isDirty('slug') && filled($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->slug, valueIsSlug: true, ignoreId: $model->getKey());
            }
        });
    }

    /**
     * Pastikan slug unik dengan menambahkan suffix -2, -3, dst jika perlu.
     */
    protected static function generateUniqueSlug(string $value, bool $valueIsSlug = false, $ignoreId = null): string
    {
        $base = $valueIsSlug ? Str::slug($value) : Str::slug($value);
        $slug = $base ?: Str::random(6);

        $i = 2;
        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /**
     * Accessor agar image_url selalu berupa URL publik.
     * - Jika sudah URL penuh → kembalikan apa adanya
     * - Jika path storage → jadikan asset('storage/...')
     */
    public function getImageUrlAttribute($value): ?string
    {
        if (blank($value)) {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        return asset('storage/' . ltrim($value, '/'));
    }
}
