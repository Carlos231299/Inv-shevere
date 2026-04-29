<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Movement;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    public function adjust(Request $request)
    {
        $request->validate([
            'sku' => 'required|exists:products,sku',
            'real_stock' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255'
        ]);

        $sku = $request->input('sku');
        $realStock = (float) $request->input('real_stock');
        $notes = $request->input('notes', 'Ajuste de Inventario');

        try {
            DB::beginTransaction();

            $product = Product::lockForUpdate()->find($sku);
            $currentStock = (float) $product->stock;
            $diff = $realStock - $currentStock;

            if (abs($diff) < 0.001) {
                return response()->json(['message' => 'El stock ya está actualizado.'], 200);
            }

            // 1. Create Movement (Inventory Correction)
            // We use 'inventory_correction' type. Ensure migration for 'type' string is run.
            $movement = Movement::create([
                'type' => 'inventory_correction',
                'product_sku' => $sku,
                'quantity' => abs($diff),
                'price_at_moment' => 0, // No financial impact
                'cost_at_moment' => $product->cost_price, // Track cost for internal ref
                'total' => 0, // No financial impact
                'user_id' => auth()->id(),
                'payment_method' => 'adjustment', // Dummy
                'batch_identifier' => $diff > 0 ? 'ADJ-IN-' . time() : 'ADJ-OUT-' . time(),
                'description' => $notes // Assuming we might need description column or put in batch_identifier
                // Note: 'description' column might not exist in movements, check migration. 
                // Using 'payment_method' to store 'adjustment' is fine.
            ]);

            // 2. Update Product Stock
            $product->stock = $realStock;
            $product->save();

            // 3. Update Batches
            if ($diff > 0) {
                // INCREASE STOCK -> Create Batch
                Batch::create([
                    'product_sku' => $sku,
                    'batch_number' => 'ADJ-' . date('ymd-His'),
                    'initial_quantity' => $diff,
                    'current_quantity' => $diff,
                    'purchase_id' => null,
                    'cost_price' => $product->cost_price,
                    'sale_price' => $product->sale_price,
                    'status' => 'active'
                ]);
            } else {
                // DECREASE STOCK -> Consume Batches (FIFO)
                $qtyToDeduct = abs($diff);
                $batches = Batch::where('product_sku', $sku)
                    ->where('current_quantity', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($batches as $batch) {
                    if ($qtyToDeduct <= 0) break;

                    $deduct = min($qtyToDeduct, $batch->current_quantity);
                    $batch->current_quantity -= $deduct;
                    $qtyToDeduct -= $deduct;

                    if ($batch->current_quantity <= 0) {
                        $batch->status = 'exhausted';
                    }
                    $batch->save();
                }

                // If still quantity to deduct (Ghost Stock case), we ignore it as we already forced product stock down.
                // The discrepancy is effectively resolved by the Product update.
            }

            DB::commit();

            return response()->json(['message' => 'Inventario ajustado correctamente. Nuevo Stock: ' . $realStock]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Inventory Adjustment Error: " . $e->getMessage());
            return response()->json(['message' => 'Error al ajustar inventario: ' . $e->getMessage()], 500);
        }
    }
}
