<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Transactions;

use App\AppPanel\Clusters\Inventory\InventoryCluster;
use App\AppPanel\Clusters\Inventory\Resources\Transactions\Pages\CreateTransaction;
use App\AppPanel\Clusters\Inventory\Resources\Transactions\Pages\EditTransaction;
use App\AppPanel\Clusters\Inventory\Resources\Transactions\Pages\ListTransactionActivities;
use App\AppPanel\Clusters\Inventory\Resources\Transactions\Pages\ListTransactions;
use App\AppPanel\Clusters\Inventory\Resources\Transactions\Schemas\TransactionForm;
use App\AppPanel\Clusters\Inventory\Resources\Transactions\Tables\TransactionsTable;
use App\Models\Inventory\Transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;
    protected static ?string $cluster = InventoryCluster::class;
    protected static ?string $modelLabel = 'Transaksi';
    protected static ?string $navigationLabel = 'Transaksi';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyPermission(['viewAny-transaction', 'view-transaction']);
    }
    public static function form(Schema $schema): Schema
    {
        return TransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'create' => CreateTransaction::route('/create'),
            'edit' => EditTransaction::route('/{record}/edit'),
            'activities' => ListTransactionActivities::route('/{record}/activities'),
        ];
    }
}
