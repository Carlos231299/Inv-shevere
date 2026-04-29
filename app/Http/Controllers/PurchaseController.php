<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('purchases.index');
    }

    private function sanitizeCurrency($value)
    {
        if (is_string($value)) {
            // Eliminar puntos de miles (1.200 -> 1200)
            $value = str_replace('.', '', $value);
            // Reemplazar coma decimal por punto (1200,50 -> 1200.50)
            $value = str_replace(',', '.', $value);
        }
        return $value;
    }

    public function store(Request $request)
    {
        // 1. Sanitize Inputs before Validation
        $input = $request->all();

        // Sanitize Items
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as &$item) {
                if (isset($item['quantity'])) $item['quantity'] = $this->sanitizeCurrency($item['quantity']);
                if (isset($item['cost_price'])) $item['cost_price'] = $this->sanitizeCurrency($item['cost_price']);
                if (isset($item['sale_price'])) $item['sale_price'] = $this->sanitizeCurrency($item['sale_price']);
            }
        }

        // Sanitize Deposit
        if (isset($input['deposit_amount'])) {
            $input['deposit_amount'] = $this->sanitizeCurrency($input['deposit_amount']);
        }

        $request->merge($input); // Update Request with sanitized data

        $request->validate([
            'items' => 'required|array',
            'items.*.sku' => 'required|exists:products,sku',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.cost_price' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,credit,partial',
            // payment_method is nullable if on credit
            'payment_method' => 'nullable|string', 
            'deposit_amount' => 'nullable|numeric',
            'provider_id' => 'nullable|exists:providers,id',
            'custom_date' => 'nullable|date', // Add validation for custom_date
        ]);

        try {
            // Check if in initial inventory mode
            $isInitialMode = \App\Models\Setting::isInitialMode();
            
            \DB::transaction(function () use ($request, $isInitialMode) {
                // Determine Date
                $createdAt = $request->custom_date ? \Carbon\Carbon::parse($request->custom_date) : now();

                // 1. Create Purchase Header
                $purchase = \App\Models\Purchase::create([
                    'user_id' => 1,
                    'provider_id' => $request->provider_id,
                    'total_amount' => 0, // Update later
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Generate Batch Number for this purchase
                $todayDateString = $createdAt->toDateString();
                $purchaseSeq = \App\Models\Purchase::whereDate('created_at', $todayDateString)
                    ->where('id', '<', $purchase->id)
                    ->count();
                
                $batchBase = 'L-00' . $createdAt->format('md');
                $batchNumber = ($purchaseSeq === 0) ? $batchBase : $batchBase . '-' . $purchaseSeq;

                $grandTotal = 0;

                foreach ($request->items as $item) {
                    $lineTotal = $item['quantity'] * $item['cost_price'];
                    $grandTotal += $lineTotal;

                    // Registrar Movimiento linked to Purchase
                    \App\Models\Movement::create([
                        'type' => 'purchase',
                        'product_sku' => $item['sku'],
                        'quantity' => $item['quantity'],
                        'price_at_moment' => $item['cost_price'],
                        'cost_at_moment' => $item['cost_price'],
                        'total' => $lineTotal,
                        'user_id' => 1, 
                        'payment_method' => $request->payment_method,
                        'purchase_id' => $purchase->id, // Link to Header
                        'is_initial' => $isInitialMode, // Mark as initial if in initial mode
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    // Actualizar Stock y Precios (Lógica de 3 columnas)
                    $product = \App\Models\Product::find($item['sku']);
                    $currentStock = $product->stock;
                    $newQuantity = (float)$item['quantity'];
                    $newCostPrice = (float)$item['cost_price'];
                    $totalStock = $currentStock + $newQuantity;


                    // 1. Promedio Ponderado de COSTO
                    $currentCost = (float)($product->cost_price ?? 0);

                    // REGLA: Si existe costo actual, promediamos. Si no, tomamos el nuevo precio.
                    if ($currentCost > 0) {
                       if ($totalStock > 0) {
                            // Formula: (StockActual * CostoActual + CantidadNueva * CostoNuevo) / (Antiguo Stock + Nueva Cantidad)
                            // Nota: ($currentStock + $newQuantity) es igual a $totalStock calculado arriba
                            $newAvg = (($currentStock * $currentCost) + ($newQuantity * $newCostPrice)) / $totalStock;
                            $product->average_sale_price = $newAvg;
                       }
                    } else {
                        // Si no hay costo previo, el promedio es el nuevo costo.
                        $product->average_sale_price = $newCostPrice;
                    }

                    // 2. Último Precio de Compra
                    $product->cost_price = $newCostPrice;

                    // 3. Precio de Venta Actual (Manual)
                    if (isset($item['sale_price'])) {
                        $product->sale_price = $item['sale_price'];
                    }

                    $product->stock = $totalStock;
                    $product->save();

                    // Create New Batch (Para auditoría y la acción "Ver")
                    \App\Models\Batch::create([
                        'product_sku' => $item['sku'],
                        'batch_number' => (!empty($item['batch_number'])) ? $item['batch_number'] : $batchNumber,
                        'purchase_id' => $purchase->id,
                        'initial_quantity' => $item['quantity'],
                        'current_quantity' => $item['quantity'],
                        'cost_price' => $item['cost_price'],
                        'sale_price' => $item['sale_price'] ?? $product->sale_price,
                        'status' => 'active',
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                $purchase->update(['total_amount' => $grandTotal]);

                // --- Handle Accounts Payable (Credits) ---
                $status = $request->input('payment_status', 'paid');
                if ($status === 'credit' || $status === 'partial') {
                    if (!$request->provider_id) {
                         throw new \Exception("Para crédito se requiere un proveedor registrado.");
                    }

                    $deposit = ($status === 'partial') ? $request->input('deposit_amount', 0) : 0;
                    $debtAmount = $grandTotal - $deposit;

                    if ($debtAmount > 0 || $deposit > 0) {
                        $ap = \App\Models\AccountPayable::create([
                            'provider_id' => $request->provider_id,
                            'amount' => $grandTotal,      // Total Original Debt
                            'paid_amount' => $deposit,    // Initial Payment
                            'due_date' => $createdAt->copy()->addDays(30), // Calculated from custom date
                            'status' => ($grandTotal - $deposit <= 0) ? 'paid' : 'pending',
                            'description' => "Compra #{$purchase->id}",
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt
                        ]);

                        // REGISTRAR ABONO INICIAL PARA CIERRE DE CAJA
                        if ($deposit > 0) {
                            \App\Models\AccountPayablePayment::create([
                                'account_payable_id' => $ap->id,
                                'amount' => $deposit,
                                'payment_method' => $request->payment_method ?? 'cash',
                                'payment_date' => $createdAt,
                                'created_at' => $createdAt,
                                'updated_at' => $createdAt
                            ]);
                        }
                    }

                    // FORCE Movements to 'credit' method so they don't affect DAILY CASH balance (Historical reports only)
                    $purchase->movements()->update(['payment_method' => 'credit']);
                }
        });

            return response()->json(['message' => 'Compra registrada con éxito']);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function show($id)
    {
        $purchase = \App\Models\Purchase::with(['movements.product', 'provider', 'accountPayable.payments'])->findOrFail($id);
        if (request()->wantsJson()) {
            return response()->json($purchase);
        }
        return view('purchases.ticket', compact('purchase'));
    }

    public function ticket($id)
    {
        $purchase = \App\Models\Purchase::with(['movements.product', 'provider'])->findOrFail($id);
        return view('purchases.ticket', compact('purchase'));
    }

    public function update(Request $request, $id)
    {
        // Sanitize
        $input = $request->all();
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as &$item) {
                if (isset($item['cost_price'])) $item['cost_price'] = $this->sanitizeCurrency($item['cost_price']);
                if (isset($item['sale_price'])) $item['sale_price'] = $this->sanitizeCurrency($item['sale_price']);
            }
        }
        if (isset($input['deposit_amount'])) $input['deposit_amount'] = $this->sanitizeCurrency($input['deposit_amount']);

        $request->merge($input);

        $request->validate([
            'items' => 'required|array',
            'items.*.sku' => 'required|exists:products,sku',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.cost_price' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,credit,partial',
            'payment_method' => 'nullable|string', 
            'deposit_amount' => 'nullable|numeric',
            'provider_id' => 'nullable|exists:providers,id',
            'custom_date' => 'nullable|date',
        ]);

        try {
            $isInitialMode = \App\Models\Setting::isInitialMode();
            
            \DB::transaction(function () use ($request, $id, $isInitialMode) {
                $purchase = \App\Models\Purchase::with('movements')->findOrFail($id);

                // 1. ROLLBACK
                foreach ($purchase->movements as $movement) {
                    $product = \App\Models\Product::find($movement->product_sku);
                    if ($product) {
                        $product->stock -= $movement->quantity;
                        $product->save();
                    }
                }
                \App\Models\Batch::where('purchase_id', $purchase->id)->delete();
                $ap = \App\Models\AccountPayable::where('description', "Compra #{$purchase->id}")->first();
                if ($ap) {
                    $ap->payments()->delete();
                    $ap->delete();
                }
                $purchase->movements()->delete();

                // 2. APPLY NEW DATA
                $createdAt = $request->custom_date ? \Carbon\Carbon::parse($request->custom_date) : $purchase->created_at;
                
                $purchase->update([
                    'provider_id' => $request->provider_id,
                    'total_amount' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => now(),
                ]);

                // Batch number generation
                $todayDateString = $createdAt->toDateString();
                $purchaseSeq = \App\Models\Purchase::whereDate('created_at', $todayDateString)
                    ->where('id', '<', $purchase->id)
                    ->count();
                $batchBase = 'L-00' . $createdAt->format('md');
                $batchNumber = ($purchaseSeq === 0) ? $batchBase : $batchBase . '-' . $purchaseSeq;

                $grandTotal = 0;
                foreach ($request->items as $item) {
                    $lineTotal = $item['quantity'] * $item['cost_price'];
                    $grandTotal += $lineTotal;

                    \App\Models\Movement::create([
                        'type' => 'purchase',
                        'product_sku' => $item['sku'],
                        'quantity' => $item['quantity'],
                        'price_at_moment' => $item['cost_price'],
                        'cost_at_moment' => $item['cost_price'],
                        'total' => $lineTotal,
                        'user_id' => 1, 
                        'payment_method' => $request->payment_method,
                        'purchase_id' => $purchase->id,
                        'is_initial' => $isInitialMode,
                        'created_at' => $createdAt,
                        'updated_at' => now(),
                    ]);

                    $product = \App\Models\Product::find($item['sku']);
                    $product->cost_price = (float)$item['cost_price'];
                    if (isset($item['sale_price'])) $product->sale_price = $item['sale_price'];
                    $product->stock += (float)$item['quantity'];
                    $product->save();

                    // MANTENER INTEGRIDAD DE LOTES: 
                    // Si el lote ya existía y tenía ventas, debemos descontarlas de la nueva cantidad inicial.
                    $targetBatchNumber = (!empty($item['batch_number'])) ? $item['batch_number'] : $batchNumber;
                    $alreadySold = \App\Models\Movement::where('batch_identifier', $targetBatchNumber)
                        ->where('type', 'sale')
                        ->sum('quantity');

                    $newCurrentQuantity = max(0, (float)$item['quantity'] - $alreadySold);

                    \App\Models\Batch::create([
                        'product_sku' => $item['sku'],
                        'batch_number' => $targetBatchNumber,
                        'purchase_id' => $purchase->id,
                        'initial_quantity' => $item['quantity'],
                        'current_quantity' => $newCurrentQuantity,
                        'cost_price' => $item['cost_price'],
                        'sale_price' => $item['sale_price'] ?? $product->sale_price,
                        'status' => ($newCurrentQuantity <= 0) ? 'exhausted' : 'active',
                        'created_at' => $createdAt,
                        'updated_at' => now(),
                    ]);
                }

                $purchase->update(['total_amount' => $grandTotal]);

                // 3. Handle AP
                if ($request->payment_status === 'credit' || $request->payment_status === 'partial') {
                    $deposit = ($request->payment_status === 'partial') ? ($request->deposit_amount ?? 0) : 0;
                    $ap = \App\Models\AccountPayable::create([
                        'provider_id' => $request->provider_id,
                        'amount' => $grandTotal,
                        'paid_amount' => $deposit,
                        'due_date' => $createdAt->copy()->addDays(30),
                        'description' => "Compra #{$purchase->id}",
                        'status' => 'pending',
                        'created_at' => $createdAt,
                        'updated_at' => now(),
                    ]);
                    if ($deposit > 0) {
                        $ap->payments()->create([
                            'amount' => $deposit,
                            'payment_method' => $request->payment_method ?? 'cash',
                            'payment_date' => $createdAt,
                            'created_at' => $createdAt,
                            'updated_at' => now(),
                        ]);
                    }
                    $purchase->movements()->update(['payment_method' => 'credit']);
                }
            });
            return response()->json(['message' => 'Compra actualizada con éxito', 'purchase_id' => $id]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400); 
        }
    }

    public function destroy($id)
    {
        try {
            \DB::transaction(function () use ($id) {
                $purchase = \App\Models\Purchase::with('movements')->findOrFail($id);

                foreach ($purchase->movements as $movement) {
                    $product = \App\Models\Product::find($movement->product_sku);
                    if ($product) {
                        $product->stock -= $movement->quantity;
                        $product->save();
                    }
                }

                // Delete related batches
                \App\Models\Batch::where('purchase_id', $purchase->id)->delete();

                // Delete related accounts payable and payments
                $ap = \App\Models\AccountPayable::where('description', "Compra #{$purchase->id}")->first();
                if ($ap) {
                    $ap->payments()->delete();
                    $ap->delete();
                }

                $purchase->movements()->delete();
                $purchase->delete();
            });

            return response()->json(['message' => 'Compra eliminada y stock actualizado.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }
}
