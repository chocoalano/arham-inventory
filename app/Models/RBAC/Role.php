<?php

namespace App\Models\RBAC;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Role extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'roles';

    protected $fillable = ['name', 'label', 'desc'];

    /* -----------------------------
     | Relationships
     * ---------------------------- */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role')
            ->withTimestamps();
    }

    /* -----------------------------
     | Activity Log
     * ---------------------------- */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('role')
            ->dontSubmitEmptyLogs();
    }

    /* -----------------------------
     | Query helpers
     * ---------------------------- */
    public static function findByName(string $name): ?self
    {
        $driver = static::query()->getConnection()->getDriverName();

        return static::query()
            ->when(
                $driver === 'pgsql',
                fn (Builder $q) => $q->where('name', 'ILIKE', $name),
                fn (Builder $q) => $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            )
            ->first();
    }

    /**
     * Generate nama role yang dijamin unik.
     *
     * @param  string|null  $seed   Nama awal (mis. "Admin Sales"). Jika null, pakai "role".
     * @param  int          $maxLen Batas panjang kolom name (default 64 — sesuaikan dengan skema DB).
     */
    public static function generateUniqueName(?string $seed, int $maxLen = 64): string
    {
        // 1) Normalisasi seed menjadi slug (lowercase, dash)
        $base = Str::slug($seed ?: 'role', '-'); // contoh: "Admin Sales" -> "admin-sales"
        if ($base === '') {
            $base = 'role';
        }

        // 2) Suffix waktu agar mudah ditelusuri & besar peluang unik
        $suffix = 'copy-' . now()->format('YmdHis'); // ex: copy-20250825...
        $sep    = '-';

        // 3) Potong base agar total <= maxLen
        $candidate = self::truncateForSuffix($base, $suffix, $sep, $maxLen);

        // 4) Pastikan unik di DB; jika tabrakan, tambahkan 4 char acak (loop aman)
        $tries = 0;
        while (self::query()->where('name', $candidate)->exists()) {
            $rand      = Str::lower(Str::random(4));
            $candidate = self::truncateForSuffix($base, "{$suffix}-{$rand}", $sep, $maxLen);

            if (++$tries > 25) {
                // last resort
                $candidate = Str::limit($base, max(1, $maxLen - 9), '') . $sep . Str::lower(Str::random(8));
                if (! self::query()->where('name', $candidate)->exists()) {
                    break;
                }
            }
        }

        return $candidate;
    }

    /**
     * Potong base agar muat dengan suffix & separator, menjaga batas panjang.
     */
    protected static function truncateForSuffix(string $base, string $suffix, string $sep, int $maxLen): string
    {
        $allow = $maxLen - (mb_strlen($suffix) + mb_strlen($sep));
        $trim  = $allow > 0 ? Str::limit($base, $allow, '') : 'role';
        return "{$trim}{$sep}{$suffix}";
    }

    /* -----------------------------
     | (Opsional) Accessors/Mutators
     * ---------------------------- */
    public function setNameAttribute($value): void
    {
        // konsisten lower-case & slug-ish (tanpa spasi)
        $this->attributes['name'] = Str::slug((string) $value, '-');
    }

    public function getLabelAttribute(?string $value): string
    {
        if (! empty($value)) {
            return $value;
        }
        // Fallback label yang human-friendly dari name
        return Str::of($this->attributes['name'] ?? '')
            ->replace('-', ' ')
            ->headline()
            ->toString();
    }
}
