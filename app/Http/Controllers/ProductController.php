<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
        }

        $perPage = $request->get('per_page', 10);
        $products = $query->paginate($perPage);

        if ($request->ajax()) {
            return view('products.partials.table_rows', compact('products'))->render();
        }

        return view('products.index', compact('products'));
    }

    public function show($sku)
    {
        $product = Product::with(['movements' => function($q) {
            $q->where('type', 'purchase')->orderBy('created_at', 'desc');
        }])->findOrFail($sku);

        // Also get batches for detailed lote view
        $batches = \App\Models\Batch::where('product_sku', $sku)
            ->where('current_quantity', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        if (request()->ajax()) {
            return view('products.show', compact('product', 'batches'));
        }
        return view('products.show', compact('product', 'batches'));
    }

    public function create()
    {
        if (request()->ajax()) {
            return view('products.form');
        }
        return view('products.create');
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
        // Sanitize
        $input = $request->all();
        if (isset($input['cost_price'])) $input['cost_price'] = $this->sanitizeCurrency($input['cost_price']);
        if (isset($input['sale_price'])) $input['sale_price'] = $this->sanitizeCurrency($input['sale_price']);
        if (isset($input['min_stock'])) $input['min_stock'] = $this->sanitizeCurrency($input['min_stock']);
        
        $request->merge($input);

        $request->validate([
            'sku' => 'required|unique:products,sku',
            'name' => 'required',
            'measure_type' => 'required|in:kg,unit',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'sku' => $request->sku,
            'name' => $request->name,
            'measure_type' => $request->measure_type,
            'cost_price' => $request->cost_price,
            'sale_price' => $request->sale_price,
            'average_sale_price' => 0, // Always 0 for new products
            'min_stock' => $request->min_stock,
            'stock' => 0, 
            'status' => 'active'
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Producto creado exitosamente.', 'product' => $product], 200);
        }

        return redirect()->route('products.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit($sku)
    {
        $product = Product::findOrFail($sku);
        if (request()->ajax()) {
            return view('products.form', compact('product'));
        }
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, $sku)
    {
        $product = Product::findOrFail($sku);

        // Sanitize
        $input = $request->all();
        if (isset($input['cost_price'])) $input['cost_price'] = $this->sanitizeCurrency($input['cost_price']);
        if (isset($input['sale_price'])) $input['sale_price'] = $this->sanitizeCurrency($input['sale_price']);
        if (isset($input['min_stock'])) $input['min_stock'] = $this->sanitizeCurrency($input['min_stock']);

        $request->merge($input);

        $request->validate([
            'sku' => 'required|unique:products,sku,' . $sku . ',sku',
            'name' => 'required',
            'measure_type' => 'required|in:kg,unit',
            'cost_price' => 'required|numeric|min:0',
            // 'average_sale_price' => 'required|numeric|min:0', // Calculated automatically
            'sale_price' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive'
        ]);

        $product->update($request->all());

        if ($request->ajax()) {
            return response()->json([
                'message' => 'Producto actualizado correctamente.', 
                'product' => $product,
                'new_sku' => $product->sku // For frontend redirection if SKU changed
            ], 200);
        }

        return redirect()->route('products.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy($sku)
    {
        $product = Product::findOrFail($sku);
        // Optional: Check for movements before deleting
        if($product->movements()->exists()){
             return redirect()->route('products.index')->with('error', 'No se puede eliminar el producto porque tiene movimientos registrados. Desactivalo en su lugar.');
        }
        
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Producto eliminado.');
    }

    // API Methods
    public function apiSearch($sku)
    {
        $product = Product::find($sku);
        if ($product) {
            return response()->json($product);
        }
        return response()->json(['message' => 'No encontrado'], 404);
    }

    public function apiStore(Request $request)
    {
        // Sanitize
        $input = $request->all();
        if (isset($input['cost_price'])) $input['cost_price'] = $this->sanitizeCurrency($input['cost_price']);
        if (isset($input['sale_price'])) $input['sale_price'] = $this->sanitizeCurrency($input['sale_price']);
        if (isset($input['min_stock'])) $input['min_stock'] = $this->sanitizeCurrency($input['min_stock']);

        $request->merge($input);

        $request->validate([
            'sku' => 'required|unique:products,sku',
            'name' => 'required',
            'measure_type' => 'required|in:kg,unit',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'sku' => $request->sku,
            'name' => $request->name,
            'measure_type' => $request->measure_type,
            'cost_price' => $request->cost_price,
            'sale_price' => $request->sale_price,
            'min_stock' => $request->min_stock,
            'stock' => 0,
            'status' => 'active'
        ]);

        return response()->json($product, 201);
    }

    public function apiIndex()
    {
        try {
            $products = Product::select('sku', 'name', 'stock', 'cost_price', 'sale_price', 'average_sale_price', 'measure_type')
                ->get();

            return response()->json($products);

        } catch (\Exception $e) {
            \Log::error("Error in apiIndex: " . $e->getMessage());
            return response()->json(Product::all()); 
        }
    }
}
