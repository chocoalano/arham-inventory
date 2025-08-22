<?php

namespace App\Models\Traits;

use App\Models\RBAC\Permission;
use App\Models\RBAC\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    /** @var Collection|null */
    protected ?Collection $permissionsCache = null;

    /**
     * Relasi many-to-many ke Role.
     */
    public function roles(): BelongsToMany
    {
        // Sesuaikan nama pivot bila perlu (default di sini: user_role)
        return $this->belongsToMany(Role::class, 'user_role');
    }

    /**
     * Relasi many-to-many ke Permission (izin langsung).
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission');
    }

    /* ==================== ROLES ==================== */

    /**
     * Cek 1 role (string name | int id | Role model).
     * Bisa juga array => diarahkan ke hasAnyRole().
     */
    public function hasRole(string|int|Role|array $role): bool
    {
        if (is_array($role)) {
            return $this->hasAnyRole($role);
        }

        if ($role instanceof Role) {
            return $this->roles()->whereKey($role->getKey())->exists();
        }

        if (is_int($role)) {
            return $this->roles()->whereKey($role)->exists();
        }

        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Cek minimal salah satu dari role yang diberikan.
     */
    public function hasAnyRole(array $roles): bool
    {
        [$ids, $names] = $this->normalizeRoles($roles);

        return $this->roles()
            ->where(function ($q) use ($ids, $names) {
                if (! empty($ids))   $q->whereKey($ids);
                if (! empty($names)) $q->orWhereIn('name', $names);
            })
            ->exists();
    }

    /**
     * Cek SEMUA role diberikan harus dimiliki user.
     */
    public function hasAllRoles(array $roles): bool
    {
        [$ids, $names] = $this->normalizeRoles($roles);

        $count = $this->roles()
            ->where(function ($q) use ($ids, $names) {
                if (! empty($ids))   $q->whereKey($ids);
                if (! empty($names)) $q->orWhereIn('name', $names);
            })
            ->distinct()
            ->count('roles.id');

        $requested = count(array_unique(array_merge($ids, $names)));

        return $count === $requested && $requested > 0;
    }

    /**
     * Tambah role (string|int|Role). Tidak menghapus role lain.
     */
    public function assignRole(string|int|Role $role): static
    {
        $roleModel = $role instanceof Role
            ? $role
            : (is_int($role)
                ? Role::findOrFail($role)
                : Role::where('name', $role)->firstOrFail());

        $this->roles()->syncWithoutDetaching([$roleModel->getKey()]);
        return $this->clearPermissionsCache();
    }

    /**
     * Hapus role.
     */
    public function removeRole(string|int|Role $role): static
    {
        $roleId = $role instanceof Role
            ? $role->getKey()
            : (is_int($role) ? $role : Role::where('name', $role)->value('id'));

        if ($roleId) {
            $this->roles()->detach($roleId);
        }
        return $this->clearPermissionsCache();
    }

    /* ==================== PERMISSIONS ==================== */

    /**
     * Dapatkan semua permission (langsung + dari role), dengan cache sederhana.
     */
    public function getAllPermissions(): Collection
    {
        if ($this->permissionsCache instanceof Collection) {
            return $this->permissionsCache;
        }

        $this->loadMissing(['permissions', 'roles.permissions']);

        $all = $this->permissions
            ->concat($this->roles->flatMap->permissions)
            ->unique('id')
            ->values();

        return $this->permissionsCache = $all;
    }

    /**
     * Cek 1 permission (string name | int id | Permission model).
     */
    public function hasPermissionTo(string|int|Permission $permission): bool
    {
        $perms = $this->getAllPermissions();

        if ($permission instanceof Permission) {
            return $perms->contains('id', $permission->getKey());
        }

        if (is_int($permission)) {
            return $perms->contains('id', $permission);
        }

        return $perms->contains('name', $permission);
    }

    /**
     * Cek minimal salah satu permission yang diberikan.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $perms = $this->getAllPermissions();

        foreach ($permissions as $p) {
            if ($p instanceof Permission && $perms->contains('id', $p->getKey())) return true;
            if (is_int($p)               && $perms->contains('id', $p))          return true;
            if (is_string($p)            && $perms->contains('name', $p))        return true;
        }
        return false;
    }

    /**
     * (Opsional) beri permission langsung ke user.
     */
    public function givePermissionTo(string|int|Permission $permission): static
    {
        $permModel = $permission instanceof Permission
            ? $permission
            : (is_int($permission)
                ? Permission::findOrFail($permission)
                : Permission::where('name', $permission)->firstOrFail());

        $this->permissions()->syncWithoutDetaching([$permModel->getKey()]);
        return $this->clearPermissionsCache();
    }

    /**
     * (Opsional) cabut permission langsung dari user.
     */
    public function revokePermission(string|int|Permission $permission): static
    {
        $permId = $permission instanceof Permission
            ? $permission->getKey()
            : (is_int($permission) ? $permission : Permission::where('name', $permission)->value('id'));

        if ($permId) {
            $this->permissions()->detach($permId);
        }
        return $this->clearPermissionsCache();
    }

    /* ==================== UTIL ==================== */

    protected function normalizeRoles(array $roles): array
    {
        $ids = [];
        $names = [];

        foreach ($roles as $r) {
            if ($r instanceof Role) {
                $ids[] = $r->getKey();
            } elseif (is_int($r)) {
                $ids[] = $r;
            } elseif (is_string($r)) {
                $names[] = $r;
            }
        }

        // unik
        return [array_values(array_unique($ids)), array_values(array_unique($names))];
    }

    /**
     * Bersihkan cache permission lokal.
     */
    protected function clearPermissionsCache(): static
    {
        $this->permissionsCache = null;
        return $this;
    }

    /**
     * Otomatis bersihkan cache saat model disimpan.
     * (Bisa diperluas untuk event pivot attach/detach jika perlu.)
     */
    public static function bootHasRoles(): void
    {
        static::saved(function ($model) {
            $model->clearPermissionsCache();
        });
    }
}
