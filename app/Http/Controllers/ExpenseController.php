<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        // Try to identify Trabajadores category by name to be absolutely sure it's excluded
        $workerCategoryIds = \App\Models\ExpenseCategory::where('name', 'like', '%TRABAJADOR%')
            ->pluck('id')
            ->toArray();
        if (empty($workerCategoryIds)) {
            $cat = \App\Models\ExpenseCategory::where('name', 'like', '%TRABAJADOR%')->first();
            $workerCategoryIds = $cat ? [$cat->id] : [3];
        }

        $categories = \App\Models\ExpenseCategory::whereNotIn('id', $workerCategoryIds)->orderBy('name')->get();
        
        $query = Expense::with('category')->whereNotIn('category_id', $workerCategoryIds);

        // 1. Filter by Date Range (Default to current month)
        $startDate = $request->start_date ?? \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? \Carbon\Carbon::parse($startDate)->endOfMonth()->format('Y-m-d');

        $query->whereBetween('expense_date', [
            \Carbon\Carbon::parse($startDate)->startOfDay(),
            \Carbon\Carbon::parse($endDate)->endOfDay()
        ]);

        // 2. Filter by Category (Keep for compatibility, though cards will use it)
        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('category_id', $request->category_id);
        }
        
        // Clone query for aggregation BEFORE pagination
        $aggregationQuery = clone $query;
        $totalFiltered = $aggregationQuery->sum('amount');

        // Calculate Totals per Category for Summary Cards
        $categoryTotals = [];
        foreach ($categories as $cat) {
            $categoryTotals[$cat->id] = Expense::where('category_id', $cat->id)
                ->whereBetween('expense_date', [
                    \Carbon\Carbon::parse($startDate)->startOfDay(),
                    \Carbon\Carbon::parse($endDate)->endOfDay()
                ])
                ->sum('amount');
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(10);

        return view('expenses.index', compact('expenses', 'categories', 'totalFiltered', 'categoryTotals', 'startDate', 'endDate'));
    }

    public function getCategoryDetails(Request $request)
    {
        $categoryId = $request->category_id;
        $category = \App\Models\ExpenseCategory::findOrFail($categoryId);
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $query = Expense::where('category_id', $categoryId);

        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [
                \Carbon\Carbon::parse($startDate)->startOfDay(),
                \Carbon\Carbon::parse($endDate)->endOfDay()
            ]);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        return view('expenses.details_modal', compact('expenses', 'category'));
    }

    public function create()
    {
        $workerCategoryId = \App\Models\ExpenseCategory::where('name', 'like', '%TRABAJADOR%')->first()?->id ?? 3;
        $categories = \App\Models\ExpenseCategory::where('id', '!=', $workerCategoryId)->orderBy('name')->get();
        if (request()->ajax()) {
            return view('expenses.form', compact('categories'));
        }
        return view('expenses.create', compact('categories'));
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
        // Sanitize Amount
        if ($request->has('amount')) {
            $request->merge(['amount' => $this->sanitizeCurrency($request->amount)]);
        }

        $request->validate([
            'description' => 'required',
            'category_id' => 'nullable|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,nequi,bancolombia',
            'expense_date' => 'required|date',
            'worker' => 'nullable|string' // Add validation for worker
        ]);

        $data = $request->all();

        // Append worker to description if present
        if ($request->has('worker') && !empty($request->worker)) {
            $data['description'] .= ' - ' . $request->worker;
        }

        // Clean worker from data array if it's not a column
        unset($data['worker']);

        $expense = Expense::create($data);

        if ($request->ajax()) {
             return response()->json(['message' => 'Gasto registrado correctamente.', 'expense' => $expense], 201);
        }

        return redirect()->route('expenses.index')->with('success', 'Gasto registrado correctamente.');
    }

    public function edit(Expense $expense)
    {
        $workerCategoryId = \App\Models\ExpenseCategory::where('name', 'like', '%TRABAJADOR%')->first()?->id ?? 3;
        $categories = \App\Models\ExpenseCategory::where('id', '!=', $workerCategoryId)->orderBy('name')->get();
        if (request()->ajax()) {
            return view('expenses.form', compact('expense', 'categories'));
        }
        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        // Sanitize Amount
        if ($request->has('amount')) {
            $request->merge(['amount' => $this->sanitizeCurrency($request->amount)]);
        }

        $request->validate([
            'description' => 'required',
            'category_id' => 'nullable|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,nequi,bancolombia',
            'expense_date' => 'required|date',
            'worker' => 'nullable|string'
        ]);

        $data = $request->all();

        // Append worker to description if present and not already there (simple check)
        // Or strictly rely on user not adding it twice?
        // Let's just append if selected. User can edit description if needed.
        if ($request->has('worker') && !empty($request->worker)) {
             // Check if already ends with worker name to avoid duplication on multiple edits
             if (!str_ends_with($data['description'], ' - ' . $request->worker)) {
                 $data['description'] .= ' - ' . $request->worker;
             }
        }
        
        unset($data['worker']);

        $expense->update($data);

        if ($request->ajax()) {
             return response()->json(['message' => 'Gasto actualizado correctamente.', 'expense' => $expense], 200);
        }

        return redirect()->route('expenses.index')->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Gasto eliminado.');
    }
}
