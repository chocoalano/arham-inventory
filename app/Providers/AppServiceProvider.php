<?php

namespace App\Providers;

use App\Models\Finance\Account;
use App\Models\Finance\AccountMapping;
use App\Models\Finance\CostCenter;
use App\Models\Finance\FiscalYear;
use App\Models\Finance\Journal;
use App\Models\Finance\Period;
use App\Models\Finance\ProductAccountLink;
use App\Models\Inventory\Invoice;
use App\Models\Inventory\Payment;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Supplier;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\Warehouse;
use App\Models\RawMaterial\ProductBom;
use App\Models\RawMaterial\RawMaterial;
use App\Models\RawMaterial\RawMaterialBatch;
use App\Models\RawMaterial\RawMaterialCategory;
use App\Models\RawMaterial\RawMaterialStockMovement;
use App\Models\RawMaterial\RawMaterialSupplier;
use App\Models\RawMaterial\Unit;
use App\Models\RawMaterial\UnitConversation;
use App\Models\RBAC\Role;
use App\Models\User;
use App\Policies\Accounting\AccountMappingPolicy;
use App\Policies\Accounting\AccountPolicy;
use App\Policies\Accounting\CostCenterPolicy;
use App\Policies\Accounting\FiscalYearPolicy;
use App\Policies\Accounting\JournalPolicy;
use App\Policies\Accounting\PeriodPolicy;
use App\Policies\Accounting\ProductAccountLinkPolicy;
use App\Policies\Inventory\InvoicePolicy;
use App\Policies\Inventory\PaymentPolicy;
use App\Policies\Inventory\ProductPolicy;
use App\Policies\Inventory\ProductVariantPolicy;
use App\Policies\Inventory\SupplierPolicy;
use App\Policies\Inventory\TransactionPolicy;
use App\Policies\Inventory\WarehousePolicy;
use App\Policies\Produksi\ProductBomPolicy;
use App\Policies\Produksi\RawMaterialBatchPolicy;
use App\Policies\Produksi\RawMaterialCategoryPolicy;
use App\Policies\Produksi\RawMaterialPolicy;
use App\Policies\Produksi\RawMaterialStockMovementPolicy;
use App\Policies\Produksi\RawMaterialSupplierPolicy;
use App\Policies\Produksi\UnitConversationPolicy;
use App\Policies\Produksi\UnitPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductVariant::class, ProductVariantPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Warehouse::class, WarehousePolicy::class);

        Gate::policy(ProductBom::class, ProductBomPolicy::class);
        Gate::policy(RawMaterialBatch::class, RawMaterialBatchPolicy::class);
        Gate::policy(RawMaterialCategory::class, RawMaterialCategoryPolicy::class);
        Gate::policy(RawMaterialStockMovement::class, RawMaterialStockMovementPolicy::class);
        Gate::policy(RawMaterialSupplier::class, RawMaterialSupplierPolicy::class);
        Gate::policy(UnitConversation::class, UnitConversationPolicy::class);
        Gate::policy(Unit::class, UnitPolicy::class);

        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(AccountMapping::class, AccountMappingPolicy::class);
        Gate::policy(CostCenter::class, CostCenterPolicy::class);
        Gate::policy(FiscalYear::class, FiscalYearPolicy::class);
        Gate::policy(Journal::class, JournalPolicy::class);
        Gate::policy(Period::class, PeriodPolicy::class);
        Gate::policy(ProductAccountLink::class, ProductAccountLinkPolicy::class);
    }
}
