<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\PageSettings;

use App\AppPanel\Clusters\EcommerceSetting\EcommerceSettingCluster;
use App\AppPanel\Clusters\EcommerceSetting\Resources\PageSettings\Pages\ManagePageSettings;
use App\Models\Ecommerce\PageSetting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section; // Form layout (Schema API)
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PageSettingResource extends Resource
{
    protected static ?string $model = PageSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = EcommerceSettingCluster::class;

    /* ===================== FORM (Schema API v4) ===================== */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Halaman')
                ->schema([
                    FormSelect::make('page')
                        ->label('Page')
                        ->required()
                        ->options(self::getPageOptions())
                        ->searchable(),
                ])
                ->columns(2),

            Section::make('Konten')
                ->schema([
                    RichEditor::make('jumbotron')
                        ->label('Jumbotron')
                        ->toolbarButtons([
                            'bold','italic','underline','strike',
                            'bulletList','orderedList','blockquote',
                            'link','redo','undo',
                        ])
                        ->columnSpanFull(),

                    RichEditor::make('content')
                        ->label('Content')
                        ->columnSpanFull(),
                ]),

            Section::make('Banner')
                ->schema([
                    FileUpload::make('banner_image')
                        ->label('Banner Image')
                        ->image()
                        ->directory('banners')
                        ->visibility('public')
                        ->imageEditor()
                        ->openable()
                        ->downloadable(),

                    TextInput::make('banner_image_alt')
                        ->label('Alt Text')
                        ->maxLength(150),
                ])
                ->columns(2),

            Section::make('SEO')
                ->schema([
                    Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->rows(3)
                        ->maxLength(255),

                    TagsInput::make('meta_keywords')
                        ->label('Meta Keywords')
                        ->separator(',')
                        ->placeholder('tulis lalu Enter…'),
                ])
                ->columns(2),
        ]);
    }

    /* ===================== INFOLIST (Schema API v4) ===================== */
    public static function infolist(Schema $schema): Schema
    {
        // Tanpa InfoSection: gunakan entries langsung (komponen yang pasti ada di v4)
        return $schema->components([
            TextEntry::make('page')->label('Page')->badge(),

            ImageEntry::make('banner_image')
                ->label('Banner')
                ->imageHeight(120),

            TextEntry::make('banner_image_alt')->label('Alt'),

            TextEntry::make('meta_description')
                ->label('Meta Description')
                ->wrap(),

            TextEntry::make('meta_keywords')
                ->label('Keywords'),

            // Render HTML aman dari database (gunakan ->html()).
            // Pastikan konten sudah disanitasi di sisi admin jika diperlukan.
            TextEntry::make('jumbotron')
                ->label('Jumbotron')
                ->html(),

            TextEntry::make('content')
                ->label('Content')
                ->html(),

            TextEntry::make('updated_at')
                ->dateTime('d M Y H:i')
                ->label('Updated'),
        ]);
    }

    /* ===================== TABLE ===================== */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('page')
                    ->label('Page')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                ImageColumn::make('banner_image')
                    ->label('Banner')
                    ->height(40),

                TextColumn::make('banner_image_alt')
                    ->label('Alt')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('meta_description')
                    ->label('Meta Description')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('meta_keywords')
                    ->label('Keywords')
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('page')
                    ->options(self::getPageOptions())
                    ->label('Page'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePageSettings::route('/'),
        ];
    }

    /** Opsi untuk field "page" — tanpa enum, fallback statis. */
    protected static function getPageOptions(): array
    {
        return [
            'home'           => 'Home',
            'shop'           => 'Shop',
            'product_detail' => 'Product Detail',
            'cart'           => 'Cart',
            'checkout'       => 'Checkout',
            'about'          => 'About',
            'contact'        => 'Contact',
        ];
    }
}
