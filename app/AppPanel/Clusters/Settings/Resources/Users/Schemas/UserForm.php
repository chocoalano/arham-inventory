<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Form user')
                    ->description('Isi data akun pengguna dengan benar. Bidang bertanda wajib harus diisi.')
                    ->aside()
                    ->Schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->belowContent('Masukkan nama lengkap pengguna sesuai identitas.')
                            ->required(),

                        TextInput::make('email')
                            ->label('Alamat Email')
                            ->belowContent('Masukkan alamat email aktif yang valid.')
                            ->email()
                            ->required(),

                        DateTimePicker::make('email_verified_at')
                            ->label('Tanggal Verifikasi Email')
                            ->belowContent('Pilih tanggal dan waktu saat email diverifikasi.'),

                        TextInput::make('password')
                            ->label('Kata Sandi')
                            ->password()
                            ->revealable()
                            ->belowContent('Masukkan kata sandi untuk akun pengguna ini.')
                            ->required(),
                        Select::make('warehouse_id')
                            ->label('Pilih Penempatan kerja anda')
                            ->relationship('warehouse', 'name')
                            ->belowContent('Silahkan pilih penempatan kerja pengguna ini.')
                            ->required(),
                    ])
                    ->compact()
                    ->columns(2)
                    ->columnSpanFull()
            ]);
    }
}
