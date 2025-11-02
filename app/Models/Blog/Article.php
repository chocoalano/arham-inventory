<?php

namespace App\Models\Blog;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    // RELATIONS
    public function author()     { return $this->belongsTo(User::class, 'author_id'); }
    public function media()      { return $this->hasMany(ArticleMedia::class)->orderBy('sort_order'); }
    public function categories() { return $this->belongsToMany(ArticleCategory::class, 'article_category', 'article_id', 'article_categories_id'); }
    public function tags()       { return $this->belongsToMany(ArticleTag::class, 'article_tag'); }

    // SCOPES
    public function scopePublished($q)
    {
        return $q->where('status','published')
                 ->whereNotNull('published_at')
                 ->where('published_at','<=', now());
    }
}
