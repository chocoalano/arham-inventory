<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\Articles;

use App\AppPanel\Clusters\EcommerceSetting\EcommerceSettingCluster;
use App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Pages\CreateArticle;
use App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Pages\EditArticle;
use App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Pages\ListArticles;
use App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Pages\ViewArticle;
use App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Schemas\ArticleForm;
use App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Schemas\ArticleInfolist;
use App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Tables\ArticlesTable;
use App\Models\Blog\Article;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = EcommerceSettingCluster::class;

    public static function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ArticleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'view' => ViewArticle::route('/{record}'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
