<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Movement;
use App\Models\Product;
use App\Models\Credit;
use App\Models\AccountPayable;
use Illuminate\Support\Facades\DB;

class InitialSetupController extends Controller
{
    /**
     * Display the initial setup page
     */
    public function index()
    {
        $isInitialMode = Setting::isInitialMode();
        $initialCash = Setting::getInitialCash();
        $modeClosed = Setting::isInitialModeClosed();
        
        // Get summary of initial data
        $initialInventoryCount = Movement::where('is_initial', true)->count();
        
        // Sum of initial receivables
        $initialCreditsSum = Credit::where('is_initial', true)->sum('total_debt');
        
        // Sum of initial payables
        $initialPayablesSum = AccountPayable::where('is_initial', true)->sum('amount');

        return view('initial_setup.index', compact(
            'isInitialMode',
            'initialCash',
            'modeClosed',
            'initialInventoryCount',
            'initialCreditsSum',
            'initialPayablesSum'
        ));
    }

    /**
     * Toggle initial inventory mode
     */
    public function toggleMode(Request $request)
    {
        $currentMode = Setting::isInitialMode();
        
        // If trying to activate mode
        if (!$currentMode) {
            // Check if there are operational movements
            $hasOperationalMovements = Movement::where('is_initial', false)->exists();
            
            if ($hasOperationalMovements) {
                return back()->with('error', 'No se puede activar el modo inicial si ya existen movimientos operativos.');
            }
            
            Setting::set('initial_inventory_mode', 'true');
            return back()->with('success', 'Modo Inventario Inicial activado. Ahora puedes registrar tu inventario base.');
        } 
        // If trying to deactivate mode
        else {
            Setting::set('initial_inventory_mode', 'false');
            Setting::set('initial_mode_closed_at', now()->toDateTimeString());
            
            return back()->with('success', 'Modo Inventario Inicial cerrado. El sistema ahora opera normalmente.');
        }
    }

    /**
     * Show cash balance form
     */
    public function setCashBalance()
    {
        if (!Setting::isInitialMode()) {
            return redirect()->route('dashboard')->with('error', 'Debes activar el Modo Inventario Inicial primero.');
        }

        $initialCash = Setting::getInitialCash();
        return view('initial_setup.cash_balance', compact('initialCash'));
    }

    /**
     * Store cash balance
     */
    public function storeCashBalance(Request $request)
    {
        if (!Setting::isInitialMode()) {
            return redirect()->route('dashboard')->with('error', 'Debes activar el Modo Inventario Inicial primero.');
        }

        $request->validate([
            'initial_cash' => 'required|numeric|min:0'
        ]);

        Setting::set('initial_cash_balance', $request->initial_cash);
        Setting::setResetTimestamp('cash');

        // Auto-close initial mode as requested
        Setting::set('initial_inventory_mode', 'false');
        if (!Setting::isInitialModeClosed()) {
            Setting::set('initial_mode_closed_at', now()->toDateTimeString());
        }

        return redirect()->route('initial-setup.index')->with('success', 'Efectivo inicial registrado. El Modo Inicial se ha cerrado automáticamente.');
    }

    /**
     * Store Bank Bases (Nequi & Bancolombia)
     */
    public function storeBankBases(Request $request)
    {
        $request->validate([
            'initial_nequi' => 'required|numeric|min:0',
            'initial_bancolombia' => 'required|numeric|min:0'
        ]);

        Setting::set('initial_nequi_balance', $request->initial_nequi);
        Setting::setResetTimestamp('nequi');
        
        Setting::set('initial_bancolombia_balance', $request->initial_bancolombia);
        Setting::setResetTimestamp('bancolombia');

        // Auto-close logic
        Setting::set('initial_inventory_mode', 'false');
        if (!Setting::isInitialModeClosed()) {
            Setting::set('initial_mode_closed_at', now()->toDateTimeString());
        }

        return redirect()->route('initial-setup.index')->with('success', 'Saldos bancarios registrados. El Modo Inicial se ha cerrado automáticamente.');
    }

