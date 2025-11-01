<?php
namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBuilder extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'page_builder';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'page_setting_id',
        'section',
        'content_type',
        'content_value',
    ];

    /**
     * Atribut yang harus di-cast ke tipe bawaan.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'section' => 'string', // ENUM
        'content_type' => 'string', // ENUM
    ];

    /**
     * Relasi ke PageSetting yang memiliki blok konten ini.
     *
     * @return BelongsTo
     */
    public function pageSetting(): BelongsTo
    {
        return $this->belongsTo(PageSetting::class, 'page_setting_id');
    }
}
