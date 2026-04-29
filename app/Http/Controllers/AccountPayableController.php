<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\Provider;
use Illuminate\Http\Request;

use App\Models\AccountPayablePayment;

class AccountPayableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $term = $request->q;
            $providers = Provider::where('name', 'LIKE', "%{$term}%")
                ->whereHas('accountPayables', function($q) {
                    $q->where('status', '!=', 'paid');
                })
                ->withSum(['accountPayables as total_debt' => function($q) {
                    $q->where('status', '!=', 'paid');
                }], 'amount') // This sums the original amount, we need remaining
                // Actually, logic is complex for remaining in SQL directly if partial payments exist in complex ways.
                // Let's fetch providers with their payables and calculate in PHP or slightly more complex query.
                // Simpler approach for now: Get providers with pending payables.
                ->get();

            // Refine total debt calculation (Original - Paid)
            $providers->transform(function($p) {
                $p->total_pending = $p->accountPayables->where('status', '!=', 'paid')->sum(function($ap) {
                     return $ap->amount - $ap->paid_amount;
                });
                return $p;
            });
            
            return response()->json($providers);
        }

        // Default: Show all providers with debt
        $providers = Provider::whereHas('accountPayables', function($q) {
            $q->where('status', '!=', 'paid');
        })->get();

        // Calculate pending for each
        $providers->transform(function($p) {
            $p->total_pending = $p->accountPayables->where('status', '!=', 'paid')->sum(function($ap) {
                    return $ap->amount - $ap->paid_amount;
            });
            return $p;
        });

        // Summary Stats (Monthly)
        $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
        $totalPending = AccountPayable::whereIn('status', ['pending', 'overdue'])->get()->sum(function($p) { return $p->amount - $p->paid_amount; });
        $newDebtsMonth = AccountPayable::where('created_at', '>=', $startOfMonth)->sum('amount');
        $totalPaidMonth = AccountPayablePayment::where('payment_date', '>=', $startOfMonth)->sum('amount');

        return view('cuentas.cuentas por pagar.index', compact('providers', 'totalPending', 'newDebtsMonth', 'totalPaidMonth'));
    }

    public function showProvider($id)
    {
        $provider = Provider::findOrFail($id);
        
        // Get all payables for this provider
        $cuentas = AccountPayable::where('provider_id', $id)
            ->latest()
            ->get();
            
        $totalPending = $cuentas->where('status', '!=', 'paid')->sum(function($ap) {
            return $ap->amount - $ap->paid_amount;
        });

        return view('cuentas.cuentas por pagar.show_provider', compact('provider', 'cuentas', 'totalPending'));
    }

    public function create()
    {
        $providers = Provider::all();
        return view('cuentas.cuentas por pagar.create', compact('providers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider_id' => 'nullable|exists:providers,id',
            'new_provider_name' => 'required_without:provider_id|nullable|string|max:255',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,paid,overdue'
        ]);

        if (!$request->provider_id && $request->new_provider_name) {
            $provider = Provider::create(['name' => $request->new_provider_name]);
            $validated['provider_id'] = $provider->id;
        }

        unset($validated['new_provider_name']);

        if ($validated['status'] == 'paid') {
            $validated['paid_amount'] = $validated['amount'];
        }

        // Set is_initial flag if in Initial Mode
        if (\App\Models\Setting::isInitialMode()) {
            $validated['is_initial'] = true;
        }

        AccountPayable::create($validated);

        return redirect()->route('cuentas-por-pagar.index')->with('success', 'Cuenta por pagar registrada exitosamente.');
    }

    public function show($id)
    {
        $cuenta = AccountPayable::with(['provider', 'payments'])->findOrFail($id);
        
        $history = collect();

        // 1. Initial Debt
        $history->push([
            'date' => $cuenta->created_at,
            'description' => $cuenta->description ?? 'Deuda Inicial',
            'type' => 'debt',
            'amount' => $cuenta->amount
        ]);

        // 2. Payments from new table
        foreach($cuenta->payments as $payment) {
            $pmLabel = match($payment->payment_method) {
                'cash' => 'Efectivo',
                'nequi' => 'Nequi',
                'bancolombia' => 'Bancolombia',
                default => 'Transferencia'
            };
            $history->push([
                'date' => $payment->payment_date,
                'description' => "Abono ($pmLabel)",
                'type' => 'payment',
                'amount' => $payment->amount,
                'id' => $payment->id // Added ID for Edit
            ]);
        }

        // Sort by date
        $history = $history->sortBy('date');

        return view('cuentas.cuentas por pagar.show', compact('cuenta', 'history'));
    }

    public function storePayment(Request $request)
    {
        $request->validate([
            'account_payable_id' => 'required|exists:account_payables,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string'
        ]);

        $cuenta = AccountPayable::findOrFail($request->account_payable_id);

        // Calculate new paid amount
        $newPaidAmount = $cuenta->paid_amount + $request->amount;

        if ($newPaidAmount > $cuenta->amount) {
            return back()->with('error', 'El abono supera la deuda total.');
        }

        // Create Payment Record
        AccountPayablePayment::create([
            'account_payable_id' => $cuenta->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => now(),
        ]);

        // Update Parent
        $cuenta->paid_amount = $newPaidAmount;

        if ($cuenta->paid_amount >= $cuenta->amount) {
            $cuenta->status = 'paid';
        } else {
            $cuenta->status = 'pending';
        }

        $cuenta->save();

        return back()->with('success', 'Abono registrado correctamente.');
    }

    public function edit($id)
    {
        $cuenta = AccountPayable::findOrFail($id);
        $providers = Provider::all();
        if (request()->ajax()) {
            return view('cuentas.cuentas por pagar.form', compact('cuenta', 'providers'));
        }
        return view('cuentas.cuentas por pagar.edit', compact('cuenta', 'providers'));
    }

    public function update(Request $request, $id)
    {
        $cuenta = AccountPayable::findOrFail($id);

        $validated = $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0|lte:amount',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,paid,overdue'
        ]);

        if ($validated['paid_amount'] >= $validated['amount']) {
            $validated['status'] = 'paid';
        } elseif ($validated['paid_amount'] > 0) {
            $validated['status'] = 'pending'; // Or 'partial' if we had that status
        }

        $cuenta->update($validated);

        if ($request->ajax()) {
             return response()->json(['message' => 'Cuenta actualizada correctamente.', 'cuenta' => $cuenta], 200);
        }

        return redirect()->route('cuentas-por-pagar.index')->with('success', 'Cuenta actualizada correctamente.');
    }

    public function destroy($id)
    {
        $cuenta = AccountPayable::findOrFail($id);
        $cuenta->delete();
        return redirect()->route('cuentas-por-pagar.index')->with('success', 'Cuenta eliminada.');
    }

    public function editPayment($id)
    {
        $payment = AccountPayablePayment::findOrFail($id);
        
        if(request()->ajax()) {
            return response()->json([
                'id' => $id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'date' => $payment->payment_date
            ]);
        }
        return back();
    }

    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string'
        ]);

        $payment = AccountPayablePayment::findOrFail($id);
        $cuenta = $payment->accountPayable; // Fixed relationship access

        // Revert old amount
        $cuenta->paid_amount -= $payment->amount;
        
        // Check new limit
        $newTotalPaid = $cuenta->paid_amount + $request->amount;

        // Tolerance check - allow update even if it matches exactly but strict check > total
        // But the previous balance might have been fully paid.
        // We need to re-validate against total amount.
        if ($newTotalPaid > ($cuenta->amount + 1)) { // Tolerance
             return back()->with('error', 'El nuevo monto supera el total de la deuda.');
        }

        // Apply new values
        $payment->amount = $request->amount;
        $payment->payment_method = $request->payment_method;
        $payment->save();

        $cuenta->paid_amount = $newTotalPaid;
        
        if ($cuenta->paid_amount >= $cuenta->amount - 0.01) {
            $cuenta->status = 'paid';
            $cuenta->paid_amount = $cuenta->amount;
        } else {
            $cuenta->status = 'pending';
        }
        
        $cuenta->save();

        return back()->with('success', 'Abono actualizado correctamente.');
    }
    public function destroyPayment($id)
    {
        $payment = AccountPayablePayment::findOrFail($id);
        $cuenta = $payment->accountPayable;

        // Revert balance
        $cuenta->paid_amount -= $payment->amount;

        // Re-evaluate status
        if ($cuenta->paid_amount >= $cuenta->amount - 0.01) {
            $cuenta->status = 'paid';
        } else {
            $cuenta->status = 'pending';
        }

        $cuenta->save();
        $payment->delete();

        return back()->with('success', 'Abono eliminado correctamente.');
    }
}
