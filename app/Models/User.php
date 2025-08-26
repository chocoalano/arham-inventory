<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Inventory\Warehouse;
use App\Models\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'warehouse_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function log(): HasMany
    {
        return $this->hasMany(Log::class, 'causer_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()                 // log semua field fillable
            ->useLogName('pengguna')          // nama log
            ->dontSubmitEmptyLogs();        // hindari log kosong
    }

    /**
     * Generate email unik berbasis seed (email asal).
     * - Jika seed invalid/empty → pakai "user@{app-domain}"
     * - Default memakai plus-addressing: local+copy-yyyymmddHHMMSS@domain
     * - Dibuat benar-benar unik dengan cek DB dan random suffix jika perlu
     */
    public static function generateUniqueEmail(?string $seed, bool $usePlusAddressing = true): string
    {
        // Tentukan base local & domain
        if ($seed && filter_var($seed, FILTER_VALIDATE_EMAIL)) {
            [$local, $domain] = explode('@', $seed, 2);
            $baseLocal = $local;
            $baseDomain = $domain;
        } else {
            $baseLocal = 'user';
            $baseDomain = self::guessDefaultDomain(); // contoh: ambil dari mail.from / app.url
        }

        $suffix = 'copy-' . now()->format('YmdHis');
        $separator = $usePlusAddressing ? '+' : '-';
        $candidate = "{$baseLocal}{$separator}{$suffix}@{$baseDomain}";

        // Pastikan unik di DB, tambahkan 4 char acak jika tabrakan
        $tries = 0;
        while (self::query()->where('email', $candidate)->exists()) {
            $rand = Str::lower(Str::random(4));
            $candidate = "{$baseLocal}{$separator}{$suffix}-{$rand}@{$baseDomain}";

            if (++$tries > 25) { // guard kecil biar tidak infinite loop
                // last resort: ganti local base jadi acak
                $candidate = 'user-' . Str::lower(Str::random(8)) . '@' . $baseDomain;
                if (!self::query()->where('email', $candidate)->exists()) {
                    break;
                }
            }
        }

        return $candidate;
    }

    /**
     * Menebak domain default yang masuk akal untuk email dummy.
     */
    protected static function guessDefaultDomain(): string
    {
        // 1) dari MAIL_FROM_ADDRESS
        $from = (string) config('mail.from.address', '');
        if ($from && str_contains($from, '@')) {
            [, $domain] = explode('@', $from, 2);
            return $domain;
        }

        // 2) dari APP_URL
        $appUrl = (string) config('app.url', '');
        if ($appUrl) {
            $host = parse_url($appUrl, PHP_URL_HOST);
            if ($host) {
                return $host;
            }
        }

        // 3) fallback aman untuk testing
        return 'example.test';
    }
}