    /**
     * Reset all database data (DANGER ZONE)
     */
    public function resetDatabase()
    {
        try {
            \Schema::disableForeignKeyConstraints();

            // Delete all operational data using Query Builder direct truncate
            \DB::table('movements')->truncate();
            \DB::table('sales')->truncate();
            \DB::table('purchases')->truncate();
            \DB::table('credits')->truncate();
            \DB::table('credit_payments')->truncate();
            \DB::table('expenses')->truncate();
            \DB::table('account_payables')->truncate();
            \DB::table('account_payable_payments')->truncate();
            \DB::table('batches')->truncate();
            \DB::table('sale_payments')->truncate();
            \DB::table('cash_adjustments')->truncate();
            \DB::table('cash_registers')->truncate();

            // Reset Stock and Prices for all products
            \DB::table('products')->update([
                'stock' => 0,
                'cost_price' => 0,
                'average_sale_price' => 0,
                'sale_price' => 0
            ]);

            // Reset settings
            Setting::set('initial_inventory_mode', 'false');
            Setting::set('initial_cash_balance', '0');
            Setting::set('initial_nequi_balance', '0');
            Setting::set('initial_bancolombia_balance', '0');
            Setting::set('initial_mode_closed_at', null);

            \Schema::enableForeignKeyConstraints();
            
            return redirect()->route('initial-setup.index')->with('success', 'Base de datos reseteada correctamente (Limpieza Forzada).');
        } catch (\Exception $e) {
            \Schema::enableForeignKeyConstraints(); // Ensure constraints are re-enabled even on error
            return redirect()->route('initial-setup.index')->with('error', 'Error al resetear la base de datos: ' . $e->getMessage());
        }
    }

    /**
     * DEBUG: Force cleanup and show status
     */
    public function debugCleanup()
    {
        $before = \DB::table('batches')->count();
        
        \Schema::disableForeignKeyConstraints();
        \DB::table('batches')->truncate();
        \DB::table('products')->update([
            'stock' => 0,
            'cost_price' => 0,
            'average_sale_price' => 0,
            'sale_price' => 0
        ]);
        \Schema::enableForeignKeyConstraints();
        
        $after = \DB::table('batches')->count();
        $productsWithStock = \DB::table('products')->where('stock', '>', 0)->count();

        return "DEBUG RESULTADO:<br>Lotes antes: $before<br>Lotes después: $after<br>Productos con stock > 0: $productsWithStock<br><br>Si 'Lotes después' es 0, el sistema está limpio.";
    }

    /**
     * Import products and initial stock from CSV
     */
    public function importProducts(Request $request)
    {
        if (!Setting::isInitialMode()) {
            return back()->with('error', 'El Modo Inicial debe estar activo para importar inventario.');
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip header
        $header = fgetcsv($handle, 1000, ',');
        
        $importedCount = 0;
        $errorCount = 0;

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if (count($data) < 5) continue;

                $name = strtoupper(trim($data[0]));
                $sku = strtoupper(trim($data[1]));
                $salePrice = (float) $data[2];
                $costPrice = (float) $data[3];
                $stock = (float) $data[4];

                if (empty($sku) || empty($name)) {
                    $errorCount++;
                    continue;
                }

                // Create or Update Product
                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $name,
                        'sale_price' => $salePrice,
                        'cost_price' => $costPrice,
                        'stock' => $stock,
                        'measure_type' => 'unit', // Default
                        'status' => 'active'
                    ]
                );

                // If stock > 0, create initial movement
                if ($stock > 0) {
                    Movement::create([
                        'product_sku' => $sku,
                        'type' => 'input',
                        'quantity' => $stock,
                        'description' => 'CARGA INICIAL MASIVA',
                        'is_initial' => true,
                        'cost_at_moment' => $costPrice
                    ]);
                }

                $importedCount++;
            }
            
            DB::commit();
            fclose($handle);

            return back()->with('success', "Importación completada: $importedCount productos procesados. Errores: $errorCount.");
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Error durante la importación: ' . $e->getMessage());
        }
    }

    /**
     * Download CSV Template
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="plantilla_productos_shevere.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NOMBRE', 'SKU', 'PRECIO_VENTA', 'COSTO', 'STOCK_INICIAL']);
            fputcsv($file, ['PRODUCTO DE EJEMPLO', '123456', '5000', '3500', '10']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
