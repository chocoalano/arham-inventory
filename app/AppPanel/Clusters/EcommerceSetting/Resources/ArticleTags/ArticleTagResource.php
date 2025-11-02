<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\ArticleTags;

use App\AppPanel\Clusters\EcommerceSetting\EcommerceSettingCluster;
use App\AppPanel\Clusters\EcommerceSetting\Resources\ArticleTags\Pages\ManageArticleTags;
use App\Models\Blog\ArticleTag;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleTagResource extends Resource
{
    protected static ?string $model = ArticleTag::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $cluster = EcommerceSettingCluster::class;

    protected static ?string $navigationLabel = 'Article Tags';
    protected static ?string $modelLabel       = 'Article Tag';
    protected static ?string $pluralModelLabel = 'Article Tags';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Tag')->schema([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, Get $get) {
                        if (! $get('slug')) {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(150)
                    ->rule(function ($record) {
                        $table = (new ArticleTag)->getTable(); // mis. 'tags'
                        return Rule::unique($table, 'slug')->ignore($record);
                    })
                    ->helperText('URL unik. Otomatis dari Nama bila kosong.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')->toggleable()->searchable(),

                Tables\Columns\TextColumn::make('articles_count')
                    ->label('Jumlah Artikel')->counts('articles')->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordUrl(null)
            ->recordAction('view')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageArticleTags::route('/'),
        ];
    }
}
