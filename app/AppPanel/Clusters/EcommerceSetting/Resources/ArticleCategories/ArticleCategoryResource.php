<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\ArticleCategories;

use App\AppPanel\Clusters\EcommerceSetting\EcommerceSettingCluster;
use App\AppPanel\Clusters\EcommerceSetting\Resources\ArticleCategories\Pages\ManageArticleCategories;
use App\Models\Blog\ArticleCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleCategoryResource extends Resource
{
    protected static ?string $model = ArticleCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = EcommerceSettingCluster::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(150)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, Get $get) {
                        if (!$get('slug')) {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(170)
                    ->rule(function ($record) {
                        // pastikan validasi mengarah ke tabel model aktual
                        $table = (new ArticleCategory)->getTable();
                        return Rule::unique($table, 'slug')->ignore($record);
                    })
                    ->helperText('URL unik. Akan dibuat otomatis dari Nama jika dikosongkan.'),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(3),

                Select::make('parent_id')
                    ->label('Induk')
                    ->relationship('parent', 'name') // butuh relasi parent() di model
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Biarkan kosong jika ini kategori tingkat atas.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('parent.name')
                    ->label('Induk')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('articles_count')
                    ->label('Jumlah Artikel')
                    ->counts('articles')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Filter Induk')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
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
            'index' => ManageArticleCategories::route('/'),
        ];
    }
}
