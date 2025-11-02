<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Model;

class ArticleCategory extends Model
{
    protected $fillable = ['name','slug','description','parent_id'];

    public function parent()   { return $this->belongsTo(ArticleCategory::class, 'parent_id'); }
    public function children() { return $this->hasMany(ArticleCategory::class, 'parent_id'); }
    public function articles() { return $this->belongsToMany(Article::class, 'article_category', 'article_categories_id', 'article_id'); }
}
