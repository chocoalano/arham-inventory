<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Pages;

use App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;
}
