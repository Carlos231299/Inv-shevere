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

        // 1. Sales Today Breakdown (Simplified) - EXCLUDE INITIAL
        $salesToday = Movement::where('type', 'sale')
            ->where('is_initial', false)
            ->whereDate('created_at', $today->toDateString())
            ->sum('total');

        // Contado = Direct Sales + Payments received today (EXCLUDE INITIAL)
        $directCashToday = Movement::where('type', 'sale')
            ->where('is_initial', false)
            ->where('payment_method', 'cash')
            ->whereDate('created_at', $today->toDateString())
            ->sum('total');
            
        $paymentsTodayCash = CreditPayment::where('payment_method', 'cash')
            ->whereDate('created_at', $today->toDateString())
            ->sum('amount');

        // Mixed Payments - Cash Component (Today)
        $mixedCashToday = SalePayment::whereHas('sale', function($q) use ($today) {
                $q->whereDate('created_at', $today->toDateString())
                  ->where('payment_method', 'mixed'); // Ensure strict mixed context
            })
            ->where('payment_method', 'cash')
            ->sum('amount');

        // Restore missing variables
        $salesTodayCash = $directCashToday + $paymentsTodayCash + $mixedCashToday;
        
        // This is recalculated below more accurately, but we need it defined for now or simply use the breakdown sum later.
        // Actually, let's keep it clean.
        // collectedReceivablesToday is re-defined at line ~103 (original) or later, so we might not need it here.
        // But salesTodayCash is definitely needed.

        // SPLIT TRANSFERS: Nequi vs Bancolombia vs Others
        // 1. Collections (Abonos)
        // 5. Initial Bases & Reset Timestamps
        $baseNequi = \App\Models\Setting::getInitialNequi();
        $baseBancolombia = \App\Models\Setting::getInitialBancolombia();
        
        $resetNequiAt = \App\Models\Setting::getResetTimestamp('nequi');
        $resetBancolombiaAt = \App\Models\Setting::getResetTimestamp('bancolombia');

        // FINAL CUMULATIVE BALANCES (Filtered by reset timestamp)
        $paymentsHistoryNequi = CreditPayment::where('payment_method', 'nequi')->where('created_at', '>=', $resetNequiAt)->sum('amount');
        $salesHistoryNequi = Movement::where('type', 'sale')->where('is_initial', false)
            ->where('payment_method', 'nequi')->where('created_at', '>=', $resetNequiAt)->sum('total');
        // Add Mixed Nequi History
        $mixedHistoryNequi = SalePayment::whereHas('sale', function($q) use ($resetNequiAt) {
                $q->where('payment_method', 'mixed')->where('created_at', '>=', $resetNequiAt);
            })->where('payment_method', 'nequi')->sum('amount');

        $paidPayablesHistoryNequi = \App\Models\AccountPayablePayment::where('payment_method', 'nequi')->where('payment_date', '>=', $resetNequiAt)->sum('amount');
        $expensesHistoryNequi = Expense::where('payment_method', 'nequi')->where('expense_date', '>=', $resetNequiAt)->sum('amount');
        $purchasesHistoryNequi = Movement::where('type', 'purchase')->where('is_initial', false)
            ->where('payment_method', 'nequi')->where('created_at', '>=', $resetNequiAt)->sum('total');

        $nequiBalance = $baseNequi + $salesHistoryNequi + $paymentsHistoryNequi + $mixedHistoryNequi - ($paidPayablesHistoryNequi + $expensesHistoryNequi + $purchasesHistoryNequi);

        $paymentsHistoryBancolombia = CreditPayment::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('created_at', '>=', $resetBancolombiaAt)->sum('amount');
        $salesHistoryBancolombia = Movement::where('type', 'sale')->where('is_initial', false)
            ->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('created_at', '>=', $resetBancolombiaAt)->sum('total');
        
        // Add Mixed Bancolombia History
        $mixedHistoryBancolombia = SalePayment::whereHas('sale', function($q) use ($resetBancolombiaAt) {
                $q->where('payment_method', 'mixed')->where('created_at', '>=', $resetBancolombiaAt);
            })->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->sum('amount');
        $paidPayablesHistoryBancolombia = \App\Models\AccountPayablePayment::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('payment_date', '>=', $resetBancolombiaAt)->sum('amount');
        $expensesHistoryBancolombia = Expense::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('expense_date', '>=', $resetBancolombiaAt)->sum('amount');
        $purchasesHistoryBancolombia = Movement::where('type', 'purchase')->where('is_initial', false)
            ->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->where('created_at', '>=', $resetBancolombiaAt)->sum('total');

        $bancolombiaBalance = $baseBancolombia + $salesHistoryBancolombia + $paymentsHistoryBancolombia + $mixedHistoryBancolombia - ($paidPayablesHistoryBancolombia + $expensesHistoryBancolombia + $purchasesHistoryBancolombia);

        // Daily stats (for indicators) - These are NOT filtered by reset timestamp as they are daily
        $mixedNequiToday = SalePayment::whereHas('sale', function($q) use ($today) {
            $q->whereDate('created_at', $today)->where('payment_method', 'mixed');
        })->where('payment_method', 'nequi')->sum('amount');

        $incomeNequiToday = CreditPayment::where('payment_method', 'nequi')->whereDate('created_at', $today)->sum('amount') 
                          + Movement::where('type', 'sale')->where('is_initial', false)->where('payment_method', 'nequi')->whereDate('created_at', $today)->sum('total')
                          + $mixedNequiToday;
        
        $mixedBancolombiaToday = SalePayment::whereHas('sale', function($q) use ($today) {
            $q->whereDate('created_at', $today)->where('payment_method', 'mixed');
        })->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->sum('amount');

        $incomeBancolombiaToday = CreditPayment::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('created_at', $today)->sum('amount')
                                + Movement::where('type', 'sale')->where('is_initial', false)->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('created_at', $today)->sum('total')
                                + $mixedBancolombiaToday;

        $outgoNequiToday = \App\Models\AccountPayablePayment::where('payment_method', 'nequi')->whereDate('payment_date', $today)->sum('amount')
                         + Expense::where('payment_method', 'nequi')->whereDate('expense_date', $today)->sum('amount')
                         + Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'nequi')->whereDate('created_at', $today)->sum('total');

        $outgoBancolombiaToday = \App\Models\AccountPayablePayment::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('payment_date', $today)->sum('amount')
                               + Expense::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('expense_date', $today)->sum('amount')
                               + Movement::where('type', 'purchase')->where('is_initial', false)->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('created_at', $today)->sum('total');
        
        // Granular Outputs specifically for Expenses
        $expensesTodayNequi = Expense::where('payment_method', 'nequi')->whereDate('expense_date', $today)->sum('amount');
        $expensesTodayBancolombia = Expense::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('expense_date', $today)->sum('amount');
        $expensesTodayCash = Expense::where('payment_method', 'cash')->whereDate('expense_date', $today)->sum('amount');

        // Granular Outputs specifically for Payables
        $paidPayablesNequi_Today = \App\Models\AccountPayablePayment::where('payment_method', 'nequi')->whereDate('payment_date', $today)->sum('amount');
        $paidPayablesBancolombia_Today = \App\Models\AccountPayablePayment::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('payment_date', $today)->sum('amount');
        $paidPayablesCash = \App\Models\AccountPayablePayment::where('payment_method', 'cash')->whereDate('payment_date', $today)->sum('amount');

        // Compatibility variables
        $totalNequiToday = $incomeNequiToday - $outgoNequiToday;
        $totalBancolombiaToday = $incomeBancolombiaToday - $outgoBancolombiaToday;
        
        // For Dashboard card summary row compatibility
        $paymentsTodayNequi = CreditPayment::where('payment_method', 'nequi')->whereDate('created_at', $today)->sum('amount');
        $paymentsTodayBancolombia = CreditPayment::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('created_at', $today)->sum('amount');
        $paymentsTodayBancolombia = CreditPayment::whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('created_at', $today)->sum('amount');
        
        // Add Mixed components to salesToday breakdown
        $salesTodayNequi = Movement::where('type', 'sale')->where('is_initial', false)->where('payment_method', 'nequi')->whereDate('created_at', $today)->sum('total') + $mixedNequiToday;

        $salesTodayBancolombia = Movement::where('type', 'sale')->where('is_initial', false)->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('created_at', $today)->sum('total') + $mixedBancolombiaToday;

        // Restore missing daily variables
        $paidPayablesCash = \App\Models\AccountPayablePayment::where('payment_method', 'cash')->whereDate('payment_date', $today)->sum('amount');

        // Old vars refactored
        $paymentsTodayTransfer = $paymentsTodayNequi + $paymentsTodayBancolombia;
        $salesTodayTransfer = $salesTodayNequi + $salesTodayBancolombia;
        $totalTransferToday = $totalNequiToday + $totalBancolombiaToday;
        $paidPayablesToday = $paidPayablesCash + $paidPayablesNequi_Today + $paidPayablesBancolombia_Today;

        // --- RESTORED CALCULATIONS ---

        // Credit Sales
        $salesTodayCredit = Movement::where('type', 'sale')
            ->where('is_initial', false)
            ->where('payment_method', 'credit')
            ->whereDate('created_at', $today->toDateString())
            ->sum('total');

        // Expenses
        $expensesDay = Expense::whereDate('expense_date', $today->toDateString())->sum('amount');

        // Net Profit (Sales - Cost - Expenses)
        $movementsToday = Movement::where('type', 'sale')
            ->where('is_initial', false)
            ->whereDate('created_at', $today)
            ->with('product')
            ->get();
        
        $costToday = $movementsToday->reduce(function ($carry, $mov) {
            $unitCost = $mov->cost_at_moment ?? ($mov->product->cost_price ?? 0);
            return $carry + ($mov->quantity * $unitCost);
        }, 0);

        $profitToday = $salesToday - $costToday - $expensesDay;

        // Receivables (Por Cobrar)
        $receivables = Credit::where('status', 'pending')
            ->where('is_initial', false)
            ->get()
            ->sum(function($credit) {
                return $credit->total_debt - $credit->paid_amount;
            });

        // Payables (Por Pagar)
        $payables = AccountPayable::whereIn('status', ['pending', 'overdue'])
            ->get()
            ->sum(function($payable) {
                return $payable->amount - $payable->paid_amount;
            });

        // Collected Receivables (Abonos) - Total
        $collectedReceivablesToday = CreditPayment::whereDate('created_at', $today)->sum('amount');

        // -----------------------------

        // 6. Low Stock
        $lowStockProducts = Product::whereColumn('stock', '<=', 'min_stock')->get();

        // 7. Chart Data (Last 7 Days)
        $chartData = [
            'labels' => [],
            'sales' => [],
            'purchases' => []
        ];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            $salesSum = Movement::where('type', 'sale')
                ->where('is_initial', false)
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->sum('total');
                
            $purchasesSum = Movement::where('type', 'purchase')
                ->where('is_initial', false)
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->sum('total');

            $chartData['labels'][] = $date->format('d/m');
            $chartData['sales'][] = $salesSum;
            $chartData['purchases'][] = $purchasesSum;
        }

        // 8. Cash in Box Calculation (Income - Expenses) - EXCLUDE INITIAL
        // 9. Previous Day Balance (Arqueo) - All cash history until yesterday + INITIAL CASH
        $initialCash = \App\Models\Setting::getInitialCash();
        $resetCashAt = \App\Models\Setting::getResetTimestamp('cash');

        $totalCashIncomeHistory = Movement::where('type', 'sale')
            ->where('is_initial', false)
            ->where('payment_method', 'cash')
            ->where('created_at', '>=', $resetCashAt) // Filter by reset
            ->whereDate('created_at', '<', $today)
            ->sum('total') 
            + CreditPayment::where('payment_method', 'cash')->where('created_at', '>=', $resetCashAt)->whereDate('created_at', '<', $today)->sum('amount')
            + SalePayment::whereHas('sale', function($q) use ($resetCashAt, $today) {
                $q->where('payment_method', 'mixed')->where('created_at', '>=', $resetCashAt)->whereDate('created_at', '<', $today);
            })->where('payment_method', 'cash')->sum('amount');
        
        $totalCashOutgoHistory = Expense::where('payment_method', 'cash')->where('expense_date', '>=', $resetCashAt)->whereDate('expense_date', '<', $today)->sum('amount') + 
            Movement::where('type', 'purchase')
            ->where('is_initial', false)
            ->where('payment_method', 'cash')
            ->where('created_at', '>=', $resetCashAt)
            ->whereDate('created_at', '<', $today)
            ->sum('total') + 
            \App\Models\AccountPayablePayment::where('payment_method', 'cash')
            ->where('payment_date', '>=', $resetCashAt)
            ->whereDate('payment_date', '<', $today)
            ->sum('amount');

        $previousDayBalance = $initialCash + $totalCashIncomeHistory - $totalCashOutgoHistory;

        // FINAL TOTAL CASH (Cumulative from reset)
        $totalCashIncomeAll = Movement::where('type', 'sale')->where('is_initial', false)->where('payment_method', 'cash')->where('created_at', '>=', $resetCashAt)->sum('total') 
            + CreditPayment::where('payment_method', 'cash')->where('created_at', '>=', $resetCashAt)->sum('amount')
            + SalePayment::whereHas('sale', function($q) use ($resetCashAt) {
                $q->where('payment_method', 'mixed')->where('created_at', '>=', $resetCashAt);
            })->where('payment_method', 'cash')->sum('amount');
        $totalCashOutgoAll = Expense::where('payment_method', 'cash')->where('expense_date', '>=', $resetCashAt)->sum('amount') 
            + Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'cash')->where('created_at', '>=', $resetCashAt)->sum('total')
            + \App\Models\AccountPayablePayment::where('payment_method', 'cash')->where('payment_date', '>=', $resetCashAt)->sum('amount');
        
        $totalCash = $initialCash + $totalCashIncomeAll - $totalCashOutgoAll;
        $cashInBoxToday = $totalCash - $previousDayBalance; // Simplified

        // NEW: Specific Cash Variables requested
        $cashSales = $directCashToday + $mixedCashToday;
        $paymentsToday = $paymentsTodayCash;
        $cashPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'cash')->whereDate('created_at', $today)->sum('total');
        $nequiPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'nequi')->whereDate('created_at', $today)->sum('total');
        $bancolombiaPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->whereIn('payment_method', ['bancolombia', 'bank', 'transfer'])->whereDate('created_at', $today)->sum('total');
        $creditPurchases = Movement::where('type', 'purchase')->where('is_initial', false)->where('payment_method', 'credit')->whereDate('created_at', $today)->sum('total');
        $cashPaymentsPaid = $paidPayablesCash; 

        // Payables Breakdown for Dashboard
        $paidPayablesNequi = $paidPayablesNequi_Today;
        $paidPayablesBancolombia = $paidPayablesBancolombia_Today;

        return view('dashboard', compact(
            'salesToday', 
            'salesTodayCash',
            'salesTodayTransfer',
            'paymentsTodayTransfer',
            'totalTransferToday',
            'salesTodayCredit',
            'cashInBoxToday',
            'previousDayBalance',
            'profitToday', 
            'receivables', 
            'payables',
            'expensesDay', 
            'lowStockProducts',
            'chartData',
            'paidPayablesToday',
            'collectedReceivablesToday',
            // New vars
            'cashSales',
            'paymentsToday',
            'cashPurchases',
            'nequiPurchases',
            'bancolombiaPurchases',
            'creditPurchases',
            'cashPaymentsPaid',
            'totalCash',
            // Granular Splits
            'totalNequiToday',
            'totalBancolombiaToday',
            'paidPayablesCash',
            'paidPayablesNequi',
            'paidPayablesBancolombia',
            'nequiBalance',
            'bancolombiaBalance',
            'expensesTodayCash',
            'expensesTodayNequi',
            'expensesTodayBancolombia',
            'incomeNequiToday',
            'incomeBancolombiaToday',
            'salesTodayNequi',
            'salesTodayBancolombia'
        ));
    }
}
