<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Movement;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\AccountPayable;
use App\Models\AccountPayablePayment;
use App\Models\Expense;
use App\Models\Product;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function resetTransactions()
    {
        try {
            // Disable FK checks (Database agnostic)
            \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

            // Truncate transaction tables
            Sale::truncate();
            Purchase::truncate();
            Movement::truncate();
            Credit::truncate();
            CreditPayment::truncate();
            AccountPayable::truncate();
            AccountPayablePayment::truncate();
            Expense::truncate();
            \App\Models\SalePayment::truncate();
            \App\Models\CashAdjustment::truncate();
            \App\Models\CashRegister::truncate();
            \App\Models\Batch::truncate();
            
            // Reset global financial bases in settings
            \App\Models\Setting::set('initial_cash_balance', 0);
            \App\Models\Setting::set('initial_nequi_balance', 0);
            \App\Models\Setting::set('initial_bancolombia_balance', 0);

            // Reset product stock to 0 since movements are gone
            Product::query()->update(['stock' => 0]);

            // Enable FK checks
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

            return redirect()->route('settings.index')->with('success', '¡Todas las transacciones han sido eliminadas y el stock reiniciado a 0!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
            return redirect()->route('settings.index')->with('error', 'Error al reiniciar datos: ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            // Update financial bases if present
            if ($request->has('initial_cash_balance')) {
                \App\Models\Setting::set('initial_cash_balance', $request->initial_cash_balance);
                \App\Models\Setting::setResetTimestamp('cash');
            }
            if ($request->has('initial_nequi_balance')) {
                \App\Models\Setting::set('initial_nequi_balance', $request->initial_nequi_balance);
                \App\Models\Setting::setResetTimestamp('nequi');
            }
            if ($request->has('initial_bancolombia_balance')) {
                \App\Models\Setting::set('initial_bancolombia_balance', $request->initial_bancolombia_balance);
                \App\Models\Setting::setResetTimestamp('bancolombia');
            }

            // Update business details if present
            if ($request->has('business_name')) {
                \App\Models\Setting::set('business_name', $request->business_name);
            }
            if ($request->has('business_nit')) {
                \App\Models\Setting::set('business_nit', $request->business_nit);
            }
            if ($request->has('business_address')) {
                \App\Models\Setting::set('business_address', $request->business_address);
            }
            if ($request->has('business_phone')) {
                \App\Models\Setting::set('business_phone', $request->business_phone);
            }
            if ($request->has('business_email')) {
                \App\Models\Setting::set('business_email', $request->business_email);
            }
            if ($request->has('business_payment_info')) {
                \App\Models\Setting::set('business_payment_info', $request->business_payment_info);
            }


            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Configuración actualizada correctamente!'
                ]);
            }

            return redirect()->route('settings.index')->with('success', '¡Configuración actualizada correctamente!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('settings.index')->with('error', 'Error al actualizar saldos: ' . $e->getMessage());
        }
    }
}
