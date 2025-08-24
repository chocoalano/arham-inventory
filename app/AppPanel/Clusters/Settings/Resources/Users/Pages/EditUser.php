<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\Pages;

use App\AppPanel\Clusters\Settings\Resources\Users\UserResource;
use App\Models\RBAC\Role;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Hash password hanya jika diisi
            if (filled(data_get($data, 'password'))) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            // Email verified at
            if (array_key_exists('email_verified_at', $data)) {
                $data['email_verified_at'] = blank($data['email_verified_at'])
                    ? $record->email_verified_at
                    : Carbon::parse($data['email_verified_at']);
            }

            // Ambil role_id dari data
            $roleInput = Arr::pull($data, 'role_id');
            $record->update($data);

            // Sinkronisasi role
            if (!blank($roleInput)) {
                $roles = collect(Arr::wrap($roleInput))
                    ->map(function ($val) {
                        if (is_numeric($val)) {
                            return Role::find((int) $val);   // pakai Eloquent biasa
                        }
                        return Role::findByName((string) $val);
                    })
                    ->filter()
                    ->all();

                if (!empty($roles)) {
                    $record->syncRoles($roles);
                }
            }

            return $record->refresh();
        });
    }
}
