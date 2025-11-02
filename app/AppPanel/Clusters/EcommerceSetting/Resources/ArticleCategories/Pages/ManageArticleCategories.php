<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\ArticleCategories\Pages;

use App\AppPanel\Clusters\EcommerceSetting\Resources\ArticleCategories\ArticleCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageArticleCategories extends ManageRecords
{
    protected static string $resource = ArticleCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
