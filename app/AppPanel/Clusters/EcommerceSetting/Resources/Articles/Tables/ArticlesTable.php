<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('main_image')->label('Cover')->square(),
                TextColumn::make('title')->label('Judul')->searchable()->limit(40)->wrap(),
                TextColumn::make('author.name')->label('Penulis')->sortable()->toggleable(),
                BadgeColumn::make('type')->label('Tipe')->colors([
                    'primary' => 'image',
                    'warning' => 'gallery',
                    'info'    => 'audio',
                    'success' => 'video',
                ])->sortable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('published_at')->label('Terbit')
                    ->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('view_count')->label('Views')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'image'=>'Image','gallery'=>'Gallery','audio'=>'Audio','video'=>'Video',
                ]),
                SelectFilter::make('status')->options([
                    'draft'=>'Draft','scheduled'=>'Scheduled','published'=>'Published','archived'=>'Archived',
                ]),
                Filter::make('featured')->label('Featured')
                    ->query(fn ($q) => $q->where('is_featured', true)),
                Filter::make('published_only')->label('Hanya Published')
                    ->query(fn ($q) => $q->published()),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]);
    }
}
