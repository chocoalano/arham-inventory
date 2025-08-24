<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users;

use App\AppPanel\Clusters\Settings\Resources\Users\Pages\CreateUser;
use App\AppPanel\Clusters\Settings\Resources\Users\Pages\EditUser;
use App\AppPanel\Clusters\Settings\Resources\Users\Pages\ListOrderActivities;
use App\AppPanel\Clusters\Settings\Resources\Users\Pages\ListUsers;
use App\AppPanel\Clusters\Settings\Resources\Users\Pages\ViewUser;
use App\AppPanel\Clusters\Settings\Resources\Users\RelationManagers\LogRelationManager;
use App\AppPanel\Clusters\Settings\Resources\Users\Schemas\UserForm;
use App\AppPanel\Clusters\Settings\Resources\Users\Schemas\UserInfolist;
use App\AppPanel\Clusters\Settings\Resources\Users\Tables\UsersTable;
use App\AppPanel\Clusters\Settings\SettingsCluster;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;
    protected static ?string $cluster = SettingsCluster::class;
    protected static ?string $recordTitleAttribute = 'User';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $navigationLabel = 'Pengguna';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyPermission(['viewAny-user', 'view-user']);
    }
    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LogRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'activities' => ListOrderActivities::route('/{record}/activities'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
