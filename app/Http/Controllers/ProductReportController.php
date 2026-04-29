<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movement;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductReportController extends Controller
{
    public function index(Request $request)
    {
        // Default: Current Month
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfMonth()->format('Y-m-d') : Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfMonth()->format('Y-m-d') : Carbon::now()->endOfMonth()->format('Y-m-d');

        // Query Movements directly for aggregation
        $reportData = Movement::where('type', 'sale')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->select(
                'product_sku',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('AVG(price_at_moment) as avg_price')
            )
            ->groupBy('product_sku')
            ->orderBy('total_quantity', 'desc')
            ->get();

        // Get Product Details manually to avoid N+1 issues in loop or complex joins
        $skus = $reportData->pluck('product_sku');
        $products = Product::whereIn('sku', $skus)->get()->keyBy('sku');

        // Attach product details and calculate profit
        $finalData = $reportData->map(function($item) use ($products) {
            $product = $products->get($item->product_sku);
            $item->product_name = $product ? $product->name : 'Unknown Product';
            $item->measure_type = $product ? $product->measure_type : '';
            // Profit Estimate: Revenue - (Quantity * Current Cost Price)
            // Note: Ideally we should use cost_at_moment, but if not stored, use current cost.
            $cost = $product ? $product->cost_price : 0; 
            $item->estimated_profit = $item->total_revenue - ($item->total_quantity * $cost);
            return $item;
        });

        // Summary Cards
        $totalSales = $finalData->sum('total_revenue');
        $totalProfit = $finalData->sum('estimated_profit');
        $bestSeller = $finalData->sortByDesc('total_quantity')->first();

        return view('reports.products.index', compact(
            'finalData', 
            'startDate', 
            'endDate', 
            'totalSales', 
            'totalProfit', 
            'bestSeller'
        ));
    }
}
