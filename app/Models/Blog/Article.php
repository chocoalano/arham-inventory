<?php

namespace App\Models\Blog;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Article extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'author_id','title','slug','type','main_image','video_url','audio_url',
        'excerpt','body','reading_time','status','is_featured','published_at',
        'meta_title','meta_description','meta','view_count','like_count',
    ];

    protected $casts = [
        'is_featured'  => 'boolean',
        'published_at' => 'datetime',
        'meta'         => 'array',
    ];

    /**
     * Auto-set author_id (user yang login) & auto-slug unik saat creating
     */
    protected static function booted(): void
    {
        static::creating(function (Article $article) {
            // isi author_id jika belum ada dan user sedang login
            if (blank($article->author_id) && Auth::check()) {
                $article->author_id = Auth::id();
            }

            // generate slug unik dari title jika slug kosong
            if (blank($article->slug) && filled($article->title)) {
                $base = Str::slug($article->title);
                $slug = $base;
                $i = 2;

                // pastikan slug unik
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }

                $article->slug = $slug;
            }
        });

        // (opsional) jika ingin slug ikut berubah saat title diubah dan slug kosong:
        static::updating(function (Article $article) {
            if (blank($article->slug) && $article->isDirty('title')) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    // ===================== RELATIONS =====================
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function media()
    {
        return $this->hasMany(ArticleMedia::class)->orderBy('sort_order');
    }

    /**
     * Pivot categories: table 'article_category' dengan kolom 'article_id' & 'category_id'
     */
    public function categories()
    {
        return $this->belongsToMany(
            ArticleCategory::class,
            'article_category',
            'article_id',
            'article_categories_id'
        );
    }

    /**
     * Pivot tags: table 'article_tag' dengan kolom 'article_id' & 'tag_id'
     */
    public function tags()
    {
        return $this->belongsToMany(
            ArticleTag::class,
            'article_tag',
            'article_id',
            'tag_id'
        );
    }

    // ===================== SCOPES =====================
    public function scopePublished($q)
    {
        return $q->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
