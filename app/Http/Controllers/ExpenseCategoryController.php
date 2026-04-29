<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('expenses.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('expenses.categories.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:expense_categories,name'
        ]);

        $category = ExpenseCategory::create($request->all());
 
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Categoría creada correctamente.', 'category' => $category], 201);
        }
 
        return redirect()->route('expense-categories.index')->with('success', 'Categoría creada correctamente.');
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        return view('expenses.categories.form', ['category' => $expenseCategory]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $request->validate([
            'name' => 'required|unique:expense_categories,name,' . $expenseCategory->id
        ]);

        $expenseCategory->update($request->all());
 
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Categoría actualizada correctamente.', 'category' => $expenseCategory]);
        }
 
        return redirect()->route('expense-categories.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();
 
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Categoría eliminada.']);
        }
 
        return redirect()->route('expense-categories.index')->with('success', 'Categoría eliminada.');
    }
}
