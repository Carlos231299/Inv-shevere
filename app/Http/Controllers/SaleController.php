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
        // Verificar si hay caja abierta
        $activeRegister = \App\Models\CashRegister::where('status', 'open')->first();
        if (!$activeRegister) {
            return response()->json(['message' => 'No se puede registrar la venta. Debe abrir la caja primero desde el Panel de Control.'], 400);
        }

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

                // Pre-calculate payment method label BEFORE creating movements
                // so movements get the correct method from the start
                $status   = $request->payment_status;
                $payments = $request->input('payments', []);

                // If no payments array yet, build it from legacy fields
                // (we do a partial build now; amount gets corrected after total is known)
                $paymentsForLabel = $payments;
                if (empty($paymentsForLabel) && $request->payment_method) {
                    $paymentsForLabel = [['method' => $request->payment_method, 'amount' => 0]];
                }

                $methodLabelForMovements = match(true) {
                    $status === 'credit'              => 'credit',
                    $status === 'partial' && count($paymentsForLabel) > 1 => 'mixed',
                    $status === 'partial'             => $paymentsForLabel[0]['method'] ?? 'credit',
                    count($paymentsForLabel) > 1     => 'mixed',
                    default                          => $paymentsForLabel[0]['method'] ?? ($request->payment_method ?? 'cash'),
                };

                // Clean ghost records
                $nextId = (DB::table('sales')->max('id') ?? 0) + 1;
                DB::table('sale_payments')->where('sale_id', $nextId)->delete();
                DB::table('credits')->where('sale_id', $nextId)->delete();

                $sale = \App\Models\Sale::create([
                    'client_id'       => $request->client_id,
                    'user_id'         => auth()->id() ?? 1,
                    'total_amount'    => 0,
                    'discount'        => $request->discount ?? 0,
                    'payment_method'  => $methodLabelForMovements,
                    'received_amount' => $request->received_amount ?? 0,
                    'change_amount'   => $request->change_amount ?? 0,
                    'created_at'      => $createdAt,
                    'updated_at'      => $createdAt
                ]);

                $grandTotal = 0;
                $items = $request->input('items');

                foreach ($items as $item) {
                    $product = Product::find($item['sku']);
                    if (!$product) {
                        throw new \Exception("Producto con SKU {$item['sku']} no encontrado.");
                    }

                    $remainingToDeduct = $item['quantity'];
                    $batches = \App\Models\Batch::where('product_sku', $item['sku'])
                        ->where('status', 'active')
                        ->where('current_quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    $itemGrandTotal  = 0;
                    $customSalePrice = $item['sale_price'];

                    foreach ($batches as $batch) {
                        if ($remainingToDeduct <= 0) break;

                        $deduct = min($remainingToDeduct, $batch->current_quantity);
                        $batch->current_quantity -= $deduct;
                        if ($batch->current_quantity <= 0) $batch->status = 'exhausted';
                        $batch->save();

                        $portionTotal    = $deduct * $customSalePrice;
                        $itemGrandTotal += $portionTotal;

                        // ✅ Movements now always get the CORRECT payment_method
                        Movement::create([
                            'type'              => 'sale',
                            'product_sku'       => $item['sku'],
                            'batch_identifier'  => $batch->batch_number,
                            'quantity'          => $deduct,
                            'price_at_moment'   => $customSalePrice,
                            'cost_at_moment'    => $batch->cost_price,
                            'total'             => $portionTotal,
                            'user_id'           => auth()->id() ?? 1,
                            'client_id'         => $request->client_id,
                            'payment_method'    => $methodLabelForMovements,
                            'sale_id'           => $sale->id,
                            'created_at'        => $createdAt,
                            'updated_at'        => $createdAt
                        ]);

                        $remainingToDeduct -= $deduct;
                    }

                    if ($remainingToDeduct > 0) {
                        throw new \Exception("Stock insuficiente en lotes para {$product->name}. Faltan {$remainingToDeduct} unidades.");
                    }

                    $grandTotal     += $itemGrandTotal;
                    $product->stock -= $item['quantity'];
                    $product->save();
                }

                $finalTotal = max(0, $grandTotal - ($request->discount ?? 0));
                $sale->update(['total_amount' => $finalTotal]);

                // Rebuild payments array now that we have the real total
                if (empty($payments) && $request->payment_method) {
                    $amountToPay = ($status === 'partial') ? ($request->deposit_amount ?? 0) : $finalTotal;
                    if ($status === 'credit') $amountToPay = 0;
                    $payments = $amountToPay > 0
                        ? [['method' => $request->payment_method, 'amount' => $amountToPay]]
                        : [];
                }

                if ($status === 'credit' || $status === 'partial') {
                    if (!$request->client_id) {
                        throw new \Exception("Para ventas a crédito o pagos parciales, debe seleccionar un cliente.");
                    }

                    $deposit = 0;
                    foreach ($payments as $p) $deposit += $p['amount'];

                    $credit = \App\Models\Credit::create([
                        'client_id'  => $request->client_id,
                        'movement_id'=> null,
                        'sale_id'    => $sale->id,
                        'total_debt' => $finalTotal,
                        'paid_amount'=> $deposit,
                        'status'     => 'pending',
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt
                    ]);

                    foreach ($payments as $payment) {
                        if ($payment['amount'] > 0) {
                            \App\Models\CreditPayment::create([
                                'credit_id'      => $credit->id,
                                'amount'         => $payment['amount'],
                                'payment_method' => $payment['method'],
                                'payment_date'   => $createdAt,
                                'created_at'     => $createdAt,
                                'updated_at'     => $createdAt
                            ]);
                        }
                    }

                    // Movements already have the correct method; just ensure credit label on movement
                    $sale->movements()->update(['payment_method' => 'credit']);
                    $sale->update(['payment_method' => $status === 'partial' ? $methodLabelForMovements : 'credit']);

                } else {
                    // FULLY PAID — create SalePayment per method
                    foreach ($payments as $payment) {
                        if ($payment['amount'] > 0) {
                            \App\Models\SalePayment::create([
                                'sale_id'        => $sale->id,
                                'payment_method' => $payment['method'],
                                'amount'         => $payment['amount'],
                                'created_at'     => $createdAt,
                                'updated_at'     => $createdAt
                            ]);
                        }
                    }
                    // Movements already have methodLabelForMovements — no extra update needed
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

                // Delete old payments (SalePayment) - CreditPayments handled by deleting credit above
                \App\Models\SalePayment::where('sale_id', $sale->id)->delete();

                $payments = $request->input('payments', []);
                $status   = $request->payment_status;

                // Build payments for label calculation (legacy fallback, amount unknown yet)
                $paymentsForLabel = $payments;
                if (empty($paymentsForLabel) && $request->payment_method) {
                    $paymentsForLabel = [['method' => $request->payment_method, 'amount' => 0]];
                }

                // ✅ Pre-calculate correct method label BEFORE creating movements
                $methodLabelForMovements = match(true) {
                    $status === 'credit'                                    => 'credit',
                    $status === 'partial' && count($paymentsForLabel) > 1  => 'mixed',
                    $status === 'partial'                                   => $paymentsForLabel[0]['method'] ?? 'credit',
                    count($paymentsForLabel) > 1                           => 'mixed',
                    default                                                => $paymentsForLabel[0]['method'] ?? ($request->payment_method ?? 'cash'),
                };

                $sale->update([
                    'client_id'      => $request->client_id,
                    'total_amount'   => 0,
                    'discount'       => $request->discount ?? 0,
                    'received_amount'=> $request->received_amount ?? 0,
                    'change_amount'  => $request->change_amount ?? 0,
                    'payment_method' => $methodLabelForMovements,
                    'created_at'     => $createdAt,
                    'updated_at'     => now()
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

                    $itemGrandTotal  = 0;
                    $customSalePrice = $item['sale_price'];

                    foreach ($batches as $batch) {
                        if ($remainingToDeduct <= 0) break;
                        $deduct = min($remainingToDeduct, $batch->current_quantity);
                        $batch->current_quantity -= $deduct;
                        if ($batch->current_quantity <= 0) $batch->status = 'exhausted';
                        $batch->save();

                        $portionTotal    = $deduct * $customSalePrice;
                        $itemGrandTotal += $portionTotal;

                        // ✅ Movements get the CORRECT payment_method immediately
                        Movement::create([
                            'type'             => 'sale',
                            'product_sku'      => $item['sku'],
                            'batch_identifier' => $batch->batch_number,
                            'quantity'         => $deduct,
                            'price_at_moment'  => $customSalePrice,
                            'cost_at_moment'   => $batch->cost_price,
                            'total'            => $portionTotal,
                            'user_id'          => auth()->id() ?? 1,
                            'client_id'        => $request->client_id,
                            'payment_method'   => $methodLabelForMovements,
                            'sale_id'          => $sale->id,
                            'created_at'       => $createdAt,
                            'updated_at'       => now()
                        ]);
                        $remainingToDeduct -= $deduct;
                    }

                    if ($remainingToDeduct > 0) {
                        throw new \Exception("Stock insuficiente en lotes para {$product->name}. Faltan {$remainingToDeduct} unidades.");
                    }

                    $grandTotal     += $itemGrandTotal;
                    $product->stock -= $item['quantity'];
                    $product->save();
                }

                $finalTotal = max(0, $grandTotal - ($request->discount ?? 0));
                $sale->update(['total_amount' => $finalTotal]);

                // Rebuild payments array with real amounts now that total is known
                if (empty($request->input('payments', [])) && $request->payment_method) {
                    $amountToPay = ($status === 'partial') ? ($request->deposit_amount ?? 0) : $finalTotal;
                    if ($status === 'credit') $amountToPay = 0;
                    $payments = $amountToPay > 0
                        ? [['method' => $request->payment_method, 'amount' => $amountToPay]]
                        : [];
                }

                // 4. Handle Payments & Credits
                if ($status === 'credit' || $status === 'partial') {
                    $deposit = 0;
                    foreach ($payments as $p) $deposit += $p['amount'];

                    $credit = \App\Models\Credit::create([
                        'client_id'  => $request->client_id,
                        'sale_id'    => $sale->id,
                        'total_debt' => $finalTotal,
                        'paid_amount'=> $deposit,
                        'status'     => 'pending',
                        'created_at' => $createdAt,
                        'updated_at' => now()
                    ]);

                    foreach ($payments as $payment) {
                        if ($payment['amount'] > 0) {
                            \App\Models\CreditPayment::create([
                                'credit_id'      => $credit->id,
                                'amount'         => $payment['amount'],
                                'payment_method' => $payment['method'],
                                'payment_date'   => $createdAt,
                                'created_at'     => $createdAt,
                                'updated_at'     => now()
                            ]);
                        }
                    }

                    // Force 'credit' on movements for credit sales
                    $sale->movements()->update(['payment_method' => 'credit']);
                    $sale->update(['payment_method' => $status === 'partial' ? $methodLabelForMovements : 'credit']);

                } else {
                    // Fully Paid — create one SalePayment per method
                    foreach ($payments as $payment) {
                        if ($payment['amount'] > 0) {
                            \App\Models\SalePayment::create([
                                'sale_id'        => $sale->id,
                                'payment_method' => $payment['method'],
                                'amount'         => $payment['amount'],
                                'created_at'     => $createdAt,
                                'updated_at'     => now()
                            ]);
                        }
                    }
                    // Movements already have the correct method — no extra update needed
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
