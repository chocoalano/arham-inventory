<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migrasi untuk membuat tabel.
     * Penjelasan: Metode ini dijalankan saat Anda menjalankan 'php artisan migrate'.
     */
    public function up(): void
    {
        // Tabel 'roles'
        // Digunakan untuk menyimpan daftar peran pengguna, seperti 'admin', 'editor', 'user'.
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nama unik untuk peran (contoh: admin, editor)');
            $table->string('label')->nullable()->comment('Nama yang lebih mudah dibaca (contoh: Administrator)');
            $table->longText('desc')->nullable()->comment('Deskripsi singkat tentang peran');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabel 'permission_groups'
        // Digunakan untuk mengelompokkan izin ke dalam kategori, misalnya 'Manajemen Pengguna', 'Manajemen Artikel'.
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nama unik untuk grup izin (contoh: users)');
            $table->string('label')->nullable()->comment('Nama yang mudah dibaca (contoh: Manajemen Pengguna)');
            $table->timestamps();
        });

        // Tabel 'permissions'
        // Digunakan untuk menyimpan izin spesifik, seperti 'create-user', 'edit-post'.
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            // PERBAIKAN UTAMA: Kolom 'permission_group_id' dibuat terlebih dahulu.
            // Helper 'foreignId()' secara otomatis membuat kolom unsignedBigInteger.
            // Helper 'constrained()' secara otomatis membuat foreign key ke tabel 'permission_groups'.
            $table->foreignId('permission_group_id')
                  ->constrained() // Akan membuat relasi ke tabel 'permission_groups'
                  ->cascadeOnDelete() // Jika grup izin dihapus, semua izin di dalamnya ikut terhapus.
                  ->comment('ID dari grup izin yang mengelompokkan izin ini');

            $table->string('name')->unique()->comment('Nama unik untuk izin (contoh: create-user)');
            $table->string('label')->nullable()->comment('Deskripsi yang mudah dibaca (contoh: Buat Pengguna Baru)');
            $table->timestamps();
        });

        // Tabel 'role_permission' (Tabel Pivot)
        // Menghubungkan peran dengan izin (relasi many-to-many).
        // Ini menentukan izin apa saja yang dimiliki oleh setiap peran.
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // Tabel 'user_role' (Tabel Pivot)
        // Menghubungkan pengguna dengan peran (relasi many-to-many).
        // Ini menentukan peran apa saja yang dimiliki oleh setiap pengguna.
        Schema::create('user_role', function (Blueprint $table) {
            // Asumsi tabel 'users' sudah ada saat migrasi ini dijalankan.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });
        // Tabel 'user_role' (Tabel Pivot)
        // Menghubungkan pengguna dengan peran (relasi many-to-many).
        // Ini menentukan peran apa saja yang dimiliki oleh setiap pengguna.
        Schema::create('user_permission', function (Blueprint $table) {
            // Asumsi tabel 'users' sudah ada saat migrasi ini dijalankan.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'permission_id']);
        });
    }

    /**
     * Kembalikan migrasi dengan menghapus tabel.
     * Penjelasan: Metode ini dijalankan saat Anda menjalankan 'php artisan migrate:rollback'.
     */
    public function down(): void
    {
        // Catatan: Urutan penghapusan harus dibalik dari urutan pembuatan.
        // Tabel pivot dan tabel yang memiliki foreign key harus dihapus terlebih dahulu.
        Schema::dropIfExists('user_permission');
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('permission_groups');
        Schema::dropIfExists('roles');
    }
};
