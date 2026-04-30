<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashRegister;
use App\Models\Movement;
use App\Models\CreditPayment;
use App\Models\SalePayment;
use App\Models\Expense;
use App\Models\AccountPayablePayment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CashRegisterController extends Controller
{
    public function index()
    {
        $registers = CashRegister::with('user')->orderBy('created_at', 'desc')->paginate(15);
        return view('cash_registers.index', compact('registers'));
    }
    public function open(Request $request)
    {
        $request->validate([
            'initial_cash' => 'required|numeric|min:0',
            'initial_nequi' => 'required|numeric|min:0',
            'initial_bancolombia' => 'required|numeric|min:0'
        ]);

        $activeRegister = CashRegister::where('status', 'open')->first();
        if ($activeRegister) {
            return response()->json(['success' => false, 'message' => 'Ya existe una caja abierta.'], 400);
        }

        CashRegister::create([
            'user_id' => Auth::id(),
            'opened_at' => now(),
            'initial_cash' => $request->initial_cash,
            'initial_nequi' => $request->initial_nequi,
            'initial_bancolombia' => $request->initial_bancolombia,
            'status' => 'open'
        ]);

        return response()->json(['success' => true, 'message' => 'Caja abierta correctamente.']);
    }

    public function storeAdjustment(Request $request)
    {
        $request->validate([
            'type' => 'required|in:entry,exit',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $activeRegister = CashRegister::where('status', 'open')->first();
        if (!$activeRegister) {
            return response()->json(['success' => false, 'message' => 'No hay una caja abierta para registrar este movimiento.'], 400);
        }

        \App\Models\CashAdjustment::create([
            'cash_register_id' => $activeRegister->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'description' => $request->description,
            'user_id' => Auth::id()
        ]);

        return response()->json(['success' => true, 'message' => 'Movimiento registrado correctamente.']);
    }

    public function getSystemTotals()
    {
        $activeRegister = CashRegister::where('status', 'open')->first();
        if (!$activeRegister) {
            return response()->json(['success' => false, 'message' => 'No hay caja abierta.'], 400);
        }

        $openedAt = $activeRegister->opened_at;

        // Cash Income
        $salesCash = Movement::where('type', 'sale')->where('is_initial', false)
            ->where('payment_method', 'cash')->where('created_at', '>=', $openedAt)->sum('total');
        $mixedCash = SalePayment::whereHas('sale', function($q) use ($openedAt) {
                $q->where('payment_method', 'mixed')->where('created_at', '>=', $openedAt);
            })->where('payment_method', 'cash')->sum('amount');
        $creditPaymentsCash = CreditPayment::where('payment_method', 'cash')->where('created_at', '>=', $openedAt)->sum('amount');
        
        $totalCashIncome = $salesCash + $mixedCash + $creditPaymentsCash;

        // Cash Outgo
        $expensesCash = Expense::where('payment_method', 'cash')->where('created_at', '>=', $openedAt)->sum('amount');
        $purchasesCash = Movement::where('type', 'purchase')->where('is_initial', false)
            ->where('payment_method', 'cash')->where('created_at', '>=', $openedAt)->sum('total');
        $payablesCash = AccountPayablePayment::where('payment_method', 'cash')->where('created_at', '>=', $openedAt)->sum('amount');

        $totalCashOutgo = $expensesCash + $purchasesCash + $payablesCash;

        // Manual Adjustments (Cash)
        $adjEntryCash = \App\Models\CashAdjustment::where('cash_register_id', $activeRegister->id)->where('type', 'entry')->where('payment_method', 'cash')->sum('amount');
        $adjExitCash = \App\Models\CashAdjustment::where('cash_register_id', $activeRegister->id)->where('type', 'exit')->where('payment_method', 'cash')->sum('amount');

        $systemCash = $activeRegister->initial_cash + $totalCashIncome + $adjEntryCash - ($totalCashOutgo + $adjExitCash);

        // Nequi
        $salesNequi = Movement::where('type', 'sale')->where('is_initial', false)
            ->where('payment_method', 'nequi')->where('created_at', '>=', $openedAt)->sum('total');
        $mixedNequi = SalePayment::whereHas('sale', function($q) use ($openedAt) {
                $q->where('payment_method', 'mixed')->where('created_at', '>=', $openedAt);
            })->where('payment_method', 'nequi')->sum('amount');
        $creditPaymentsNequi = CreditPayment::where('payment_method', 'nequi')->where('created_at', '>=', $openedAt)->sum('amount');
        $totalNequiIncome = $salesNequi + $mixedNequi + $creditPaymentsNequi;

        $expensesNequi = Expense::where('payment_method', 'nequi')->where('created_at', '>=', $openedAt)->sum('amount');
        $purchasesNequi = Movement::where('type', 'purchase')->where('is_initial', false)
            ->where('payment_method', 'nequi')->where('created_at', '>=', $openedAt)->sum('total');
        $payablesNequi = AccountPayablePayment::where('payment_method', 'nequi')->where('created_at', '>=', $openedAt)->sum('amount');
        $totalNequiOutgo = $expensesNequi + $purchasesNequi + $payablesNequi;

        // Manual Adjustments (Nequi)
        $adjEntryNequi = \App\Models\CashAdjustment::where('cash_register_id', $activeRegister->id)->where('type', 'entry')->where('payment_method', 'nequi')->sum('amount');
        $adjExitNequi = \App\Models\CashAdjustment::where('cash_register_id', $activeRegister->id)->where('type', 'exit')->where('payment_method', 'nequi')->sum('amount');

        $systemNequi = $activeRegister->initial_nequi + $totalNequiIncome + $adjEntryNequi - ($totalNequiOutgo + $adjExitNequi);

        // Bancolombia
        $methodsBancolombia = ['bancolombia', 'bank', 'transfer'];
        $salesBancolombia = Movement::where('type', 'sale')->where('is_initial', false)
            ->whereIn('payment_method', $methodsBancolombia)->where('created_at', '>=', $openedAt)->sum('total');
        $mixedBancolombia = SalePayment::whereHas('sale', function($q) use ($openedAt) {
                $q->where('payment_method', 'mixed')->where('created_at', '>=', $openedAt);
            })->whereIn('payment_method', $methodsBancolombia)->sum('amount');
        $creditPaymentsBancolombia = CreditPayment::whereIn('payment_method', $methodsBancolombia)->where('created_at', '>=', $openedAt)->sum('amount');
        $totalBancolombiaIncome = $salesBancolombia + $mixedBancolombia + $creditPaymentsBancolombia;

        $expensesBancolombia = Expense::whereIn('payment_method', $methodsBancolombia)->where('created_at', '>=', $openedAt)->sum('amount');
        $purchasesBancolombia = Movement::where('type', 'purchase')->where('is_initial', false)
            ->whereIn('payment_method', $methodsBancolombia)->where('created_at', '>=', $openedAt)->sum('total');
        $payablesBancolombia = AccountPayablePayment::whereIn('payment_method', $methodsBancolombia)->where('created_at', '>=', $openedAt)->sum('amount');
        $totalBancolombiaOutgo = $expensesBancolombia + $purchasesBancolombia + $payablesBancolombia;

        // Manual Adjustments (Bancolombia)
        $adjEntryBancolombia = \App\Models\CashAdjustment::where('cash_register_id', $activeRegister->id)->where('type', 'entry')->whereIn('payment_method', $methodsBancolombia)->sum('amount');
        $adjExitBancolombia = \App\Models\CashAdjustment::where('cash_register_id', $activeRegister->id)->where('type', 'exit')->whereIn('payment_method', $methodsBancolombia)->sum('amount');

        $systemBancolombia = $activeRegister->initial_bancolombia + $totalBancolombiaIncome + $adjEntryBancolombia - ($totalBancolombiaOutgo + $adjExitBancolombia);

        return response()->json([
            'success' => true,
            'system_cash' => $systemCash,
            'system_nequi' => $systemNequi,
            'system_bancolombia' => $systemBancolombia
        ]);
    }

    public function close(Request $request)
    {
        $request->validate([
            'physical_cash' => 'required|numeric|min:0',
            'physical_nequi' => 'required|numeric|min:0',
            'physical_bancolombia' => 'required|numeric|min:0',
        ]);

        $activeRegister = CashRegister::where('status', 'open')->first();
        if (!$activeRegister) {
            return response()->json(['success' => false, 'message' => 'No hay caja abierta.'], 400);
        }

        // Calculate totals right before closing
        $totals = $this->getSystemTotals()->getData();

        $activeRegister->update([
            'closed_at' => now(),
            'system_cash' => $totals->system_cash,
            'physical_cash' => $request->physical_cash,
            'system_nequi' => $totals->system_nequi,
            'physical_nequi' => $request->physical_nequi,
            'system_bancolombia' => $totals->system_bancolombia,
            'physical_bancolombia' => $request->physical_bancolombia,
            'notes' => $request->notes,
            'status' => 'closed'
        ]);

        // Borrón y Cuenta Nueva: Sincronizar configuración global al cerrar la caja
        $nextCashBase = $request->physical_cash;
        
        // Si el usuario eligió retirar a caja fuerte, registramos el movimiento y la base queda en 0
        if ($request->withdraw_to_safe) {
            \App\Models\CashAdjustment::create([
                'cash_register_id' => $activeRegister->id,
                'type' => 'exit',
                'amount' => $request->physical_cash,
                'payment_method' => 'cash',
                'description' => 'Retiro total a Caja Fuerte al cierre',
                'user_id' => Auth::id()
            ]);
            $nextCashBase = 0;
        }

        \App\Models\Setting::setResetTimestamp('cash', now());
        \App\Models\Setting::set('initial_cash_balance', $nextCashBase);
        
        \App\Models\Setting::setResetTimestamp('nequi', now());
        \App\Models\Setting::set('initial_nequi_balance', $request->physical_nequi);
        
        \App\Models\Setting::setResetTimestamp('bancolombia', now());
        \App\Models\Setting::set('initial_bancolombia_balance', $request->physical_bancolombia);

        return response()->json(['success' => true, 'message' => 'Caja cerrada correctamente.', 'id' => $activeRegister->id]);
    }

    public function ticket($id)
    {
        $register = CashRegister::with('user')->findOrFail($id);
        $adjustments = \App\Models\CashAdjustment::where('cash_register_id', $id)->get();
        return view('cash_registers.ticket', compact('register', 'adjustments'));
    }
}
