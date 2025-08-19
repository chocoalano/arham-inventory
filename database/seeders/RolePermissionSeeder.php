<?php

namespace Database\Seeders;

use App\Models\RBAC\Permission;
use App\Models\RBAC\PermissionGroup;
use App\Models\RBAC\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Aksi standar per resource.
     */
    private const ACTIONS = [
        'viewAny',   // lihat daftar
        'view',      // lihat detail
        'create',
        'update',    // (hindari "edit" -> konsisten dengan policy Laravel)
        'delete',
        'deleteAny', // hapus massal
    ];

    /**
     * Daftar resource yang diberi permission.
     */
    private const RESOURCES = [
        'user',
        'role',
        'product',
        'product_variant',
        'invoice',
        'payment',
        'transaction',
        'warehouse',
    ];

    public function run(): void
    {
        DB::transaction(function () {

            // 1) BUAT / UPDATE ROLES (idempotent)
            $adminRole  = Role::firstOrCreate(['name' => 'admin'],  [
                'label' => 'Administrator',
                'desc'  => 'Akses penuh ke sistem.',
            ]);

            $editorRole = Role::firstOrCreate(['name' => 'editor'], [
                'label' => 'Editor Konten',
                'desc'  => 'Mengelola data master & konten.',
            ]);

            $userRole   = Role::firstOrCreate(['name' => 'user'],   [
                'label' => 'Pengguna',
                'desc'  => 'Akses dasar sebagai pengguna.',
            ]);

            // 2) BUAT GROUP & PERMISSIONS per resource (idempotent)
            $allPermissionIds = [];

            foreach (self::RESOURCES as $resource) {
                // Group per resource agar rapi di UI
                $group = PermissionGroup::firstOrCreate(
                    ['name' => $resource],
                    ['label' => Str::headline($resource)]
                );

                foreach (self::ACTIONS as $action) {
                    $permName = "{$action}-{$resource}";

                    /** @var Permission $perm */
                    $perm = Permission::updateOrCreate(
                        ['name' => $permName],
                        [
                            'label' => self::labelFor($action, $resource),
                            'permission_group_id' => $group->id,
                        ]
                    );

                    $allPermissionIds[] = $perm->id;
                }
            }

            // 3) ASSIGN PERMISSIONS KE ROLES
            // Admin -> semua permission
            $adminRole->permissions()->sync($allPermissionIds);

            // Editor -> full product & variant, bisa lihat invoice/payment/warehouse
            $editorPerms = Permission::query()
                ->whereIn('name', function ($q) {
                    $q->select('name')
                      ->from('permissions');
                })
                ->where(function ($q) {
                    // full untuk product & product_variant
                    $q->whereIn('permission_group_id', PermissionGroup::query()
                        ->whereIn('name', ['product', 'product_variant'])
                        ->pluck('id'))
                      // viewAny/view untuk invoice, payment, warehouse
                      ->orWhereIn('name', [
                          'viewAny-invoice','view-invoice',
                          'viewAny-payment','view-payment',
                          'viewAny-warehouse','view-warehouse',
                      ]);
                })
                ->pluck('id')
                ->all();

            $editorRole->permissions()->sync($editorPerms);

            // User -> hanya view produk & variant
            $userPerms = Permission::query()
                ->whereIn('name', [
                    'viewAny-product','view-product',
                    'viewAny-product_variant','view-product_variant',
                ])
                ->pluck('id')
                ->all();

            $userRole->permissions()->sync($userPerms);

            // 4) USER DUMMY (aman diulang)
            // (Opsional: batasi ke local/testing)
            $adminUser = User::updateOrCreate(
                ['email' => 'admin@example.com'],
                ['name' => 'Admin User', 'password' => Hash::make('password')]
            );
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);

            $editorUser = User::updateOrCreate(
                ['email' => 'editor@example.com'],
                ['name' => 'Editor User', 'password' => Hash::make('password')]
            );
            $editorUser->roles()->syncWithoutDetaching([$editorRole->id]);

            $regularUser = User::updateOrCreate(
                ['email' => 'user@example.com'],
                ['name' => 'Regular User', 'password' => Hash::make('password')]
            );
            $regularUser->roles()->syncWithoutDetaching([$userRole->id]);

            // Hindari memberi izin yang tidak relevan ke user biasa.
            // Jika perlu izin langsung:
            // $regularUser->permissions()->syncWithoutDetaching(
            //     Permission::where('name', 'viewAny-product')->pluck('id')
            // );
        });
    }

    /**
     * Membuat label manusiawi untuk permission.
     */
    private static function labelFor(string $action, string $resource): string
    {
        $resourceLabel = Str::headline($resource);

        return match ($action) {
            'viewAny'   => "Lihat Daftar {$resourceLabel}",
            'view'      => "Lihat {$resourceLabel}",
            'create'    => "Buat {$resourceLabel}",
            'update'    => "Ubah {$resourceLabel}",
            'delete'    => "Hapus {$resourceLabel}",
            'deleteAny' => "Hapus Massal {$resourceLabel}",
            default     => Str::headline("{$action} {$resourceLabel}"),
        };
    }
}
