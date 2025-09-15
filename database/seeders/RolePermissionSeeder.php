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
        'viewAny',          // Lihat daftar (index/list)
        'view',             // Lihat detail 1 data
        'create',           // Membuat data baru
        'update',           // Mengubah data yang sudah ada

        'delete',           // Menghapus 1 data (soft delete)
        'deleteAny',        // Hapus massal (soft delete)
        'restore',          // Restore 1 data dari soft delete
        'restoreAny',       // Restore massal dari soft delete

        'forceDelete',      // Hapus permanen 1 data (bypass soft delete)
        'forceDeleteAny',   // Hapus permanen massal

        'replicate',        // Duplikasi/replicate record
        'reorder',          // Reorder/drag-sort record (jika resource mendukung)

        'export',           // Ekspor data (CSV/Excel dsb.; custom di project)
        'import',           // Impor data (CSV/Excel dsb.; custom di project)
    ];


    /**
     * Daftar resource yang diberi permission.
     */
    private const RESOURCES = [
        // 'user',
        // 'role',
        // 'product',
        // 'product_variant',
        // 'invoice',
        // 'payment',
        // 'transaction',
        // 'warehouse',
        // 'supplier',

        'account',
        'account_mapping',
        'cost_center',
        'fiscal_year',
        'journal',
        'period',
    ];

    public function run(): void
    {
        DB::transaction(function () {

            // 1) BUAT / UPDATE ROLES (idempotent)
            // $superadminRole = Role::firstOrCreate(['name' => 'Superadmin'], [
            //     'label' => 'Superadmin',
            //     'desc' => 'Akses penuh ke sistem.',
            // ]);
            // $adminRole = Role::firstOrCreate(['name' => 'admin'], [
            //     'label' => 'Administrator',
            //     'desc' => '
            //     Akses transaksi(CRUD Penjualan), Update=>hanya bisa max 1 jam setelah data transaksi dibuat,
            //     Akses transaksi(CRUD Perpindahan produk antar gudang), Update=>hanya bisa max 1 jam setelah data transaksi dibuat,
            //     Akses transaksi(CRUD Pengembalian produk), Update=>hanya bisa max 1 jam setelah data transaksi dibuat,
            //     Cetak faktur faktur penjualan & packing slip,
            //     Read Only produk/varian produk,
            //     read & update profile,
            //     Data yang ditampilkan hanya data sesuai current area.
            //     ',
            // ]);

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
            // $superadminRole->permissions()->sync($allPermissionIds);

            // Editor -> full product & variant, bisa lihat invoice/payment/warehouse
            // $adminPerms = Permission::query()
            //     ->where(function ($q) {
            //         // full untuk product & product_variant
            //         $q->whereIn('permission_group_id', PermissionGroup::query()
            //             ->whereIn('name', ['transaction'])
            //             ->pluck('id'))
            //             // viewAny/view untuk invoice, payment, warehouse
            //             ->orWhereIn('name', [
            //                 'viewAny-invoice',
            //                 'view-invoice',
            //                 'print-invoice',
            //                 'viewAny-payment',
            //                 'view-payment',
            //                 'print-payment',
            //                 'viewAny-warehouse',
            //                 'view-warehouse',
            //             ]);
            //     })
            //     ->pluck('id')
            //     ->all();

            // $adminRole->permissions()->sync($adminPerms);


            // $superadminUser = User::updateOrCreate(
            //     ['email' => 'superadmin@example.com'],
            //     [
            //         'name' => 'Superadmin User',
            //         'password' => Hash::make('password'),
            //         'email_verified_at' => now()
            //         ]
            // );
            // $superadminUser->roles()->syncWithoutDetaching([$superadminRole->id]);
            // $admin1User = User::updateOrCreate(
            //     ['email' => 'admin1@example.com'],
            //     [
            //         'name' => 'Admin1 User',
            //         'password' => Hash::make('password'),
            //         'email_verified_at' => now()
            //         ]
            // );
            // $admin1User->roles()->syncWithoutDetaching([$adminRole->id]);
            // $admin2User = User::updateOrCreate(
            //     ['email' => 'admin2@example.com'],
            //     [
            //         'name' => 'Admin2 User',
            //         'password' => Hash::make('password'),
            //         'email_verified_at' => now()
            //         ]
            // );
            // $admin2User->roles()->syncWithoutDetaching([$adminRole->id]);
        });
    }

    /**
     * Membuat label manusiawi untuk permission.
     */
    private static function labelFor(string $action, string $resource): string
    {
        $resourceLabel = Str::headline($resource);

        return match ($action) {
            'viewAny'         => "Lihat Daftar {$resourceLabel}",
            'view'            => "Lihat {$resourceLabel}",
            'create'          => "Buat {$resourceLabel}",
            'update'          => "Ubah {$resourceLabel}",
            'delete'          => "Hapus {$resourceLabel}",
            'deleteAny'       => "Hapus Massal {$resourceLabel}",
            'forceDelete',    => "Menghapus permanen 1 data (skip recycle bin/soft delete) {$resourceLabel}",
            'forceDeleteAny', => "Menghapus permanen banyak data sekaligus {$resourceLabel}",
            'restore',        => "Mengembalikan data yang dihapus (soft delete -> aktif kembali) {$resourceLabel}",
            'restoreAny',     => "Mengembalikan banyak data yang dihapus (opsional jika mau konsisten) {$resourceLabel}",
            'export',         => "Mengekspor data (misalnya ke Excel/CSV) {$resourceLabel}",
            'import',         => "Mengimpor data (misalnya dari Excel/CSV) {$resourceLabel}",
            'print',          => "Mencetak/export pdf {$resourceLabel}",
            default => Str::headline("{$action} {$resourceLabel}"),
        };
    }
}
