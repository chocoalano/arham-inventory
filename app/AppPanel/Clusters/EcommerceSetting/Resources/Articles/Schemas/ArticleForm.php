<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\Articles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama')->schema([
                    TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(200)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                            if (! $get('slug')) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(220)
                        ->rule(fn ($record) => Rule::unique('articles', 'slug')->ignore($record))
                        ->helperText('URL unik. Akan dibuat otomatis dari Judul bila kosong.'),

                    Select::make('type')
                        ->label('Tipe Konten')
                        ->options([
                            'image' => 'Gambar',
                            'gallery' => 'Galeri',
                            'audio' => 'Audio',
                            'video' => 'Video',
                        ])->required()->native(false),

                    FileUpload::make('main_image')
                        ->label('Gambar Utama')
                        ->image()
                        ->disk('public')
                        ->directory('articles')
                        ->imageEditor()
                        ->visibility('public')
                        ->hint('Digunakan untuk tipe image / thumbnail'),

                    TextInput::make('video_url')
                        ->label('Video URL (YouTube Embed)')
                        ->url()
                        ->visible(fn (Get $get) => $get('type') === 'video'),

                    TextInput::make('audio_url')
                        ->label('Audio URL (SoundCloud Embed)')
                        ->url()
                        ->visible(fn (Get $get) => $get('type') === 'audio'),

                    Textarea::make('excerpt')
                        ->label('Ringkasan')->rows(3),

                    // Pilih salah satu editor yang Anda gunakan:
                    RichEditor::make('body')
                        ->label('Konten')
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'bold', 'italic', 'strike', 'underline', 'link', 'h2', 'h3', 'blockquote', 'orderedList', 'bulletList', 'codeBlock', 'undo', 'redo',
                        ]),
                ])->columns(2)->columnSpanFull(),

                Section::make('Kategorisasi')->schema([
                    Select::make('categories')
                        ->label('Kategori')
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),

                    Select::make('tags')
                        ->label('Tag')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                ]),

                Section::make('Publikasi & SEO')->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'scheduled' => 'Scheduled',
                            'published' => 'Published',
                            'archived' => 'Archived',
                        ])->required()->native(false),

                    DateTimePicker::make('published_at')->label('Tanggal Terbit')->seconds(false),

                    Toggle::make('is_featured')->label('Featured'),

                    TextInput::make('reading_time')
                        ->label('Waktu Baca (menit)')
                        ->numeric()->minValue(0)->maxValue(300),

                    TextInput::make('meta_title')->label('Meta Title')->maxLength(255),
                    Textarea::make('meta_description')->label('Meta Description')->rows(2),
                    KeyValue::make('meta')->label('Meta Lainnya')->keyLabel('key')->valueLabel('value'),
                ])->columns(2),
            ]);
    }
}
