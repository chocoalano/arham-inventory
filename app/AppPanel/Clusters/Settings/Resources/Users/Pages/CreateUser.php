<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\Pages;

use App\AppPanel\Clusters\Settings\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function handleRecordCreation(array $data): Model
    {
        $data['email_verified_at'] = $data['email_verified_at'] ?? now();
        $data['password'] = Hash::make($data['password']);
        $model = static::getModel()::create($data);
        $model->assignRole($data['role_id']);
        return $model;
    }
}
