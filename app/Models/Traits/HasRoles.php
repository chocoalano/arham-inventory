<?php
namespace App\Models\Traits;

use App\Models\RBAC\Permission;
use App\Models\RBAC\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    protected $permissionsCache;

    /**
     * Relasi many-to-many ke model Role.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    /**
     * Relasi many-to-many ke model Permission (izin langsung).
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission');
    }

    /**
     * Cek apakah pengguna memiliki peran tertentu.
     *
     * @param string|Role $role
     * @return bool
     */
    public function hasRole($role): bool
    {
        return (bool) $this->roles->where('name', $role)->count();
    }

    /**
     * Cek apakah pengguna memiliki salah satu dari peran yang diberikan.
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        return (bool) $this->roles->whereIn('name', $roles)->count();
    }

    /**
     * Cek apakah pengguna memiliki semua peran yang diberikan.
     *
     * @param array $roles
     * @return bool
     */
    public function hasAllRoles(array $roles): bool
    {
        return $this->roles->count() === count($roles);
    }

    /**
     * Berikan peran kepada pengguna.
     *
     * @param string|Role $role
     * @return $this
     */
    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching($role);

        return $this;
    }

    /**
     * Hapus peran dari pengguna.
     *
     * @param string|Role $role
     * @return $this
     */
    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role);

        return $this;
    }

    /**
     * Dapatkan semua izin yang dimiliki pengguna, termasuk yang berasal dari peran.
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        if ($this->permissionsCache) {
            return $this->permissionsCache;
        }

        // Dapatkan izin langsung dari pengguna
        $directPermissions = $this->permissions;

        // Dapatkan izin dari semua peran pengguna
        $rolePermissions = $this->roles->load('permissions')->flatMap(function ($role) {
            return $role->permissions;
        });

        // Gabungkan kedua koleksi izin
        $allPermissions = $directPermissions->merge($rolePermissions);

        // Cache hasil dan kembalikan
        return $this->permissionsCache = $allPermissions->unique('id');
    }

    /**
     * Cek apakah pengguna memiliki izin tertentu.
     *
     * @param string|Permission $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool
    {
        if (is_string($permission)) {
            return $this->getAllPermissions()->contains('name', $permission);
        }

        return $this->getAllPermissions()->contains('id', $permission->id);
    }
}
