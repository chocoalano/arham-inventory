<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Pages;

use App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\ArticleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewArticle extends ViewRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
