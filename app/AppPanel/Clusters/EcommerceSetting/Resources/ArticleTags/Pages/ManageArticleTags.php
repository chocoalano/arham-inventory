<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\ArticleTags\Pages;

use App\AppPanel\Clusters\EcommerceSetting\Resources\ArticleTags\ArticleTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageArticleTags extends ManageRecords
{
    protected static string $resource = ArticleTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
