<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\Pages;

use App\AppPanel\Clusters\Settings\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
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
        if (isset($data['password']) && !isNull($data['password'] && !isEmpty($data['password']))) {
            $data['password']=Hash::make($data['password']);
        }
        $data['email_verified_at'] = $data['email_verified_at'] ?? now();
        $record->update($data);
        $record->assignRole($data['role_id']);
        return $record;
    }
}
