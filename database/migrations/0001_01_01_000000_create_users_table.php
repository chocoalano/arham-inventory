<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Primary key user');
            $table->foreignId('warehouse_id')
                  ->nullable()
                  ->comment('Relasi ke warehouse (nullable)');
            $table->string('name')->comment('Nama lengkap user');
            $table->string('email')->unique()->comment('Email unik user untuk login');
            $table->timestamp('email_verified_at')->nullable()->comment('Tanggal verifikasi email');
            $table->string('password')->comment('Password yang sudah di-hash');
            $table->rememberToken()->comment('Token remember me untuk login');
            $table->timestamps(); // created_at & updated_at
            $table->softDeletes()->comment('Tanggal soft delete');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('Email user sebagai primary key untuk reset password');
            $table->string('token')->comment('Token reset password');
            $table->timestamp('created_at')->nullable()->comment('Waktu token dibuat');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Primary key session ID');
            $table->foreignId('user_id')->nullable()->index()->comment('Relasi ke user (nullable jika guest)');
            $table->string('ip_address', 45)->nullable()->comment('Alamat IP (IPv4/IPv6)');
            $table->text('user_agent')->nullable()->comment('Informasi browser/device');
            $table->longText('payload')->comment('Data session yang disimpan');
            $table->integer('last_activity')->index()->comment('Waktu terakhir aktif (timestamp)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
