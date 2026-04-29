<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WorkerExpenseController extends Controller
{
    private $weeklyCap = 420000;

    /**
     * Dynamically resolve the Worker category ID by name
     */
    private function resolveWorkerCategoryId()
    {
        $category = ExpenseCategory::where('name', 'like', '%TRABAJADOR%')->first();
        return $category ? $category->id : 3; // Fallback to 3 if none found
    }

    public function index(Request $request)
    {
        $workerCategoryId = $this->resolveWorkerCategoryId();
        $query = Expense::where('category_id', $workerCategoryId);

        // Resolve Dates (Default to Current Week)
        $startDate = $request->start_date ?? Carbon::now()->startOfWeek()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->endOfWeek()->format('Y-m-d');

        // Always Filter by Date Range
        $query->whereBetween('expense_date', [
            Carbon::parse($startDate)->startOfDay(), 
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        // Filter by Worker
        $selectedWorker = $request->worker;
        if ($selectedWorker) {
            $query->where('description', 'like', "%{$selectedWorker}%");
        }

        $aggregationQuery = clone $query;
        $totalFiltered = $aggregationQuery->sum('amount');

        // Weekly Stats for Chart/Cards
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);
        
        $weeklyStats = collect();
        if ($startDateObj->diffInDays($endDateObj) > 32) {
             // If range is huge, maybe group by month? For now, stick to logic requested or keep simple.
             // User wants "Weekly" expenses default.
             // Let's keep the aggregation logic consistent with the view expectations.
             $workerExpenses = $aggregationQuery->orderBy('expense_date', 'asc')->get();
             $weeklyStats = $workerExpenses->groupBy(function($date) {
                return Carbon::parse($date->expense_date)->startOfWeek()->format('Y-m-d');
            })->map(function ($row) {
                return $row->sum('amount');
            });
        }

        // Individual Worker Totals (Based on Filter)
        $startFilter = Carbon::parse($startDate);
        $endFilter = Carbon::parse($endDate);
        
        $workerTotals = [];
        $workersToTrack = ['BREINER', 'ANDRES', 'JAIR'];

        foreach ($workersToTrack as $w) {
            $workerTotals[$w] = Expense::where('category_id', $workerCategoryId)
                ->where('description', 'like', "%{$w}%")
                ->whereBetween('expense_date', [
                    $startFilter->copy()->startOfDay(), 
                    $endFilter->copy()->endOfDay()
                ])
                ->sum('amount');
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(10);

        if ($request->ajax()) {
            return view('workers.table', compact('expenses', 'totalFiltered', 'weeklyStats', 'selectedWorker'))->render();
        }

        return view('workers.index', compact('expenses', 'totalFiltered', 'weeklyStats', 'selectedWorker', 'startDate', 'endDate', 'workerTotals'));
    }

    public function create()
    {
        return view('workers.form');
    }

    public function store(Request $request)
    {
        $workerCategoryId = $this->resolveWorkerCategoryId();
        $amount = $this->sanitizeCurrency($request->amount);
        $request->merge(['amount' => $amount]);

        $request->validate([
            'worker' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,nequi,bancolombia',
            'expense_date' => 'required|date',
        ]);

        $worker = $request->worker;
        $concept = $request->concept ?? 'PAGO TRABAJADOR'; // Default concept
        $date = Carbon::parse($request->expense_date);
        
        // Check Weekly Cap
        if (!$request->has('force_save')) {
            $startOfWeek = $date->copy()->startOfWeek();
            $endOfWeek = $date->copy()->endOfWeek();

            $currentWeeklyTotal = Expense::where('category_id', $workerCategoryId)
                ->where('description', 'like', "%{$worker}%")
                ->whereBetween('expense_date', [$startOfWeek->startOfDay(), $endOfWeek->endOfDay()])
                ->sum('amount');

            if (($currentWeeklyTotal + $amount) > $this->weeklyCap) {
                return response()->json([
                    'over_cap' => true,
                    'current_total' => $currentWeeklyTotal,
                    'new_total' => $currentWeeklyTotal + $amount,
                    'worker' => $worker,
                    'message' => "Este pago superará el tope semanal de $" . number_format($this->weeklyCap)
                ], 409);
            }
        }

        $expense = Expense::create([
            'description' => strtoupper($concept) . " - " . $worker,
            'category_id' => $workerCategoryId,
            'amount' => $amount,
            'payment_method' => $request->payment_method,
            'expense_date' => $request->expense_date,
        ]);

        return response()->json(['message' => 'Pago registrado correctamente.', 'expense' => $expense], 201);
    }

    public function edit(Expense $worker)
    {
        // Extract concept and worker name
        // Format: "CONCEPT - NAME"
        $parts = explode(" - ", $worker->description);
        $concept = $parts[0] ?? 'PAGO TRABAJADOR';
        $workerName = $parts[1] ?? $worker->description;

        return view('workers.form', [
            'expense' => $worker, 
            'worker' => $workerName,
            'currentConcept' => $concept
        ]);
    }

    public function update(Request $request, Expense $worker)
    {
        $workerCategoryId = $this->resolveWorkerCategoryId();
        $amount = $this->sanitizeCurrency($request->amount);
        $request->merge(['amount' => $amount]);

        $request->validate([
            'worker' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,nequi,bancolombia',
            'expense_date' => 'required|date',
        ]);

        $workerName = $request->worker;
        $concept = $request->concept ?? 'PAGO TRABAJADOR';
        $date = Carbon::parse($request->expense_date);

        if (!$request->has('force_save')) {
            $startOfWeek = $date->copy()->startOfWeek();
            $endOfWeek = $date->copy()->endOfWeek();

            $currentWeeklyTotal = Expense::where('category_id', $workerCategoryId)
                ->where('description', 'like', "%{$workerName}%")
                ->where('id', '!=', $worker->id)
                ->whereBetween('expense_date', [
                    $startOfWeek->startOfDay(), 
                    $endOfWeek->endOfDay()
                ])
                ->sum('amount');

            if (($currentWeeklyTotal + $amount) > $this->weeklyCap) {
                return response()->json([
                    'over_cap' => true,
                    'current_total' => $currentWeeklyTotal,
                    'new_total' => $currentWeeklyTotal + $amount,
                    'worker' => $workerName,
                    'message' => "Este pago superará el tope semanal de $" . number_format($this->weeklyCap)
                ], 409);
            }
        }

        $worker->update([
            'description' => strtoupper($concept) . " - " . $workerName,
            'amount' => $amount,
            'payment_method' => $request->payment_method,
            'expense_date' => $request->expense_date,
        ]);

        return response()->json(['message' => 'Registro actualizado correctamente.', 'expense' => $worker], 200);
    }

    public function getWorkerDetails(Request $request)
    {
        $workerCategoryId = $this->resolveWorkerCategoryId();
        $worker = $request->worker;
        $startDate = $request->start_date ?? Carbon::now()->startOfWeek()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->endOfWeek()->format('Y-m-d');

        $expenses = Expense::where('category_id', $workerCategoryId)
            ->where('description', 'like', "%{$worker}%")
            ->whereBetween('expense_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->orderBy('expense_date', 'desc')
            ->get();

        return view('workers.details_modal', compact('expenses', 'worker', 'startDate', 'endDate'))->render();
    }

    public function destroy(Expense $worker)
    {
        $worker->delete();
        return response()->json(['message' => 'Registro eliminado.'], 200);
    }

    private function sanitizeCurrency($value)
    {
        if (is_string($value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        return (float) $value;
    }
}
