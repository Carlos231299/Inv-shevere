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
            \App\Models\Setting::set('initial_cash_balance', $request->initial_cash_balance);
            \App\Models\Setting::set('initial_nequi_balance', $request->initial_nequi_balance);
            \App\Models\Setting::set('initial_bancolombia_balance', $request->initial_bancolombia_balance);

            // Update timestamps to mark today as the new reset point
            \App\Models\Setting::setResetTimestamp('nequi');
            \App\Models\Setting::setResetTimestamp('bancolombia');
            \App\Models\Setting::setResetTimestamp('cash');

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Saldos iniciales actualizados correctamente!'
                ]);
            }

            return redirect()->route('settings.index')->with('success', '¡Saldos iniciales actualizados correctamente!');
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
