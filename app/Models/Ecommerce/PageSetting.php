<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageSetting extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'page_settings';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'page',
        'jumbotron',
        'content',
        'banner_image',
        'banner_image_alt',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * Atribut yang harus di-cast ke tipe bawaan.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'page' => 'string', // Meskipun ini ENUM, cast as string sudah cukup
    ];

    /**
     * Relasi ke item-item PageBuilder yang membentuk konten halaman.
     *
     * @return HasMany
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(PageBuilder::class, 'page_setting_id');
    }
}
