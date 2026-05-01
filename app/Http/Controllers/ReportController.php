<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movement;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Credit;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{



    public function index()
    {
        return view('reports.index');
    }

    public function financial(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // 1. REAL Data (What actually happened)
        $salesMovements = Movement::where('type', 'sale')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with('product')
            ->get();

        $totalRealSales = $salesMovements->sum('total');

        // 2. EXPECTED Data (Based on average/list price)
        $totalExpectedSales = $salesMovements->reduce(function ($carry, $movement) {
            $expectedPrice = $movement->product ? ($movement->product->average_sale_price ?? 0) : 0;
            return $carry + ($movement->quantity * $expectedPrice);
        }, 0);

        // 3. Costo de Ventas (Estimado con costo actual)
        $totalCost = $salesMovements->reduce(function ($carry, $movement) {
            $cost = $movement->product ? $movement->product->cost_price : 0; 
            return $carry + ($movement->quantity * $cost);
        }, 0);

        // 4. Gastos (Operacionales)
        // 4. Gastos (Operacionales) Detallados
        // 4. Gastos (Operacionales) Detallados
        $expenses = Expense::whereBetween('expense_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select('description', DB::raw('sum(amount) as total'))
            ->groupBy('description')
            ->get();
        $totalExpenses = $expenses->sum('total');

        // Cálculos Finales
        $realGrossProfit = $totalRealSales - $totalCost;
        $expectedGrossProfit = $totalExpectedSales - $totalCost;

        $realNetProfit = $realGrossProfit - $totalExpenses;
        $expectedNetProfit = $expectedGrossProfit - $totalExpenses;

        // 5. "Dónde está la utilidad" (Metrics)
        // Inventory Value (Current Stock * Avg Cost)
        $inventoryValuation = Product::all()->sum(function($p) {
            return $p->stock * $p->cost_price;
        });

        // Accounts Receivable (Pending Credits)
        $totalAccountsReceivable = Credit::where('status', 'pending')->sum(DB::raw('total_debt - paid_amount'));

        // Losses (Averías/Salidas) in the period
        // Assuming 'adjustment_exit' or similar tracks losses. 
        // Need to check specific movement types, assuming 'exit' is manually created losses/adjustments.
        // If type is just 'sale' or 'purchase', we might need to look for specific 'notes' or a new type.
        // For now, I'll assume 'exit' (Salida Manual/Merma).
        $lossesValuation = Movement::where('type', 'exit')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('total'); // Assuming 'total' is cost-based for exits

        // Accounts Payable (New debts in period - Balance only)
        // User requested: "ir modificandose según los abonos"
        $totalAccountsPayable = \App\Models\AccountPayable::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get()
            ->sum(function($cuenta) {
                return $cuenta->amount - $cuenta->paid_amount;
            });

        // Update Operational Profit formula
        // Revised per user request: Sales - Cost = Gross Profit - Expenses = Operational Profit
        // Debts are now tracked in the "Asset Distribution" section only.
        $operationalProfit = $realNetProfit;

        // 1. Previous Balance (Acumulado anterior al rango)
        $startStr = $startDate . ' 00:00:00';
        $endStr = $endDate . ' 23:59:59';
        
        $initialCash = \App\Models\Setting::getInitialCash();
        $resetCashAt = \App\Models\Setting::getResetTimestamp('cash');

        $prevIncome = \App\Models\Movement::where('type', 'sale')
            ->where('payment_method', 'cash')->where('is_initial', false)
            ->where('created_at', '>=', $resetCashAt)
            ->where('created_at', '<', $startStr)->sum('total') 
            + \App\Models\CreditPayment::where('payment_method', 'cash')
            ->where('created_at', '>=', $resetCashAt)
            ->where('created_at', '<', $startStr)->sum('amount');
        
        $prevOutgo = \App\Models\Expense::where('payment_method', 'cash')->where('expense_date', '>=', $resetCashAt)->where('expense_date', '<', $startStr)->sum('amount') 
            + \App\Models\Movement::where('type', 'purchase')
            ->where('payment_method', 'cash')->where('is_initial', false)
            ->where('created_at', '>=', $resetCashAt)
            ->where('created_at', '<', $startStr)->sum('total')
            + \App\Models\AccountPayablePayment::where('payment_method', 'cash')
            ->where('payment_date', '>=', $resetCashAt)
            ->where('payment_date', '<', $startStr)->sum('amount');

        $previousDayBalance = $initialCash + $prevIncome - $prevOutgo;

        // 2. Period Data (Rango seleccionado) -> Componentes de la fórmula solicitada:
        // ($previousDayBalance + $cashSales + $paymentsToday - $expensesToday - $cashPurchases - $cashPaymentsPaid)
        
        $cashSales = \App\Models\Movement::where('type', 'sale')
            ->where('payment_method', 'cash')->where('is_initial', false)
            ->whereBetween('created_at', [$startStr, $endStr])->sum('total');

        $paymentsToday = \App\Models\CreditPayment::where('payment_method', 'cash')
            ->whereBetween('created_at', [$startStr, $endStr])->sum('amount');

        $expensesToday = \App\Models\Expense::where('payment_method', 'cash')->whereBetween('expense_date', [$startStr, $endStr])->sum('amount');

        $cashPurchases = \App\Models\Movement::where('type', 'purchase')
            ->where('payment_method', 'cash')->where('is_initial', false)
            ->whereBetween('created_at', [$startStr, $endStr])->sum('total');
            
        $cashPaymentsPaid = \App\Models\AccountPayablePayment::where('payment_method', 'cash')
            ->whereBetween('payment_date', [$startStr, $endStr])->sum('amount');

        // Cálculo final solicitado
        $totalCash = ($previousDayBalance + $cashSales + $paymentsToday - $expensesToday - $cashPurchases - $cashPaymentsPaid);

        return view('reports.financial', compact(
            'startDate', 
            'endDate', 
            'totalRealSales', 
            'totalExpectedSales',
            'totalCost', 
            'realGrossProfit', 
            'expectedGrossProfit',
            'totalExpenses', 
            'expenses', // Pass detail
            'realNetProfit',
            'expectedNetProfit',
            'inventoryValuation',
            'totalAccountsReceivable',
            'lossesValuation',
            'operationalProfit',
            'totalAccountsPayable',
            'totalCash',
            // Detailed Cash vars
            'previousDayBalance',
            'cashSales',
            'paymentsToday',
            'expensesToday',
            'cashPurchases',
            'cashPaymentsPaid'
        ));
    }
    public function inventory()
    {
        $products = \App\Models\Product::orderBy('name')->get();
        $totalValue = $products->sum(function($product) {
            return $product->stock * $product->cost_price;
        });

        return view('reports.inventory', compact('products', 'totalValue'));
    }

    public function sales(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $type = $request->input('type', 'all');

        // Fetch Sales Headers
        $sales = collect();
        if ($type == 'all' || $type == 'sale') {
            $sales = \App\Models\Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->with(['movements.product', 'client'])
                ->get()
                ->map(function($sale) {
                    $sale->type = 'sale';
                    return $sale;
                });
        }

        // Fetch Purchases Headers
        $purchases = collect();
        if ($type == 'all' || $type == 'purchase') {
            $purchases = \App\Models\Purchase::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->with(['movements.product', 'provider'])
                ->get()
                ->map(function($purchase) {
                    $purchase->type = 'purchase';
                    return $purchase;
                });
        }

        // Merge and Sort by date
        $transactions = $sales->concat($purchases)->sortByDesc('created_at');

        // Manual Pagination
        $perPage = 50;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $currentItems = $transactions->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedItems = new \Illuminate\Pagination\LengthAwarePaginator($currentItems, $transactions->count(), $perPage, $currentPage, [
            'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()
        ]);

        return view('reports.purchases&sales', [
            'transactions' => $paginatedItems, 
            'startDate' => $startDate,
            'endDate' => $endDate,
            'type' => $type
        ]);
    }

    // --- Export Methods ---

    // --- Export Methods using SimpleXLSXGen ---

    public function exportFinancial(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Re-calculate data
        $salesMovements = Movement::where('type', 'sale')->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->with('product')->get();
        
        // 1. REAL Data (What actually happened)
        $totalRealSales = $salesMovements->sum('total');
        
        // 2. EXPECTED Data (Based on average/list price)
        $totalExpectedSales = $salesMovements->reduce(function ($carry, $movement) {
            $expectedPrice = $movement->product ? ($movement->product->average_sale_price ?? 0) : 0;
            return $carry + ($movement->quantity * $expectedPrice);
        }, 0);

        // 3. COST (COGS)
        $totalCost = $salesMovements->reduce(function ($carry, $movement) {
            $cost = $movement->product ? $movement->product->cost_price : 0; 
            return $carry + ($movement->quantity * $cost);
        }, 0);

        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');

        // Profit Calcs
        $realGrossProfit = $totalRealSales - $totalCost;
        $expectedGrossProfit = $totalExpectedSales - $totalCost;
        
        $realNetProfit = $realGrossProfit - $totalExpenses;
        $expectedNetProfit = $expectedGrossProfit - $totalExpenses;

        // Header Style
        $hStyle = '<style bgcolor="#8B0000" color="#FFFFFF"><center><b>';
        $hClose = '</b></center></style>';

        $data = [
            ['<style font-size="18"><center><b>Estado de Resultados (Real vs Esperado)</b></center></style>'],
            ['<center><b>Desde:</b> ' . $startDate . ' | <b>Hasta:</b> ' . $endDate . '</center>'],
            [],
            [$hStyle . 'Concepto' . $hClose, $hStyle . 'Real (Facturado)' . $hClose, $hStyle . 'Esperado (Precio Prom.)' . $hClose],
            
            // Revenues
            ['Ingresos por Ventas', '<right>$ ' . number_format($totalRealSales, 0) . '</right>', '<right>$ ' . number_format($totalExpectedSales, 0) . '</right>'],
            
            // Costs
            ['(-) Costo de Ventas', '<right>$ ' . number_format($totalCost, 0) . '</right>', '<right>$ ' . number_format($totalCost, 0) . '</right>'],
            
            // Gross Profit
            ['<style bgcolor="#f0f0f0"><b>Utilidad Bruta</b></style>', 
             '<style bgcolor="#f0f0f0"><right><b>$ ' . number_format($realGrossProfit, 0) . '</b></right></style>',
             '<style bgcolor="#f0f0f0"><right><b>$ ' . number_format($expectedGrossProfit, 0) . '</b></right></style>'
            ],
            
            // Expenses
            ['(-) Gastos Operacionales', '<right>$ ' . number_format($totalExpenses, 0) . '</right>', '<right>$ ' . number_format($totalExpenses, 0) . '</right>'],
            
            // Net Profit -> Now Operational Profit per user request
            ['<style bgcolor="#dff0d8"><b>Utilidad Operacional</b></style>', 
             '<style bgcolor="#dff0d8"><right><b>$ ' . number_format($realNetProfit, 0) . '</b></right></style>',
             '<style bgcolor="#dff0d8"><right><b>$ ' . number_format($expectedNetProfit, 0) . '</b></right></style>'
            ],
            
            [],
            ['<style color="#888888">Nota: "Real" es lo efectivamente cobrado (con precios modificados). "Esperado" es si se hubiera vendido al precio promedio del inventario.</style>']
        ];

        $filename = 'Estado_Resultados_' . date('Y-m-d_h-i_A') . '.xlsx';
        return \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs($filename);
    }

    public function exportInventory()
    {
        $products = \App\Models\Product::orderBy('name')->get();
        
        // Header Style: Dark Red Background, White Text, Centered, Bold
        $hStyle = '<style bgcolor="#8B0000" color="#FFFFFF"><center><b>';
        $hClose = '</b></center></style>';

        $data = [
            ['<style font-size="18"><center><b>INVENTARIO VALORIZADO - ' . \App\Models\Setting::getBusinessName() . '</b></center></style>'],
            ['<center><b>Fecha Corte:</b> ' . date('d/m/Y h:i A') . '</center>'],
            [],
            [
                $hStyle . 'SKU' . $hClose, 
                $hStyle . 'Producto' . $hClose, 
                $hStyle . 'Stock' . $hClose, 
                $hStyle . 'Unidad' . $hClose, 
                $hStyle . 'Costo Unit.' . $hClose, 
                $hStyle . 'Valor Total' . $hClose
            ]
        ];

        foreach ($products as $product) {
            // Highlight low stock
            $stockStyle = $product->stock <= $product->min_stock ? '<style color="#FF0000"><b>' . $product->stock . '</b></style>' : $product->stock;

            $data[] = [
                '<left>' . $product->sku . '</left>',
                $product->name,
                '<center>' . $stockStyle . '</center>',
                '<center>' . $product->measure_type . '</center>',
                '<right>$' . number_format($product->cost_price, 0) . '</right>',
                '<right>$' . number_format($product->stock * $product->cost_price, 0) . '</right>'
            ];
        }

        // Add Total Row
        $totalValue = $products->sum(function($p) { return $p->stock * $p->cost_price; });
        $data[] = [];
        $data[] = ['', '', '', '', '<right><b>TOTAL INVENTARIO:</b></right>', '<style bgcolor="#FFFF00"><right><b>$ ' . number_format($totalValue, 0) . '</b></right></style>'];

        $filename = 'Inventario_' . date('Y-m-d_H-i') . '.xlsx';
        return \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs($filename);
    }

    public function exportSales(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        $type = $request->input('type', 'all');

        // Fetch Sales Headers
        $sales = collect();
        if ($type == 'all' || $type == 'sale') {
            $sales = \App\Models\Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->with(['movements.product', 'client'])
                ->get()
                ->map(function($sale) {
                    $sale->type = 'sale';
                    return $sale;
                });
        }

        // Fetch Purchases Headers
        $purchases = collect();
        if ($type == 'all' || $type == 'purchase') {
            $purchases = \App\Models\Purchase::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->with(['movements.product', 'provider'])
                ->get()
                ->map(function($purchase) {
                    $purchase->type = 'purchase';
                    return $purchase;
                });
        }

        // Merge and Sort
        $transactions = $sales->concat($purchases)->sortByDesc('created_at');
        
        $hStyle = '<style bgcolor="#8B0000" color="#FFFFFF"><center><b>';
        $hClose = '</b></center></style>';

        $data = [
            ['<style font-size="18"><center><b>REPORTE DE COMPRAS Y VENTAS</b></center></style>'],
            ['<center><b>Desde:</b> ' . $startDate . ' | <b>Hasta:</b> ' . $endDate . '</center>'],
            [],
            [
                $hStyle . 'N° Factura' . $hClose,
                $hStyle . 'Tipo' . $hClose,
                $hStyle . 'Fecha' . $hClose, 
                $hStyle . 'Producto' . $hClose, 
                $hStyle . 'Cliente / Prov.' . $hClose, 
                $hStyle . 'Cant.' . $hClose, 
                $hStyle . 'Unidad' . $hClose, 
                $hStyle . 'Precio Unit.' . $hClose, 
                $hStyle . 'Total' . $hClose,
                $hStyle . 'Método' . $hClose
            ]
        ];

        foreach ($transactions as $t) {
            $id = str_pad($t->id, 6, '0', STR_PAD_LEFT);
            $type = ($t->type == 'sale') ? 'Venta' : 'Compra';
            $clientName = $t->type == 'sale' ? ($t->client->name ?? 'Consumidor Final') : ($t->provider->name ?? 'Proveedor');

            // Product Summary
            $productCount = $t->movements->count();
            $firstProduct = $t->movements->first()->product->name ?? 'N/A';
            $productSummary = ($productCount > 1) ? $firstProduct . " (+ " . ($productCount - 1) . " más)" : $firstProduct;

            // Payment Method Logic
            $pmLabel = 'N/A';
            if ($t->type == 'sale') {
                $pm = $t->payment_method;
            } else {
                $pm = $t->movements->first()->payment_method ?? 'N/A';
            }

            if($pm == 'cash') $pmLabel = 'Efectivo';
            elseif($pm == 'bank' || $pm == 'transfer') $pmLabel = 'Transf.';
            elseif($pm == 'credit') $pmLabel = 'Crédito';
            else $pmLabel = ucfirst($pm);

            $data[] = [
                '#' . $id,
                $type,
                '<center>' . $t->created_at->format('d/m/Y h:i A') . '</center>',
                $productSummary,
                $clientName,
                '<center>' . $t->movements->sum('quantity') . '</center>',
                '<center>-</center>',
                '<right>' . ($t->type == 'sale' ? '-' : '-') . '</right>', // We don't show unit price for grouped
                '<right><b>' . number_format($t->total_amount, 0) . '</b></right>',
                '<center>' . $pmLabel . '</center>'
            ];
        }

        $filename = 'Historial_Compras_Ventas_' . date('Y-m-d_h-i_A') . '.xlsx';
        return \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs($filename);
    }

    // --- PDF Export Methods ---

    public function exportPdfFinancial(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Re-calculate data
        $salesMovements = Movement::where('type', 'sale')->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->with('product')->get();
        
        // 1. REAL Data
        $totalRealSales = $salesMovements->sum('total');

        // 2. EXPECTED Data
        $totalExpectedSales = $salesMovements->reduce(function ($carry, $movement) {
            $expectedPrice = $movement->product ? ($movement->product->average_sale_price ?? 0) : 0;
            return $carry + ($movement->quantity * $expectedPrice);
        }, 0);

        // 3. COSTS
        $totalCost = $salesMovements->reduce(function ($carry, $movement) {
            $cost = $movement->product ? $movement->product->cost_price : 0; 
            return $carry + ($movement->quantity * $cost);
        }, 0);

        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');
        
        // Profit Calcs
        $realGrossProfit = $totalRealSales - $totalCost;
        $expectedGrossProfit = $totalExpectedSales - $totalCost;
        
        $realNetProfit = $realGrossProfit - $totalExpenses;
        $expectedNetProfit = $expectedGrossProfit - $totalExpenses;

        $pdf = Pdf::loadView('reports.exports.financial', compact(
            'startDate', 'endDate', 
            'totalRealSales', 'totalExpectedSales', 
            'totalCost', 
            'realGrossProfit', 'expectedGrossProfit', 
            'totalExpenses', 
            'realNetProfit', 'expectedNetProfit'
        ));
        return $pdf->download('Estado_Resultados_' . date('Y-m-d_H-i') . '.pdf');
    }

    public function exportPdfInventory()
    {
        $products = \App\Models\Product::orderBy('name')->get();
        $totalValue = $products->sum(function($product) { return $product->stock * $product->cost_price; });
        
        $pdf = Pdf::loadView('reports.exports.inventory', compact('products', 'totalValue'));
        return $pdf->download('Inventario_' . date('Y-m-d_H-i') . '.pdf');
    }

    public function exportPdfBarcodes()
    {
        $products = \App\Models\Product::orderBy('name')->get();
        
        // We will generate the barcodes in the view using the HTML Generator (maximum DomPDF compatibility, no GD required)
        $generator = new \Picqer\Barcode\BarcodeGeneratorHTML();
        
        $pdf = Pdf::loadView('reports.exports.barcodes', compact('products', 'generator'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('Catalogo_Codigos_' . date('Y-m-d_H-i') . '.pdf');
    }

    public function exportPdfSales(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        $type = $request->input('type', 'all');

        // Fetch Sales Headers
        $sales = collect();
        if ($type == 'all' || $type == 'sale') {
            $sales = \App\Models\Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->with(['movements.product', 'client'])
                ->get()
                ->map(function($sale) {
                    $sale->type = 'sale';
                    return $sale;
                });
        }

        // Fetch Purchases Headers
        $purchases = collect();
        if ($type == 'all' || $type == 'purchase') {
            $purchases = \App\Models\Purchase::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->with(['movements.product', 'provider'])
                ->get()
                ->map(function($purchase) {
                    $purchase->type = 'purchase';
                    return $purchase;
                });
        }

        // Merge and Sort
        $transactions = $sales->concat($purchases)->sortByDesc('created_at');
        
        $pdf = Pdf::loadView('reports.exports.sales', ['transactions' => $transactions, 'startDate' => $startDate, 'endDate' => $endDate]);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('Historial_Compras_Ventas_' . date('Y-m-d_H-i') . '.pdf');
    }

    public function exportPdfManual()
    {
        $pdf = Pdf::loadView('reports.exports.manual');
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('Manual_Usuario_Carniceria_Salome.pdf');
    }

    public function manual()
    {
        return view('reports.manual');
    }

    // --- Daily Summary Export Methods ---

    public function exportDaily(Request $request)
    {
        $dateParam = $request->input('date', Carbon::today()->format('Y-m-d'));
        $today = Carbon::parse($dateParam)->startOfDay();
        $dateStr = $today->format('d/m/Y');

        // 1. Data Fetching
        $allMovementsToday = Movement::whereDate('created_at', $today)
            ->where('is_initial', false)
            ->with('product')
            ->get();
        $expensesTodayList = Expense::whereDate('expense_date', $today)->get();
        $paymentsTodayList = \App\Models\CreditPayment::whereDate('created_at', $today)->with('credit.client')->get();
        $purchasePaymentsTodayList = \App\Models\AccountPayablePayment::whereDate('payment_date', $today)->with('accountPayable.provider')->get();

        // Arqueo Logic
        $initialCash = \App\Models\Setting::getInitialCash();
        $resetCashAt = \App\Models\Setting::getResetTimestamp('cash');

        $historyIncome = Movement::where('type', 'sale')->where('is_initial', false)->where('payment_method', 'cash')
            ->where('created_at', '>=', $resetCashAt)
            ->whereDate('created_at', '<', $today)->sum('total') 
            + \App\Models\CreditPayment::where('payment_method', 'cash')
            ->where('created_at', '>=', $resetCashAt)
            ->whereDate('created_at', '<', $today)->sum('amount');
        
        $historyOutgo = Expense::where('payment_method', 'cash')
            ->where('expense_date', '>=', $resetCashAt)
            ->whereDate('expense_date', '<', $today)->sum('amount') 
            + Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'cash')
            ->where('created_at', '>=', $resetCashAt)
            ->whereDate('created_at', '<', $today)->sum('total')
            + \App\Models\AccountPayablePayment::where('payment_method', 'cash')
            ->where('payment_date', '>=', $resetCashAt)
            ->whereDate('payment_date', '<', $today)->sum('amount');

        $previousDayBalance = $initialCash + $historyIncome - $historyOutgo;

        // Cash breakdown
        // OLD: $cashSales = $allMovementsToday->where('type', 'sale')->where('payment_method', 'cash')->sum('total');
        // NEW: Sum from SalePayment where sale was created today
        $salesTodayIds = \App\Models\Sale::whereDate('created_at', $today)->pluck('id');
        $cashSales = \App\Models\SalePayment::whereIn('sale_id', $salesTodayIds)->where('payment_method', 'cash')->sum('amount');
        
        // Also add legacy cash sales if migration hasn't run? 
        // Better to rely on migration script to fill SalePayment. 
        // However, we can add a fallback: if no SalePayments found for a paid sale, look at movement?
        // Let's stick to the plan: Migration script will be mandatory.
        
        $cashPurchases = $allMovementsToday->where('type', 'purchase')->where('payment_method', 'cash')->sum('total');
        $cashPaymentsReceived = $paymentsTodayList->where('payment_method', 'cash')->sum('amount'); // CreditPayments
        $cashPaymentsPaid = $purchasePaymentsTodayList->where('payment_method', 'cash')->sum('amount');
        
        // Bank breakdown
        // OLD: $bankSales = $allMovementsToday->where('type', 'sale')->whereIn('payment_method', ['nequi', 'bancolombia'])->sum('total');
        $bankSales = \App\Models\SalePayment::whereIn('sale_id', $salesTodayIds)->whereIn('payment_method', ['nequi', 'bancolombia'])->sum('amount');

        $bankPaymentsReceived = $paymentsTodayList->whereIn('payment_method', ['nequi', 'bancolombia'])->sum('amount');
        $bankPaymentsPaid = $purchasePaymentsTodayList->whereIn('payment_method', ['nequi', 'bancolombia'])->sum('amount');
        
        $expensesToday = $expensesTodayList->where('payment_method', 'cash')->sum('amount');

        // Manual Adjustments
        $adjustmentsToday = \App\Models\CashAdjustment::whereDate('created_at', $today->toDateString())->get();
        $adjEntryCash = $adjustmentsToday->where('type', 'entry')->where('payment_method', 'cash')->sum('amount');
        $adjExitCash = $adjustmentsToday->where('type', 'exit')->where('payment_method', 'cash')->sum('amount');
        $adjEntryBank = $adjustmentsToday->where('type', 'entry')->whereIn('payment_method', ['nequi', 'bancolombia', 'bank', 'transfer'])->sum('amount');
        $adjExitBank = $adjustmentsToday->where('type', 'exit')->whereIn('payment_method', ['nequi', 'bancolombia', 'bank', 'transfer'])->sum('amount');

        // Cash Balance
        $efectivoTotal = $previousDayBalance + $cashSales + $cashPaymentsReceived + $adjEntryCash - ($expensesToday + $cashPurchases + $cashPaymentsPaid + $adjExitCash);
        
        // Bank Total Today
        $bancosTotalHoy = $bankSales + $bankPaymentsReceived + $adjEntryBank - ($bankPaymentsPaid + $adjExitBank);

        // 2. Excel Structure
        $hStyle = '<style bgcolor="#1976d2" color="#FFFFFF"><center><b>';
        $hClose = '</b></center></style>';
        $sectionStyle = '<style bgcolor="#f0f0f0"><b>';

        $data = [
            ['<style font-size="18"><center><b>CUADRE DE CAJA DIARIO - ' . \App\Models\Setting::getBusinessName() . '</b></center></style>'],
            ['<center><b>Fecha:</b> ' . $dateStr . '</center>'],
            [],
            // Arqueo Section - CASH
            [$hStyle . 'RESUMEN EFECTIVO (CAJA)' . $hClose, $hStyle . 'VALOR' . $hClose],
            ['Saldo Anterior (Acumulado)', '<right>$ ' . number_format($previousDayBalance, 0) . '</right>'],
            ['(+) Ventas Hoy (Efectivo)', '<right>$ ' . number_format($cashSales, 0) . '</right>'],
            ['(+) Abonos Recibidos (Efectivo)', '<right>$ ' . number_format($cashPaymentsReceived, 0) . '</right>'],
            ['(-) Gastos Hoy (Efectivo)', '<right>$ ' . number_format($expensesToday, 0) . '</right>'],
            ['(-) Costo de Ventas (Efectivo)', '<right>$ ' . number_format($cashPurchases, 0) . '</right>'],
            ['(-) Abonos Realizados (Efectivo)', '<right>$ ' . number_format($cashPaymentsPaid, 0) . '</right>'],
            ['<style bgcolor="#FFFF00"><b>TOTAL EFECTIVO EN CAJA</b></style>', '<style bgcolor="#FFFF00"><right><b>$ ' . number_format($efectivoTotal, 0) . '</b></right></style>'],
            [],
            // Arqueo Section - BANKS
            [$hStyle . 'RESUMEN BANCOS / TRANSFERENCIAS (HOY)' . $hClose, $hStyle . 'VALOR' . $hClose],
            ['(+) Ventas Hoy (Transferencia)', '<right>$ ' . number_format($bankSales, 0) . '</right>'],
            ['(+) Abonos Recibidos (Transferencia)', '<right>$ ' . number_format($bankPaymentsReceived, 0) . '</right>'],
            ['(-) Abonos Realizados (Transferencia)', '<right>$ ' . number_format($bankPaymentsPaid, 0) . '</right>'],
            ['<style bgcolor="#add8e6"><b>TOTAL EN BANCOS (DEL DÍA)</b></style>', '<style bgcolor="#add8e6"><right><b>$ ' . number_format($bancosTotalHoy, 0) . '</b></right></style>'],
            [],
            // Sales Detail
            [$hStyle . 'DETALLE DE VENTAS (1 A 1)' . $hClose, '', '', '', ''],
            [$sectionStyle . 'Hora' . $hClose, $sectionStyle . 'Cliente' . $hClose, $sectionStyle . 'Método' . $hClose, $sectionStyle . 'Total' . $hClose],
        ];

        foreach ($allMovementsToday->where('type', 'sale') as $sale) {
            $data[] = [
                $sale->created_at->format('h:i A'),
                $sale->client->name ?? 'Consumidor Final',
                strtoupper(match($sale->payment_method) {
                    'cash' => 'Efectivo',
                    'nequi' => 'Nequi',
                    'bancolombia' => 'Bancolombia',
                    'credit' => 'Crédito',
                    default => $sale->payment_method ?? 'Efectivo'
                }),
                '<right>$ ' . number_format($sale->total, 0) . '</right>'
            ];
        }

        $data[] = [];
        // Expenses Detail
        $data[] = [$hStyle . 'DETALLE DE GASTOS' . $hClose, ''];
        foreach ($expensesTodayList as $exp) {
            $data[] = [$exp->description, '<right>$ ' . number_format($exp->amount, 0) . '</right>'];
        }

        $filename = 'Cierre_de_Caja_' . date('Y-m-d_h-i_A') . '.xlsx';
        return \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs($filename);
    }

    public function exportPdfDaily(Request $request)
    {
        $dateParam = $request->input('date', Carbon::today()->format('Y-m-d'));
        $today = Carbon::parse($dateParam)->startOfDay();
        $dateStr = $today->format('d/m/Y');

        // 1. Fetch Details for "1 a 1" breakdown
        $salesToday = \App\Models\Sale::whereDate('created_at', $today->toDateString())
            ->whereHas('movements', function($q) {
                $q->where('is_initial', false);
            })
            ->with(['movements.product', 'client'])
            ->get();
        
        $purchasesToday = \App\Models\Purchase::whereDate('created_at', $today->toDateString())
            ->whereHas('movements', function($q) {
                $q->where('is_initial', false);
            })
            ->with(['movements.product', 'provider'])
            ->get();

        $expensesTodayList = Expense::whereDate('expense_date', $today->toDateString())->get();
        $paymentsTodayList = \App\Models\CreditPayment::whereDate('created_at', $today->toDateString())
            ->with('credit.client')
            ->get();
        
        $purchasePaymentsTodayList = \App\Models\AccountPayablePayment::whereDate('payment_date', $today->toDateString())
            ->with('accountPayable.provider')
            ->get();

        // 2. Calculations for Totals
        $allMovementsToday = Movement::whereDate('created_at', $today->toDateString())
            ->where('is_initial', false)
            ->get();
        
        $totalSales = $allMovementsToday->where('type', 'sale')->sum('total');
        $expensesToday = $expensesTodayList->where('payment_method', 'cash')->sum('amount');
        
        // Income components for Cash Balance
        // Income components for Cash Balance
        $salesTodayIds = \App\Models\Sale::whereDate('created_at', $today->toDateString())->pluck('id');
        
        $cashSales = \App\Models\SalePayment::whereIn('sale_id', $salesTodayIds)->where('payment_method', 'cash')->sum('amount');
        $transferSales = \App\Models\SalePayment::whereIn('sale_id', $salesTodayIds)->whereIn('payment_method', ['nequi', 'bancolombia'])->sum('amount');
        $creditSales = $allMovementsToday->where('type', 'sale')->where('payment_method', 'credit')->sum('total'); // Keep using movements for credit TOTAL
        
        $paymentsTodayReceived = $paymentsTodayList->where('payment_method', 'cash')->sum('amount');
        $bankPaymentsReceived = $paymentsTodayList->whereIn('payment_method', ['nequi', 'bancolombia'])->sum('amount');

        // Outgo components for Cash Balance
        $cashPurchases = $allMovementsToday->where('type', 'purchase')->where('payment_method', 'cash')->sum('total');
        $cashPaymentsPaid = $purchasePaymentsTodayList->where('payment_method', 'cash')->sum('amount');
        $bankPaymentsPaid = $purchasePaymentsTodayList->whereIn('payment_method', ['nequi', 'bancolombia'])->sum('amount');

        // Manual Adjustments
        $adjustmentsToday = \App\Models\CashAdjustment::whereDate('created_at', $today->toDateString())->get();
        $adjEntryCash = $adjustmentsToday->where('type', 'entry')->where('payment_method', 'cash')->sum('amount');
        $adjExitCash = $adjustmentsToday->where('type', 'exit')->where('payment_method', 'cash')->sum('amount');
        $adjEntryBank = $adjustmentsToday->where('type', 'entry')->whereIn('payment_method', ['nequi', 'bancolombia', 'bank', 'transfer'])->sum('amount');
        $adjExitBank = $adjustmentsToday->where('type', 'exit')->whereIn('payment_method', ['nequi', 'bancolombia', 'bank', 'transfer'])->sum('amount');

        $totalBank = $transferSales + $bankPaymentsReceived + $adjEntryBank - ($bankPaymentsPaid + $adjExitBank);

        // 3. Arqueo Logic (Saldo Anterior)
        $initialCash = \App\Models\Setting::getInitialCash();
        $resetCashAt = \App\Models\Setting::getResetTimestamp('cash');

        $historyIncome = Movement::where('type', 'sale')->where('is_initial', false)->where('payment_method', 'cash')
            ->where('created_at', '>=', $resetCashAt)
            ->whereDate('created_at', '<', $today)->sum('total') 
            + \App\Models\CreditPayment::where('payment_method', 'cash')
            ->where('created_at', '>=', $resetCashAt)
            ->whereDate('created_at', '<', $today)->sum('amount');
        
        $historyOutgo = Expense::where('payment_method', 'cash')
            ->where('expense_date', '>=', $resetCashAt)
            ->whereDate('expense_date', '<', $today)->sum('amount') 
            + Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'cash')
            ->where('created_at', '>=', $resetCashAt)
            ->whereDate('created_at', '<', $today)->sum('total')
            + \App\Models\AccountPayablePayment::where('payment_method', 'cash')
            ->where('payment_date', '>=', $resetCashAt)
            ->whereDate('payment_date', '<', $today)->sum('amount');

        $previousDayBalance = $initialCash + $historyIncome - $historyOutgo;

        // Cash Balance Calculation
        $efectivoTotal = $previousDayBalance + $cashSales + $paymentsTodayReceived + $adjEntryCash - ($expensesToday + $cashPurchases + $cashPaymentsPaid + $adjExitCash);

        // Gross Profit Calculation
        $totalCost = $allMovementsToday->where('type', 'sale')->sum(function($m) {
            return $m->quantity * ($m->product->cost_price ?? 0);
        });
        $grossProfit = $totalSales - $totalCost;
        $netProfit = $grossProfit - $expensesToday;

        $pdf = Pdf::loadView('reports.exports.daily_summary', [
            'dateStr' => $dateStr,
            'totalSales' => $totalSales,
            'totalCost' => $totalCost,
            'expensesToday' => $expensesToday,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'cashSales' => $cashSales,
            'transferSales' => $transferSales,
            'creditSales' => $creditSales,
            'paymentsToday' => $paymentsTodayReceived,
            'bankPayments' => $bankPaymentsReceived,
            'cashPaymentsPaid' => $cashPaymentsPaid,
            'bankPaymentsPaid' => $bankPaymentsPaid,
            'efectivoTotal' => $efectivoTotal,
            'totalBank' => $totalBank,
            'cashPurchases' => $cashPurchases,
            'previousDayBalance' => $previousDayBalance,
            'purchasesToday' => $purchasesToday,
            'salesToday' => $salesToday,
            'expensesTodayList' => $expensesTodayList,
            'paymentsTodayList' => $paymentsTodayList,
            'purchasePaymentsTodayList' => $purchasePaymentsTodayList,
            'adjustmentsToday' => $adjustmentsToday,
            'adjEntryCash' => $adjEntryCash,
            'adjExitCash' => $adjExitCash,
        ]);

        return $pdf->stream('Cierre_de_Caja_' . $today->format('Y-m-d') . '.pdf');
    }
}
