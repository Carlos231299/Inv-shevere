<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movement;
use App\Models\Credit;
use App\Models\Expense;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\AccountPayable;
use App\Models\CreditPayment;
use App\Models\SalePayment;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Get Active Cash Register Session & Determine Reset Point FIRST
        $activeRegister = \App\Models\CashRegister::where('status', 'open')->first();

        $baseNequi = \App\Models\Setting::getInitialNequi();
        $baseBancolombia = \App\Models\Setting::getInitialBancolombia();
        $initialCash = \App\Models\Setting::getInitialCash();
        
        $resetNequiAt = \Carbon\Carbon::parse(\App\Models\Setting::getResetTimestamp('nequi'))->addSecond();
        $resetBancolombiaAt = \Carbon\Carbon::parse(\App\Models\Setting::getResetTimestamp('bancolombia'))->addSecond();
        $resetCashAt = \Carbon\Carbon::parse(\App\Models\Setting::getResetTimestamp('cash'))->addSecond();

        if ($activeRegister) {
            $baseNequi = $activeRegister->initial_nequi;
            $baseBancolombia = $activeRegister->initial_bancolombia;
            $initialCash = $activeRegister->initial_cash;
            
            $resetNequiAt = \Carbon\Carbon::parse($activeRegister->opened_at)->addSecond();
            $resetBancolombiaAt = \Carbon\Carbon::parse($activeRegister->opened_at)->addSecond();
            $resetCashAt = \Carbon\Carbon::parse($activeRegister->opened_at)->addSecond();
        } else {
            $initialCash = 0;
            $baseNequi = 0;
            $baseBancolombia = 0;
        }

        // 2. Sales Today Breakdown (Calculated AFTER reset point)
        $directCashToday = Movement::where('type', 'sale')
            ->where('is_initial', false)
            ->where('payment_method', 'cash')
            ->where('created_at', '>', $resetCashAt)
            ->sum('total');
            
        $paymentsTodayCash = CreditPayment::where('payment_method', 'cash')
            ->where('created_at', '>', $resetCashAt)
            ->sum('amount');

        $mixedCashToday = SalePayment::whereHas('sale', function($q) use ($resetCashAt) {
                $q->where('created_at', '>', $resetCashAt)
                  ->where('payment_method', 'mixed');
            })
            ->where('payment_method', 'cash')
            ->sum('amount');

        $salesTodayCash = $directCashToday + $paymentsTodayCash + $mixedCashToday;

        $mixedNequiToday = SalePayment::whereHas('sale', function($q) use ($resetNequiAt) {
            $q->where('created_at', '>', $resetNequiAt)->where('payment_method', 'mixed');
        })->where('payment_method', 'nequi')->sum('amount');

        $salesTodayNequi = Movement::where('type', 'sale')->where('is_initial', false)
            ->where('payment_method', 'nequi')->where('created_at', '>', $resetNequiAt)->sum('total') 
            + $mixedNequiToday;

        $mixedBancolombiaToday = SalePayment::whereHas('sale', function($q) use ($resetBancolombiaAt) {
            $q->where('created_at', '>', $resetBancolombiaAt)->where('payment_method', 'mixed');
        })->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->sum('amount');

        $salesTodayBancolombia = Movement::where('type', 'sale')->where('is_initial', false)
            ->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('created_at', '>', $resetBancolombiaAt)->sum('total') 
            + $mixedBancolombiaToday;

        $salesTodayCredit = Movement::where('type', 'sale')
            ->where('is_initial', false)
            ->where('payment_method', 'credit')
            ->where('created_at', '>', $resetCashAt)
            ->sum('total');

        $salesToday = $salesTodayCash + $salesTodayNequi + $salesTodayBancolombia + $salesTodayCredit;

        // 3. Payments Received Today (Abonos)
        $paymentsTodayNequi = CreditPayment::where('payment_method', 'nequi')->where('created_at', '>', $resetNequiAt)->sum('amount');
        $paymentsTodayBancolombia = CreditPayment::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('created_at', '>', $resetBancolombiaAt)->sum('amount');
        $collectedReceivablesToday = CreditPayment::where('created_at', '>', $resetCashAt)->sum('amount');

        // 4. Incomes Total
        $incomeNequiToday = $salesTodayNequi + $paymentsTodayNequi;
        $incomeBancolombiaToday = $salesTodayBancolombia + $paymentsTodayBancolombia;

        // 5. Outgoes (Expenses, Purchases, Payables)
        $expensesTodayCash = Expense::where('payment_method', 'cash')->where('expense_date', '>', $resetCashAt)->sum('amount');
        $expensesTodayNequi = Expense::where('payment_method', 'nequi')->where('expense_date', '>', $resetNequiAt)->sum('amount');
        $expensesTodayBancolombia = Expense::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('expense_date', '>', $resetBancolombiaAt)->sum('amount');
        $expensesDay = $expensesTodayCash + $expensesTodayNequi + $expensesTodayBancolombia;

        $paidPayablesCash = \App\Models\AccountPayablePayment::where('payment_method', 'cash')->where('payment_date', '>', $resetCashAt)->sum('amount');
        $paidPayablesNequi = \App\Models\AccountPayablePayment::where('payment_method', 'nequi')->where('payment_date', '>', $resetNequiAt)->sum('amount');
        $paidPayablesBancolombia = \App\Models\AccountPayablePayment::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('payment_date', '>', $resetBancolombiaAt)->sum('amount');
        $paidPayablesToday = $paidPayablesCash + $paidPayablesNequi + $paidPayablesBancolombia;

        $cashPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'cash')->where('created_at', '>', $resetCashAt)->sum('total');
        $nequiPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'nequi')->where('created_at', '>', $resetNequiAt)->sum('total');
        $bancolombiaPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('created_at', '>', $resetBancolombiaAt)->sum('total');
        $creditPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'credit')->where('created_at', '>', $resetCashAt)->sum('total');

        $outgoNequiToday = $paidPayablesNequi + $expensesTodayNequi + $nequiPurchases;
        $outgoBancolombiaToday = $paidPayablesBancolombia + $expensesTodayBancolombia + $bancolombiaPurchases;

        // 6. Compatibility & Dashboard Totals
        $salesTodayTransfer = $salesTodayNequi + $salesTodayBancolombia;
        $paymentsTodayTransfer = $paymentsTodayNequi + $paymentsTodayBancolombia;
        $totalNequiToday = $incomeNequiToday - $outgoNequiToday;
        $totalBancolombiaToday = $incomeBancolombiaToday - $outgoBancolombiaToday;
        $totalTransferToday = $totalNequiToday + $totalBancolombiaToday;

        // 7. Manual Adjustments
        $adjEntryCash = \App\Models\CashAdjustment::where('type', 'entry')->where('payment_method', 'cash')->where('created_at', '>', $resetCashAt)->sum('amount');
        $adjExitCash = \App\Models\CashAdjustment::where('type', 'exit')->where('payment_method', 'cash')->where('created_at', '>', $resetCashAt)->sum('amount');
        $adjEntryNequi = \App\Models\CashAdjustment::where('type', 'entry')->where('payment_method', 'nequi')->where('created_at', '>', $resetNequiAt)->sum('amount');
        $adjExitNequi = \App\Models\CashAdjustment::where('type', 'exit')->where('payment_method', 'nequi')->where('created_at', '>', $resetNequiAt)->sum('amount');
        $adjEntryBancolombia = \App\Models\CashAdjustment::where('type', 'entry')->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('created_at', '>', $resetBancolombiaAt)->sum('amount');
        $adjExitBancolombia = \App\Models\CashAdjustment::where('type', 'exit')->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('created_at', '>', $resetBancolombiaAt)->sum('amount');

        // 8. FINAL BALANCES (Cumulative)
        $nequiBalance = $baseNequi + $incomeNequiToday + $adjEntryNequi - ($outgoNequiToday + $adjExitNequi);
        $bancolombiaBalance = $baseBancolombia + $incomeBancolombiaToday + $adjEntryBancolombia - ($outgoBancolombiaToday + $adjExitBancolombia);

        // 9. HISTORY (For Previous Day Balance calculation)
        $totalCashIncomeHistory = Movement::where('type', 'sale')->where('is_initial', false)->where('payment_method', 'cash')->where('created_at', '<=', $resetCashAt)->whereDate('created_at', '<', $today)->sum('total') 
            + CreditPayment::where('payment_method', 'cash')->where('created_at', '<=', $resetCashAt)->whereDate('created_at', '<', $today)->sum('amount')
            + SalePayment::whereHas('sale', function($q) use ($resetCashAt, $today) {
                $q->where('payment_method', 'mixed')->where('created_at', '<=', $resetCashAt)->whereDate('created_at', '<', $today);
            })->where('payment_method', 'cash')->sum('amount');
        
        $totalCashOutgoHistory = Expense::where('payment_method', 'cash')->where('expense_date', '<=', $resetCashAt)->whereDate('expense_date', '<', $today)->sum('amount') 
            + Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'cash')->where('created_at', '<=', $resetCashAt)->whereDate('created_at', '<', $today)->sum('total') 
            + \App\Models\AccountPayablePayment::where('payment_method', 'cash')->where('payment_date', '<=', $resetCashAt)->whereDate('payment_date', '<', $today)->sum('amount');

        $previousDayBalance = $initialCash + $totalCashIncomeHistory - $totalCashOutgoHistory;

        // 10. CURRENT CASH TOTAL
        $totalCashIncomeAll = $salesTodayCash + $collectedReceivablesToday + $adjEntryCash;
        $totalCashOutgoAll = $expensesTodayCash + $cashPurchases + $paidPayablesCash + $adjExitCash;
        $totalCash = $initialCash + $totalCashIncomeAll - $totalCashOutgoAll;
        $cashInBoxToday = $totalCash - $previousDayBalance;

        // 11. Net Profit & Other Stats
        $costToday = $movementsToday = Movement::where('type', 'sale')->where('is_initial', false)->where('created_at', '>', $resetCashAt)->with('product')->get()->reduce(function ($carry, $mov) {
            return $carry + ($mov->quantity * ($mov->cost_at_moment ?? ($mov->product->cost_price ?? 0)));
        }, 0);
        $profitToday = $salesToday - $costToday - $expensesDay;

        $receivables = Credit::where('status', 'pending')->where('is_initial', false)->get()->sum(function($c) { return $c->total_debt - $c->paid_amount; });
        $payables = AccountPayable::whereIn('status', ['pending', 'overdue'])->get()->sum(function($p) { return $p->amount - $p->paid_amount; });
        $lowStockProducts = Product::whereColumn('stock', '<=', 'min_stock')->get();

        // 12. Chart Data
        $chartData = ['labels' => [], 'sales' => [], 'purchases' => []];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $chartData['labels'][] = $date->format('d/m');
            $chartData['sales'][] = Movement::where('type', 'sale')->where('is_initial', false)->whereDate('created_at', $date->format('Y-m-d'))->sum('total');
            $chartData['purchases'][] = Movement::where('type', 'purchase')->where('is_initial', false)->whereDate('created_at', $date->format('Y-m-d'))->sum('total');
        }

        // Compatibility variables for view
        $cashSales = $directCashToday + $mixedCashToday;
        $paymentsToday = $paymentsTodayCash;
        $cashPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'cash')->where('created_at', '>', $resetCashAt)->sum('total');
        $nequiPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'nequi')->where('created_at', '>', $resetNequiAt)->sum('total');
        $bancolombiaPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('created_at', '>', $resetBancolombiaAt)->sum('total');
        $creditPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'credit')->where('created_at', '>', $resetCashAt)->sum('total');
        $cashPaymentsPaid = $paidPayablesCash;

        return view('dashboard', compact(
            'salesToday', 'salesTodayCash', 'salesTodayTransfer', 'paymentsTodayTransfer', 'totalTransferToday', 'salesTodayCredit',
            'cashInBoxToday', 'previousDayBalance', 'profitToday', 'receivables', 'payables', 'expensesDay', 'lowStockProducts', 'chartData',
            'paidPayablesToday', 'collectedReceivablesToday', 'totalCash', 'nequiBalance', 'bancolombiaBalance', 'activeRegister',
            'incomeNequiToday', 'incomeBancolombiaToday', 'salesTodayNequi', 'salesTodayBancolombia', 'baseNequi', 'baseBancolombia',
            'expensesTodayCash', 'expensesTodayNequi', 'expensesTodayBancolombia', 'paidPayablesCash', 'paidPayablesNequi', 'paidPayablesBancolombia',
            'cashSales', 'paymentsToday', 'cashPurchases', 'nequiPurchases', 'bancolombiaPurchases', 'creditPurchases', 'cashPaymentsPaid'
        ));
    }
}
