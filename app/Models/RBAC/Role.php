<?php

namespace App\Models\RBAC;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Role extends Model
{
    use HasFactory, LogsActivity;

    // Menentukan nama tabel jika berbeda dari konvensi Laravel.
    protected $table = 'roles';

    // Menentukan kolom yang dapat diisi secara massal.
    protected $fillable = ['name', 'label', 'desc'];

    /**
     * Relasi many-to-many ke model Permission.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * Relasi many-to-many ke model User.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()                 // log semua field fillable
            ->useLogName('peran pengguna')          // nama log
            ->dontSubmitEmptyLogs();        // hindari log kosong
    }
}
