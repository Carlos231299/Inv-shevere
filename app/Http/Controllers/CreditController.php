<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Client;
use App\Models\CreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    /**
     * Display a listing of clients with debts.
     */
    public function index(Request $request)
    {
        $query = Client::with(['credits' => function($q) {
            $q->where('status', '!=', 'paid');
        }]);

        if ($request->ajax() || $request->has('q')) {
            $search = $request->get('q');
            $query->where('name', 'like', "%{$search}%");
        }

        $clients = $query->get()->map(function ($client) {
            $client->total_pending = $client->credits->sum(function ($credit) {
                return $credit->total_debt - $credit->paid_amount;
            });
            return $client;
        })->filter(function ($client) {
            return $client->total_pending > 0;
        })->values();

        if ($request->ajax()) {
            return response()->json($clients);
        }

        // Summary Stats (Monthly)
        $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
        $totalPending = Credit::where('status', '!=', 'paid')->get()->sum(function($c) { return $c->total_debt - $c->paid_amount; });
        $newCreditsMonth = Credit::where('created_at', '>=', $startOfMonth)->sum('total_debt');
        $totalCollectedMonth = CreditPayment::where('created_at', '>=', $startOfMonth)->sum('amount');

        return view('cuentas.cuentas por cobrar.index', compact('clients', 'totalPending', 'newCreditsMonth', 'totalCollectedMonth'));
    }

    /**
     * Store a newly created credit (manual debt).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'new_client_name' => 'required_without:client_id|nullable|string|max:255',
            'new_client_phone' => 'nullable|string|max:20',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
        ]);

        if (!$request->client_id && $request->new_client_name) {
            $client = Client::create([
                'name' => $request->new_client_name,
                'phone' => $request->new_client_phone
            ]);
            $validated['client_id'] = $client->id;
        }

        $validated['total_debt'] = $validated['amount'];
        $validated['paid_amount'] = 0;
        $validated['status'] = 'pending';
        
        if (\App\Models\Setting::isInitialMode()) {
            $validated['is_initial'] = true;
        }

        Credit::create($validated);

        return redirect()->route('credits.index')->with('success', 'Cuenta por cobrar registrada exitosamente.');
    }

    /**
     * Display the specified client's credits and history.
     */
    public function show($id)
    {
        $client = Client::with(['credits.payments', 'credits.sale'])->findOrFail($id);
        
        $history = collect();

        // 1. Debts (Credits/Sales)
        foreach($client->credits as $credit) {
            $desc = $credit->description ?? ($credit->sale_id ? "Venta #{$credit->sale_id}" : "Deuda Manual");
            
            $history->push([
                'date' => $credit->created_at,
                'description' => $desc,
                'type' => 'debt',
                'amount' => $credit->total_debt,
                'credit_id' => $credit->id,
                'balance' => $credit->total_debt - $credit->paid_amount
            ]);

            // 2. Payments (linked to this credit)
            foreach($credit->payments as $payment) {
                $pmLabel = match($payment->payment_method) {
                    'cash' => 'Efectivo',
                    'nequi' => 'Nequi',
                    'bancolombia' => 'Bancolombia',
                    default => 'Transf.'
                };
                $history->push([
                    'date' => $payment->payment_date,
                    'description' => 'Abono a ' . $desc . ' (' . $pmLabel . ')',
                    'type' => 'payment',
                    'amount' => $payment->amount,
                    'id' => $payment->id,
                    'credit_parent_id' => $credit->id
                ]);
            }
        }

        // Sort by date desc
        $history = $history->sortByDesc('date');

        // Calculate Global Debt
        $client->total_debt = $client->credits->sum(fn($c) => $c->total_debt - $c->paid_amount);

        return view('cuentas.cuentas por cobrar.show', compact('client', 'history'));
    }

    /**
     * Display all credits for a client (grouped view).
     */
    public function showClient($id)
    {
        $client = Client::findOrFail($id);
        $credits = Credit::where('client_id', $id)
            ->with(['payments', 'sale'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalPending = $credits->sum(function($credit) {
            return $credit->total_debt - $credit->paid_amount;
        });

        return view('cuentas.cuentas por cobrar.show_client', compact('client', 'credits', 'totalPending'));
    }

    /**
     * Store a payment for a client (FIFO distribution or specific credit).
     */
    public function storePayment(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'credit_id' => 'nullable|exists:credits,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string'
        ]);

        $amountRemaining = $request->amount;

        if ($request->credit_id) {
            // Targeted Payment
            $credit = Credit::findOrFail($request->credit_id);
            $pending = $credit->total_debt - $credit->paid_amount;

            if ($amountRemaining > ($pending + 1)) {
                return back()->with('error', 'El abono supera el saldo pendiente de esta cuenta.');
            }

            DB::transaction(function() use ($credit, $amountRemaining, $request) {
                CreditPayment::create([
                    'credit_id' => $credit->id,
                    'amount' => $amountRemaining,
                    'payment_method' => $request->payment_method,
                    'payment_date' => now(),
                ]);

                $credit->paid_amount += $amountRemaining;
                if ($credit->paid_amount >= $credit->total_debt - 0.01) {
                    $credit->status = 'paid';
                    $credit->paid_amount = $credit->total_debt;
                } else {
                    $credit->status = 'partial';
                }
                $credit->save();
            });

            return back()->with('success', 'Abono registrado exitosamente.');
        } else {
            // FIFO Distribution
            $client = Client::with(['credits' => function($q) {
                $q->where('status', '!=', 'paid')->orderBy('created_at', 'asc');
            }])->findOrFail($request->client_id);

            $totalDebt = $client->credits->sum(fn($c) => $c->total_debt - $c->paid_amount);

            if ($amountRemaining > ($totalDebt + 1)) {
                return back()->with('error', 'El abono supera la deuda total del cliente.');
            }

            DB::transaction(function() use ($client, $amountRemaining, $request) {
                $rem = $amountRemaining;
                foreach ($client->credits as $credit) {
                    if ($rem <= 0) break;

                    $pending = $credit->total_debt - $credit->paid_amount;
                    $paymentAmount = min($rem, $pending);

                    if ($paymentAmount > 0) {
                        CreditPayment::create([
                            'credit_id' => $credit->id,
                            'amount' => $paymentAmount,
                            'payment_method' => $request->payment_method,
                            'payment_date' => now(),
                        ]);

                        $credit->paid_amount += $paymentAmount;
                        if ($credit->paid_amount >= $credit->total_debt - 0.01) {
                            $credit->status = 'paid';
                            $credit->paid_amount = $credit->total_debt;
                        } else {
                            $credit->status = 'partial';
                        }
                        $credit->save();

                        $rem -= $paymentAmount;
                    }
                }
            });

            return back()->with('success', 'Abono registrado y distribuido correctamente.');
        }
    }

    /**
     * Destroy all credits for a client.
     */
    public function destroyByClient($id)
    {
        $client = Client::findOrFail($id);
        
        DB::transaction(function() use ($client) {
            foreach($client->credits as $credit) {
                $credit->payments()->delete();
                $credit->delete();
            }
        });

        return redirect()->route('credits.index')->with('success', 'Historial del cliente eliminado.');
    }

    /**
     * Edit a specific payment.
     */
    public function editPayment($id)
    {
        $payment = CreditPayment::findOrFail($id);
        
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

    /**
     * Update a specific payment.
     */
    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string'
        ]);

        $payment = CreditPayment::findOrFail($id);
        $credit = $payment->credit; // Valid relationship check

        // Revert old amount
        $credit->paid_amount -= $payment->amount;
        
        // Check new limit
        $newTotalPaid = $credit->paid_amount + $request->amount;

        if ($newTotalPaid > ($credit->total_debt + 1)) {
             return back()->with('error', 'El nuevo monto supera el total de la deuda de este crédito específico.');
        }

        // Apply new values
        $payment->amount = $request->amount;
        $payment->payment_method = $request->payment_method;
        $payment->save();

        $credit->paid_amount = $newTotalPaid;
        
        if ($credit->paid_amount >= $credit->total_debt - 0.01) {
            $credit->status = 'paid';
            $credit->paid_amount = $credit->total_debt;
        } else {
            $credit->status = ($credit->paid_amount > 0) ? 'partial' : 'pending';
        }
        
        $credit->save();

        return back()->with('success', 'Abono actualizado correctamente.');
    }
}
