<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\Pages;

use App\AppPanel\Clusters\Settings\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            /** -------------------------
             * 1) Password
             * ------------------------- */
            if (filled($data['password'] ?? null)) {
                $data['password'] = Hash::make($data['password']);
            } else {
                // Opsi A (umum di Filament): biarkan validator form yang mewajibkan password.
                // Di sini kita buang agar tidak menulis NULL.
                unset($data['password']);

                // Opsi B (ketat): lempar error jika password kosong.
                // throw ValidationException::withMessages(['password' => 'Password wajib diisi.']);
            }

            /** -------------------------
             * 2) Email verified at
             * ------------------------- */
            if (array_key_exists('email_verified_at', $data)) {
                $data['email_verified_at'] = blank($data['email_verified_at'])
                    ? null
                    : Carbon::parse($data['email_verified_at']);
            } else {
                $data['email_verified_at'] = now();
            }

            /** -------------------------
             * 3) Pisahkan role input
             * ------------------------- */
            // Bisa berupa id / nama / array (sesuai trait HasRoles kamu)
            $roleInput = Arr::pull($data, 'role_id');

            /** -------------------------
             * 4) Create model
             * ------------------------- */
            /** @var Model $model */
            $model = static::getModel()::create($data);

            /** -------------------------
             * 5) Sinkronisasi roles
             * ------------------------- */
            if (!blank($roleInput) && method_exists($model, 'syncRoles')) {
                // Trait HasRoles kamu sudah menyediakan syncRoles()
                $model->syncRoles($roleInput);
            }

            return $model->refresh();
        });
    }
}
