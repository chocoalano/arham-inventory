<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Model;

class ArticleMedia extends Model
{
    protected $fillable = [
        'article_id','type','url','title','alt','caption','meta','sort_order',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
