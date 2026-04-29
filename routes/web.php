<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login'); // Redirige a la página de login
});

// Authentication
Route::get('login', [\App\Http\Controllers\LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [\App\Http\Controllers\LoginController::class, 'login']);
Route::post('logout', [\App\Http\Controllers\LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Usuarios
    Route::resource('users', \App\Http\Controllers\UserController::class);
    
    // Perfil
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/show', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.show'); // Alias for link in layout
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // Productos y Categorías
    Route::resource('products', \App\Http\Controllers\ProductController::class);
    
    // Gastos
    Route::get('/expenses/details', [\App\Http\Controllers\ExpenseController::class, 'getCategoryDetails'])->name('expenses.details');
    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class);
    Route::resource('expense-categories', \App\Http\Controllers\ExpenseCategoryController::class);
    Route::get('/workers/details', [\App\Http\Controllers\WorkerExpenseController::class, 'getWorkerDetails'])->name('workers.details');
    Route::resource('workers', \App\Http\Controllers\WorkerExpenseController::class);

    // Clientes y Proveedores
    Route::resource('clients', \App\Http\Controllers\ClientController::class);
    Route::resource('providers', \App\Http\Controllers\ProviderController::class);

    // Compras y Ventas
    Route::resource('purchases', \App\Http\Controllers\PurchaseController::class);
    Route::get('purchases/{id}/ticket', [\App\Http\Controllers\PurchaseController::class, 'ticket'])->name('purchases.ticket');
    Route::resource('sales', \App\Http\Controllers\SaleController::class);
    Route::get('sales/{id}/ticket', [\App\Http\Controllers\SaleController::class, 'ticket'])->name('sales.ticket');

    // Cuentas por Cobrar (Credits)
    Route::get('credits/client/{id}', [\App\Http\Controllers\CreditController::class, 'showClient'])->name('credits.showClient');
    Route::resource('credits', \App\Http\Controllers\CreditController::class);
    Route::post('credits/payment', [\App\Http\Controllers\CreditController::class, 'storePayment'])->name('credits.payment');
    Route::get('credits/payment/{id}/edit', [\App\Http\Controllers\CreditController::class, 'editPayment'])->name('credits.payment.edit');
    Route::put('credits/payment/{id}', [\App\Http\Controllers\CreditController::class, 'updatePayment'])->name('credits.payment.update');
    Route::delete('credits/client/{id}', [\App\Http\Controllers\CreditController::class, 'destroyByClient'])->name('credits.destroyByClient');

    // Cuentas por Pagar (Account Payables)
    Route::get('cuentas-por-pagar/provider/{id}', [App\Http\Controllers\AccountPayableController::class, 'showProvider'])->name('cuentas-por-pagar.showProvider');
    Route::resource('cuentas-por-pagar', App\Http\Controllers\AccountPayableController::class);
    Route::post('cuentas-por-pagar/payment', [\App\Http\Controllers\AccountPayableController::class, 'storePayment'])->name('cuentas-por-pagar.payment');
    Route::get('cuentas-por-pagar/payment/{id}/edit', [\App\Http\Controllers\AccountPayableController::class, 'editPayment'])->name('cuentas-por-pagar.payment.edit');
    Route::put('cuentas-por-pagar/payment/{id}', [\App\Http\Controllers\AccountPayableController::class, 'updatePayment'])->name('cuentas-por-pagar.payment.update');
    Route::delete('cuentas-por-pagar/payment/{id}', [\App\Http\Controllers\AccountPayableController::class, 'destroyPayment'])->name('cuentas-por-pagar.payment.destroy');

    // Reportes
    Route::get('/reports/products', [\App\Http\Controllers\ProductReportController::class, 'index'])->name('reports.products');
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/daily', [\App\Http\Controllers\ReportController::class, 'daily'])->name('reports.daily');
    Route::match(['get', 'post'], '/reports/financial', [\App\Http\Controllers\ReportController::class, 'financial'])->name('reports.financial');
    
    // Inventory
    Route::get('/reports/inventory', [\App\Http\Controllers\ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/inventory/barcodes', [\App\Http\Controllers\ReportController::class, 'exportPdfBarcodes'])->name('reports.inventory.barcodes'); // PDF Barcodes
    Route::get('/reports/inventory/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdfInventory'])->name('reports.inventory.pdf');
    Route::get('/reports/history', [\App\Http\Controllers\ReportController::class, 'sales'])->name('reports.purchases&sales');
    Route::get('/reports/history/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdfSales'])->name('reports.purchases&sales.pdf');
    Route::get('/reports/history/export', [\App\Http\Controllers\ReportController::class, 'exportSales'])->name('reports.purchases&sales.export'); // Excel
    Route::get('/reports/daily/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdfDaily'])->name('reports.daily.pdf');
    Route::get('/reports/daily/export', [\App\Http\Controllers\ReportController::class, 'exportDaily'])->name('reports.daily.export'); // Excel
    
    Route::get('/reports/financial/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdfFinancial'])->name('reports.financial.pdf');
    Route::get('/reports/financial/export', [\App\Http\Controllers\ReportController::class, 'exportFinancial'])->name('reports.financial.export'); // Excel

    Route::get('/reports/inventory/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdfInventory'])->name('reports.inventory.pdf');
    Route::get('/reports/inventory/export', [\App\Http\Controllers\ReportController::class, 'exportInventory'])->name('reports.inventory.export'); // Excel
    
    Route::get('/reports/sales/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdfSales'])->name('reports.sales.pdf');
    Route::get('/reports/sales/export', [\App\Http\Controllers\ReportController::class, 'exportSales'])->name('reports.sales.export'); // Excel
    
    Route::get('/reports/manual', [\App\Http\Controllers\ReportController::class, 'manual'])->name('reports.manual');
    Route::get('/reports/manual/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdfManual'])->name('reports.manual.pdf');

    // Configuración Inicial
    Route::get('/initial-setup', [\App\Http\Controllers\InitialSetupController::class, 'index'])->name('initial-setup.index');
    Route::post('/initial-setup/toggle', [\App\Http\Controllers\InitialSetupController::class, 'toggleMode'])->name('initial-setup.toggle-mode');
    Route::get('/initial-setup/cash', [\App\Http\Controllers\InitialSetupController::class, 'setCashBalance'])->name('initial-setup.cash');
    Route::post('initial-setup/cash', [\App\Http\Controllers\InitialSetupController::class, 'storeCashBalance'])->name('initial-setup.store-cash');
    Route::post('initial-setup/banks', [\App\Http\Controllers\InitialSetupController::class, 'storeBankBases'])->name('initial-setup.store-banks');
    Route::post('/initial-setup/reset', [\App\Http\Controllers\InitialSetupController::class, 'resetDatabase'])->name('initial-setup.reset');
    Route::get('/initial-setup/debug', [\App\Http\Controllers\InitialSetupController::class, 'debugCleanup'])->name('initial-setup.debug');
    
    // Inventory Adjustment (Silent)
    Route::post('/inventory/adjust', [\App\Http\Controllers\InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/reset', [\App\Http\Controllers\SettingController::class, 'resetTransactions'])->name('settings.reset_transactions');
});
