<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Model;

class ArticleTag extends Model
{
    protected $fillable = ['name','slug'];

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }
}
