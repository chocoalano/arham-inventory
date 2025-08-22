<?php return array (
  'livewireComponents' => 
  array (
    'app.app-panel.clusters.inventory.inventory-cluster' => 'App\\AppPanel\\Clusters\\Inventory\\InventoryCluster',
    'app.app-panel.clusters.inventory.resources.inventory-movements.pages.manage-inventory-movements' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\InventoryMovements\\Pages\\ManageInventoryMovements',
    'app.app-panel.clusters.inventory.resources.inventory-movements.widgets.inventory-stats' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\InventoryMovements\\Widgets\\InventoryStats',
    'app.app-panel.clusters.inventory.resources.invoices.pages.manage-invoices' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Invoices\\Pages\\ManageInvoices',
    'app.app-panel.clusters.inventory.resources.payments.pages.manage-payments' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Payments\\Pages\\ManagePayments',
    'app.app-panel.clusters.inventory.resources.transactions.pages.create-transaction' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Transactions\\Pages\\CreateTransaction',
    'app.app-panel.clusters.inventory.resources.transactions.pages.edit-transaction' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Transactions\\Pages\\EditTransaction',
    'app.app-panel.clusters.inventory.resources.transactions.pages.list-transactions' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Transactions\\Pages\\ListTransactions',
    'app.app-panel.clusters.inventory.resources.warehouses.pages.manage-warehouses' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Warehouses\\Pages\\ManageWarehouses',
    'app.app-panel.clusters.inventory.resources.warehouses.relation-managers.stocks-relation-manager' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Warehouses\\RelationManagers\\StocksRelationManager',
    'app.app-panel.clusters.produk.produk-cluster' => 'App\\AppPanel\\Clusters\\Produk\\ProdukCluster',
    'app.app-panel.clusters.produk.resources.product-variants.pages.create-product-variant' => 'App\\AppPanel\\Clusters\\Produk\\Resources\\ProductVariants\\Pages\\CreateProductVariant',
    'app.app-panel.clusters.produk.resources.product-variants.pages.edit-product-variant' => 'App\\AppPanel\\Clusters\\Produk\\Resources\\ProductVariants\\Pages\\EditProductVariant',
    'app.app-panel.clusters.produk.resources.product-variants.pages.list-product-variants' => 'App\\AppPanel\\Clusters\\Produk\\Resources\\ProductVariants\\Pages\\ListProductVariants',
    'app.app-panel.clusters.produk.resources.products.pages.create-product' => 'App\\AppPanel\\Clusters\\Produk\\Resources\\Products\\Pages\\CreateProduct',
    'app.app-panel.clusters.produk.resources.products.pages.edit-product' => 'App\\AppPanel\\Clusters\\Produk\\Resources\\Products\\Pages\\EditProduct',
    'app.app-panel.clusters.produk.resources.products.pages.list-products' => 'App\\AppPanel\\Clusters\\Produk\\Resources\\Products\\Pages\\ListProducts',
    'app.app-panel.clusters.produk.resources.suppliers.pages.manage-suppliers' => 'App\\AppPanel\\Clusters\\Produk\\Resources\\Suppliers\\Pages\\ManageSuppliers',
    'app.app-panel.clusters.settings.resources.roles.pages.manage-roles' => 'App\\AppPanel\\Clusters\\Settings\\Resources\\Roles\\Pages\\ManageRoles',
    'app.app-panel.clusters.settings.resources.users.pages.create-user' => 'App\\AppPanel\\Clusters\\Settings\\Resources\\Users\\Pages\\CreateUser',
    'app.app-panel.clusters.settings.resources.users.pages.edit-user' => 'App\\AppPanel\\Clusters\\Settings\\Resources\\Users\\Pages\\EditUser',
    'app.app-panel.clusters.settings.resources.users.pages.list-users' => 'App\\AppPanel\\Clusters\\Settings\\Resources\\Users\\Pages\\ListUsers',
    'app.app-panel.clusters.settings.resources.users.pages.view-user' => 'App\\AppPanel\\Clusters\\Settings\\Resources\\Users\\Pages\\ViewUser',
    'app.app-panel.clusters.settings.settings-cluster' => 'App\\AppPanel\\Clusters\\Settings\\SettingsCluster',
    'filament.pages.dashboard' => 'Filament\\Pages\\Dashboard',
    'app.app-panel.widgets.stats-inventory-overview' => 'App\\AppPanel\\Widgets\\StatsInventoryOverview',
    'app.app-panel.widgets.transaction-chart' => 'App\\AppPanel\\Widgets\\TransactionChart',
    'filament.livewire.database-notifications' => 'Filament\\Livewire\\DatabaseNotifications',
    'filament.auth.pages.edit-profile' => 'Filament\\Auth\\Pages\\EditProfile',
    'filament.livewire.global-search' => 'Filament\\Livewire\\GlobalSearch',
    'filament.livewire.notifications' => 'Filament\\Livewire\\Notifications',
    'filament.livewire.sidebar' => 'Filament\\Livewire\\Sidebar',
    'filament.livewire.simple-user-menu' => 'Filament\\Livewire\\SimpleUserMenu',
    'filament.livewire.topbar' => 'Filament\\Livewire\\Topbar',
    'filament.auth.pages.login' => 'Filament\\Auth\\Pages\\Login',
  ),
  'clusters' => 
  array (
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Inventory/InventoryCluster.php' => 'App\\AppPanel\\Clusters\\Inventory\\InventoryCluster',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Produk/ProdukCluster.php' => 'App\\AppPanel\\Clusters\\Produk\\ProdukCluster',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Settings/SettingsCluster.php' => 'App\\AppPanel\\Clusters\\Settings\\SettingsCluster',
  ),
  'clusteredComponents' => 
  array (
    'App\\AppPanel\\Clusters\\Inventory\\InventoryCluster' => 
    array (
      0 => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\InventoryMovements\\InventoryMovementResource',
      1 => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Invoices\\InvoiceResource',
      2 => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Payments\\PaymentResource',
      3 => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Transactions\\TransactionResource',
      4 => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Warehouses\\WarehouseResource',
    ),
    'App\\AppPanel\\Clusters\\Produk\\ProdukCluster' => 
    array (
      0 => 'App\\AppPanel\\Clusters\\Produk\\Resources\\ProductVariants\\ProductVariantResource',
      1 => 'App\\AppPanel\\Clusters\\Produk\\Resources\\Products\\ProductResource',
      2 => 'App\\AppPanel\\Clusters\\Produk\\Resources\\Suppliers\\SupplierResource',
    ),
    'App\\AppPanel\\Clusters\\Settings\\SettingsCluster' => 
    array (
      0 => 'App\\AppPanel\\Clusters\\Settings\\Resources\\Roles\\RoleResource',
      1 => 'App\\AppPanel\\Clusters\\Settings\\Resources\\Users\\UserResource',
    ),
  ),
  'clusterDirectories' => 
  array (
    0 => '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters',
  ),
  'clusterNamespaces' => 
  array (
    0 => 'App\\AppPanel\\Clusters',
  ),
  'pages' => 
  array (
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Inventory/InventoryCluster.php' => 'App\\AppPanel\\Clusters\\Inventory\\InventoryCluster',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Produk/ProdukCluster.php' => 'App\\AppPanel\\Clusters\\Produk\\ProdukCluster',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Settings/SettingsCluster.php' => 'App\\AppPanel\\Clusters\\Settings\\SettingsCluster',
    0 => 'Filament\\Pages\\Dashboard',
  ),
  'pageDirectories' => 
  array (
    0 => '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Pages',
  ),
  'pageNamespaces' => 
  array (
    0 => 'App\\AppPanel\\Pages',
  ),
  'resources' => 
  array (
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Inventory/Resources/InventoryMovements/InventoryMovementResource.php' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\InventoryMovements\\InventoryMovementResource',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Inventory/Resources/Invoices/InvoiceResource.php' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Invoices\\InvoiceResource',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Inventory/Resources/Payments/PaymentResource.php' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Payments\\PaymentResource',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Inventory/Resources/Transactions/TransactionResource.php' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Transactions\\TransactionResource',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Inventory/Resources/Warehouses/WarehouseResource.php' => 'App\\AppPanel\\Clusters\\Inventory\\Resources\\Warehouses\\WarehouseResource',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Produk/Resources/ProductVariants/ProductVariantResource.php' => 'App\\AppPanel\\Clusters\\Produk\\Resources\\ProductVariants\\ProductVariantResource',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Produk/Resources/Products/ProductResource.php' => 'App\\AppPanel\\Clusters\\Produk\\Resources\\Products\\ProductResource',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Produk/Resources/Suppliers/SupplierResource.php' => 'App\\AppPanel\\Clusters\\Produk\\Resources\\Suppliers\\SupplierResource',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Settings/Resources/Roles/RoleResource.php' => 'App\\AppPanel\\Clusters\\Settings\\Resources\\Roles\\RoleResource',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Clusters/Settings/Resources/Users/UserResource.php' => 'App\\AppPanel\\Clusters\\Settings\\Resources\\Users\\UserResource',
  ),
  'resourceDirectories' => 
  array (
    0 => '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Resources',
  ),
  'resourceNamespaces' => 
  array (
    0 => 'App\\AppPanel\\Resources',
  ),
  'widgets' => 
  array (
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Widgets/StatsInventoryOverview.php' => 'App\\AppPanel\\Widgets\\StatsInventoryOverview',
    '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Widgets/TransactionChart.php' => 'App\\AppPanel\\Widgets\\TransactionChart',
    0 => 'App\\AppPanel\\Widgets\\StatsInventoryOverview',
  ),
  'widgetDirectories' => 
  array (
    0 => '/home/alan/Documents/Laravel/filament/my-apps/app/AppPanel/Widgets',
  ),
  'widgetNamespaces' => 
  array (
    0 => 'App\\AppPanel\\Widgets',
  ),
);