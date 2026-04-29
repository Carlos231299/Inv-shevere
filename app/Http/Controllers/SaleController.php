<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movement;
use App\Models\Product;
use App\Models\Client;
use App\Models\Credit;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index()
    {
        return view('sales.index');
    }

    private function sanitizeCurrency($value)
    {
        if (is_string($value)) {
            $value = str_replace('.', '', $value); // Remove thousands
            $value = str_replace(',', '.', $value); // Comma to Dot
        }
        return $value;
    }

    public function store(Request $request)
    {
        \Log::info('Sale Store Request:', $request->all());
        // Sanitize
        $input = $request->all();
        // Sanitize Items Prices
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as &$item) {
                if (isset($item['sale_price'])) $item['sale_price'] = $this->sanitizeCurrency($item['sale_price']);
            }
        }
        // Sanitize other money fields
        if (isset($input['received_amount'])) $input['received_amount'] = $this->sanitizeCurrency($input['received_amount']);
        if (isset($input['change_amount'])) $input['change_amount'] = $this->sanitizeCurrency($input['change_amount']);
        if (isset($input['deposit_amount'])) $input['deposit_amount'] = $this->sanitizeCurrency($input['deposit_amount']);
        if (isset($input['discount'])) $input['discount'] = $this->sanitizeCurrency($input['discount']);

        $request->merge($input);

        $request->validate([
            'items' => 'required|array',
            'items.*.sku' => 'required|exists:products,sku',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.sale_price' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,credit,partial',
            'payment_method' => 'nullable|string', 
            'payments' => 'nullable|array',
            'payments.*.method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0',
            'client_id' => 'nullable|exists:clients,id',
            'deposit_amount' => 'nullable|numeric|min:0', 
            'discount' => 'nullable|numeric|min:0',
            'custom_date' => 'nullable|date',
        ]);

        try {
            $saleCreated = null;

            DB::transaction(function () use ($request, &$saleCreated) {
                // Determine Date
                $createdAt = $request->custom_date ? \Carbon\Carbon::parse($request->custom_date) : now();

                // 3. Create Sale Record (Initial)
                // We CLEAN any existing payment records for this specific ID in case of DB resets
                // (This prevents "ghost" payments if IDs are being reused)
                $nextId = (DB::table('sales')->max('id') ?? 0) + 1;
                DB::table('sale_payments')->where('sale_id', $nextId)->delete();
                DB::table('credits')->where('sale_id', $nextId)->delete();

                $sale = \App\Models\Sale::create([
                    'client_id' => $request->client_id,
                    'user_id' => auth()->id() ?? 1,
                    'total_amount' => 0, // Will update after items
                    'discount' => $request->discount ?? 0,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'received_amount' => $request->received_amount ?? 0,
                    'change_amount' => $request->change_amount ?? 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt
                ]);

                $grandTotal = 0;
                $items = $request->input('items'); // Get sanitized items

                foreach ($items as $item) {
                    $product = Product::find($item['sku']); 
                    
                    if (!$product) {
                        throw new \Exception("Producto con SKU {$item['sku']} no encontrado.");
                    }

                    // 3. Update Stock using FIFO Batches & Create Movements
                    $remainingToDeduct = $item['quantity'];
                    $batches = \App\Models\Batch::where('product_sku', $item['sku'])
                        ->where('status', 'active')
                        ->where('current_quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    $itemGrandTotal = 0;
                    
                    // User Custom Price (Sanitized)
                    $customSalePrice = $item['sale_price'];

                    foreach ($batches as $batch) {
                        if ($remainingToDeduct <= 0) break;

                        $deduct = min($remainingToDeduct, $batch->current_quantity);
                        $batch->current_quantity -= $deduct;
                        
                        if ($batch->current_quantity <= 0) {
                            $batch->status = 'exhausted';
                        }
                        $batch->save();

                        // Create Movement per batch portion
                        // USE CUSTOM SALE PRICE HERE, NOT BATCH PRICE
                        $portionTotal = $deduct * $customSalePrice;
                        $itemGrandTotal += $portionTotal;

                        Movement::create([
                            'type' => 'sale',
                            'product_sku' => $item['sku'], 
                            'batch_identifier' => $batch->batch_number,
                            'quantity' => $deduct,
                            'price_at_moment' => $customSalePrice, // Override
                            'cost_at_moment' => $batch->cost_price, // Margin analysis needs real cost

                            'total' => $portionTotal,
                            'user_id' => 1, 
                            'client_id' => $request->client_id,
                            'payment_method' => $request->payment_method,
                            'sale_id' => $sale->id, 
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt
                        ]);

                        $remainingToDeduct -= $deduct;
                    }

                    if ($remainingToDeduct > 0) {
                        throw new \Exception("Stock insuficiente en lotes para {$product->name}. Faltan {$remainingToDeduct} unidades.");
                    }

                    $grandTotal += $itemGrandTotal;
                    $product->stock -= $item['quantity'];
                    $product->save();
                }

                // Apply Discount
                $finalTotal = max(0, $grandTotal - ($request->discount ?? 0));

                // Update Sale Total
                $sale->update(['total_amount' => $finalTotal]);

                // 4. Handle Payments (Split or Single)
                $status = $request->payment_status;
                $payments = $request->input('payments', []);

                // Backward Compatibility: If no 'payments' array, construct it from legacy fields
                if (empty($payments) && $request->payment_method) {
                    $amountToPay = ($status === 'partial') ? ($request->deposit_amount ?? 0) : $finalTotal;
                    // If paid, default to total. If credit with 0 deposit, amount is 0.
                    if ($status === 'credit') $amountToPay = 0;
                    
                    if ($amountToPay > 0) {
                        $payments[] = [
                            'method' => $request->payment_method,
                            'amount' => $amountToPay
                        ];
                    }
                }

                if ($status === 'credit' || $status === 'partial') {
                    if (!$request->client_id) {
                         throw new \Exception("Para ventas a crédito o pagos parciales, debe seleccionar un cliente.");
                    }

                    $deposit = 0;
                    foreach($payments as $p) $deposit += $p['amount'];
                    
                    // Create Credit Record
                    $credit = \App\Models\Credit::create([
                        'client_id' => $request->client_id,
                        'movement_id' => null, 
                        'sale_id' => $sale->id,
                        'total_debt' => $finalTotal, 
                        'paid_amount' => $deposit,   
                        'status' => 'pending',       
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt
                    ]);

                    // REGISTRAR ABONOS (CreditPayments)
                    foreach ($payments as $payment) {
                        if ($payment['amount'] > 0) {
                            \App\Models\CreditPayment::create([
                                'credit_id' => $credit->id,
                                'amount' => $payment['amount'],
                                'payment_method' => $payment['method'],
                                'payment_date' => $createdAt,
                                'created_at' => $createdAt,
                                'updated_at' => $createdAt
                            ]);
                        }
                    }
                    
                    // Mark movements as credit/mixed
                    $methodLabel = count($payments) > 1 ? 'mixed' : ($payments[0]['method'] ?? 'credit');
                    $sale->movements()->update(['payment_method' => 'credit']); // Keep explicit credit for movements
                    $sale->update(['payment_method' => $status === 'partial' ? $methodLabel : 'credit']);

                } else {
                    // FULLY PAID
                    foreach ($payments as $payment) {
                        if ($payment['amount'] > 0) {
                            \App\Models\SalePayment::create([
                                'sale_id' => $sale->id,
                                'payment_method' => $payment['method'],
                                'amount' => $payment['amount'],
                                'created_at' => $createdAt,
                                'updated_at' => $createdAt
                            ]);
                        }
                    }
                    
                    $methodLabel = count($payments) > 1 ? 'mixed' : ($payments[0]['method'] ?? 'cash');
                    // Update movements to reflect the main method or mixed
                    // (Though ReportController should now use SalePayment for totals)
                    $sale->movements()->update(['payment_method' => $methodLabel]);
                    $sale->update(['payment_method' => $methodLabel]);
                }
                
                $saleCreated = $sale;
            });

            return response()->json([
                'message' => 'Venta registrada con éxito',
                'sale_id' => $saleCreated ? $saleCreated->id : null
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400); 
        }
    }

    public function update(Request $request, $id)
    {
        // Sanitize
        $input = $request->all();
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as &$item) {
                if (isset($item['sale_price'])) $item['sale_price'] = $this->sanitizeCurrency($item['sale_price']);
            }
        }
        if (isset($input['received_amount'])) $input['received_amount'] = $this->sanitizeCurrency($input['received_amount']);
        if (isset($input['change_amount'])) $input['change_amount'] = $this->sanitizeCurrency($input['change_amount']);
        if (isset($input['deposit_amount'])) $input['deposit_amount'] = $this->sanitizeCurrency($input['deposit_amount']);
        if (isset($input['discount'])) $input['discount'] = $this->sanitizeCurrency($input['discount']);

        $request->merge($input);

        $request->validate([
            'items' => 'required|array',
            'items.*.sku' => 'required|exists:products,sku',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.sale_price' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,credit,partial',
            'payment_method' => 'nullable|string', 
            'client_id' => 'nullable|exists:clients,id',
            'deposit_amount' => 'nullable|numeric|min:0', 
            'discount' => 'nullable|numeric|min:0',
            'custom_date' => 'nullable|date',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $sale = \App\Models\Sale::with('movements')->findOrFail($id);

                // 1. ROLLBACK Stock & Batches
                foreach ($sale->movements as $movement) {
                    $product = Product::find($movement->product_sku);
                    if ($product) {
                        $product->stock += $movement->quantity;
                        $product->save();
                    }

                    if ($movement->batch_identifier) {
                        $batch = \App\Models\Batch::where('product_sku', $movement->product_sku)
                            ->where('batch_number', $movement->batch_identifier)
                            ->first();
                        if ($batch) {
                            $batch->current_quantity += $movement->quantity;
                            if ($batch->current_quantity > 0 && $batch->status == 'exhausted') {
                                $batch->status = 'active';
                            }
                            $batch->save();
                        }
                    }
                }

                // 2. DELETE Related Records
                if ($sale->credit) {
                    $sale->credit->payments()->delete();
                    $sale->credit->delete();
                }
                $sale->movements()->delete();

                // 3. APPLY NEW DATA
                $createdAt = $request->custom_date ? \Carbon\Carbon::parse($request->custom_date) : $sale->created_at;

                // Delete old payments (SalePayment) - CreditPayments are handled by deleting credit above
                \App\Models\SalePayment::where('sale_id', $sale->id)->delete();

                $payments = $request->input('payments', []);
                $status = $request->payment_status;

                // Backward Compatibility / Reconstruction
                if (empty($payments) && $request->payment_method) {
                    // Recalculate total for amount logic if needed, but we do it later. 
                    // Wait, we need total first? No, we can form the structure.
                    $payments = [['method' => $request->payment_method, 'amount' => 0]]; // Amount updated later or we assume logic
                    // Actually, for update, we should rely on the input structure. 
                    // If UI sends legacy, we need to handle it.
                    // Assuming UI will be updated to send 'payments'.
                    // PRO TIP: validation requires 'payments' array if we enforce it. 
                    // But we made it nullable.
                }

                $sale->update([
                    'client_id' => $request->client_id,
                    'total_amount' => 0, 
                    'discount' => $request->discount ?? 0,
                    'received_amount' => $request->received_amount ?? 0,
                    'change_amount' => $request->change_amount ?? 0,
                    'payment_method' => 'pending', // Will update below
                    'created_at' => $createdAt,
                    'updated_at' => now()
                ]);

                $grandTotal = 0;
                foreach ($request->input('items') as $item) {
                     $product = Product::find($item['sku']); 
                    $remainingToDeduct = $item['quantity'];
                    $batches = \App\Models\Batch::where('product_sku', $item['sku'])
                        ->where('status', 'active')
                        ->where('current_quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    $itemGrandTotal = 0;
                    $customSalePrice = $item['sale_price'];

                    foreach ($batches as $batch) {
                        if ($remainingToDeduct <= 0) break;
                        $deduct = min($remainingToDeduct, $batch->current_quantity);
                        $batch->current_quantity -= $deduct;
                        if ($batch->current_quantity <= 0) $batch->status = 'exhausted';
                        $batch->save();

                        $portionTotal = $deduct * $customSalePrice;
                        $itemGrandTotal += $portionTotal;

                        Movement::create([
                            'type' => 'sale',
                            'product_sku' => $item['sku'], 
                            'batch_identifier' => $batch->batch_number,
                            'quantity' => $deduct,
                            'price_at_moment' => $customSalePrice,
                            'cost_at_moment' => $batch->cost_price,
                            'total' => $portionTotal,
                            'user_id' => 1, 
                            'client_id' => $request->client_id,
                            'payment_method' => 'pending', // Will update
                            'sale_id' => $sale->id, 
                            'created_at' => $createdAt,
                            'updated_at' => now()
                        ]);
                        $remainingToDeduct -= $deduct;
                    }

                    if ($remainingToDeduct > 0) {
                        throw new \Exception("Stock insuficiente en lotes para {$product->name}. Faltan {$remainingToDeduct} unidades.");
                    }

                    $grandTotal += $itemGrandTotal;
                    $product->stock -= $item['quantity'];
                    $product->save();
                }

                $finalTotal = max(0, $grandTotal - ($request->discount ?? 0));
                $sale->update(['total_amount' => $finalTotal]);

                // Handle Input Legacy Fallback for Amount
                if (empty($request->input('payments', [])) && $request->payment_method) {
                     $amountToPay = ($status === 'partial') ? ($request->deposit_amount ?? 0) : $finalTotal;
                     if ($status === 'credit') $amountToPay = 0;
                     if ($amountToPay > 0) {
                        $payments = [['method' => $request->payment_method, 'amount' => $amountToPay]];
                     } else {
                        $payments = [];
                     }
                }

                // 4. Handle Payments & Credits
                if ($status === 'credit' || $status === 'partial') {
                    $deposit = 0;
                    foreach($payments as $p) $deposit += $p['amount'];

                    $credit = \App\Models\Credit::create([
                        'client_id' => $request->client_id,
                        'sale_id' => $sale->id,
                        'total_debt' => $finalTotal, 
                        'paid_amount' => $deposit,   
                        'status' => 'pending',       
                        'created_at' => $createdAt,
                        'updated_at' => now()
                    ]);

                    foreach ($payments as $payment) {
                        if ($payment['amount'] > 0) {
                            \App\Models\CreditPayment::create([
                                'credit_id' => $credit->id,
                                'amount' => $payment['amount'],
                                'payment_method' => $payment['method'],
                                'payment_date' => $createdAt,
                                'created_at' => $createdAt,
                                'updated_at' => now()
                            ]);
                        }
                    }
                    
                    $methodLabel = count($payments) > 1 ? 'mixed' : ($payments[0]['method'] ?? 'credit');
                    $sale->movements()->update(['payment_method' => 'credit']);
                    $sale->update(['payment_method' => $status === 'partial' ? $methodLabel : 'credit']);

                } else {
                    // Fully Paid
                    foreach ($payments as $payment) {
                        if ($payment['amount'] > 0) {
                            \App\Models\SalePayment::create([
                                'sale_id' => $sale->id,
                                'payment_method' => $payment['method'],
                                'amount' => $payment['amount'],
                                'created_at' => $createdAt,
                                'updated_at' => now()
                            ]);
                        }
                    }

                    $methodLabel = count($payments) > 1 ? 'mixed' : ($payments[0]['method'] ?? 'cash');
                    $sale->movements()->update(['payment_method' => $methodLabel]);
                    $sale->update(['payment_method' => $methodLabel]);
                }
            });

            return response()->json(['message' => 'Venta actualizada con éxito', 'sale_id' => $id]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400); 
        }
    }

    public function show($id)
    {
        $sale = \App\Models\Sale::with(['movements.product', 'client', 'credit.payments'])->findOrFail($id);
        if (request()->wantsJson()) {
            return response()->json($sale);
        }
        return view('sales.ticket', compact('sale')); // Fallback to ticket if not JSON
    }

    public function ticket($id)
    {
        $sale = \App\Models\Sale::with(['movements.product', 'client', 'salePayments', 'credit.payments'])->findOrFail($id);
        
        // Use 'total_amount' if set, else sum movements
        $sale->total = $sale->total_amount; 

        return view('sales.ticket', compact('sale'));
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $sale = \App\Models\Sale::with('movements')->findOrFail($id);

                foreach ($sale->movements as $movement) {
                    $product = Product::find($movement->product_sku);
                    if ($product) {
                        $product->stock += $movement->quantity;
                        $product->save();
                    }

                    if ($movement->batch_identifier) {
                        $batch = \App\Models\Batch::where('product_sku', $movement->product_sku)
                            ->where('batch_number', $movement->batch_identifier)
                            ->first();
                        if ($batch) {
                            $batch->current_quantity += $movement->quantity;
                            if ($batch->current_quantity > 0 && $batch->status == 'exhausted') {
                                $batch->status = 'active';
                            }
                            $batch->save();
                        }
                    }
                }

                // Delete related credits and payments
                if ($sale->credit) {
                    $sale->credit->payments()->delete();
                    $sale->credit->delete();
                }
                $sale->salePayments()->delete(); // Add this line

                $sale->movements()->delete();
                $sale->delete();
            });

            return response()->json(['message' => 'Venta eliminada y stock restaurado.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }

    public function getNextId()
    {
        $lastId = \App\Models\Sale::max('id') ?? 0;
        return response()->json(['next_id' => $lastId + 1]);
    }
}
