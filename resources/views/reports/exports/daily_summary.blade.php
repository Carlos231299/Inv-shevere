<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1976d2; font-size: 18px; }
        .header p { margin: 5px 0; color: #666; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; color: #555; text-transform: uppercase; font-size: 9px; }
        
        .section-title { background: #1976d2; color: white; padding: 5px 10px; font-weight: bold; margin-bottom: 10px; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .arqueo-table { border: 2px solid #1976d2; background: #fefefe; }
        .arqueo-table td { font-size: 12px; }
        .arqueo-header { background: #1976d2; color: white; font-weight: bold; }
        
        .label { color: #666; }
        .total-box { background: #e3f2fd; padding: 10px; border-radius: 5px; margin-top: 10px; }
        .total-amount { font-size: 16px; font-weight: bold; color: #1976d2; }
        
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .badge-cash { background: #e8f5e9; color: #2e7d32; }
        .badge-bank { background: #e3f2fd; color: #1976d2; }
        .badge-credit { background: #fff3e0; color: #e65100; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RESUMEN DE CIERRE DE CAJA</h1>
        <p>Carnicería Salomé - Cierre Diario</p>
        <p>Fecha: {{ $dateStr }}</p>
    </div>

    <!-- ARQUEO DE CAJA SECTION -->
    <div class="section-title">CONTROL DE EFECTIVO (CIERRE DE CAJA)</div>
    <table class="arqueo-table" style="margin-bottom: 30px;">
        <tr>
            <td width="70%" style="padding: 10px;">SALDO ANTERIOR (Efectivo en caja al iniciar el día)</td>
            <td class="text-right" style="padding: 10px;"><strong>$ {{ number_format($previousDayBalance, 0) }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 10px;">(+) Ventas del Día (Ingresos en efectivo)</td>
            <td class="text-right" style="color: #2e7d32; padding: 10px;">+ $ {{ number_format($cashSales, 0) }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;">(+) Abonos de Clientes recibidos hoy (Efectivo)</td>
            <td class="text-right" style="color: #2e7d32; padding: 10px;">+ $ {{ number_format($paymentsToday, 0) }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;">(-) Gastos del Día (Pagados en efectivo)</td>
            <td class="text-right" style="color: #d32f2f; padding: 10px;">- $ {{ number_format($expensesToday, 0) }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;">(-) Compras del Día (Pagadas de contado hoy)</td>
            <td class="text-right" style="color: #d32f2f; padding: 10px;">- $ {{ number_format($cashPurchases, 0) }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;">(-) Abonos a Proveedores realizados hoy (Efectivo)</td>
            <td class="text-right" style="color: #d32f2f; padding: 10px;">- $ {{ number_format($cashPaymentsPaid, 0) }}</td>
        </tr>
        <tr class="arqueo-header">
            <td style="padding: 12px; font-size: 14px;">EFECTIVO TOTAL QUE DEBE HABER EN CAJA</td>
            <td class="text-right" style="padding: 12px; font-size: 14px;">$ {{ number_format($previousDayBalance + $cashSales + $paymentsToday - $expensesToday - $cashPurchases - $cashPaymentsPaid, 0) }}</td>
        </tr>
    </table>

    <!-- CONTROL DE BANCOS SECTION -->
    <div class="section-title" style="background: #1976d2; margin-top: 15px;">CONTROL DE CUENTAS / BANCOS (TRANSFERENCIAS)</div>
    <table class="arqueo-table" style="border-color: #1976d2;">
        <tr>
            <td width="70%">(+) Ventas del Día (Transferencias)</td>
            <td class="text-right" style="color: #1565c0;">+ $ {{ number_format($transferSales, 0) }}</td>
        </tr>
        <tr>
            <td>(+) Abonos recibidos hoy (Transferencias)</td>
            <td class="text-right" style="color: #1565c0;">+ $ {{ number_format($bankPayments, 0) }}</td>
        </tr>
        <tr>
            <td>(-) Abonos pagados hoy (Transferencias)</td>
            <td class="text-right" style="color: #d32f2f;">- $ {{ number_format($bankPaymentsPaid, 0) }}</td>
        </tr>
        <tr class="arqueo-header" style="background: #1976d2;">
            <td>TOTAL QUE DEBE HABER EN CUENTA (DEL DÍA)</td>
            <td class="text-right">$ {{ number_format($transferSales + $bankPayments - $bankPaymentsPaid, 0) }}</td>
        </tr>
    </table>

    <!-- DETALLE DE VENTAS -->
    <div class="section-title">DETALLE DE VENTAS (1 A 1)</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Hora</th>
                <th>Cliente</th>
                <th>Método</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesToday as $sale)
            <tr>
                <td>{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $sale->created_at->format('h:i A') }}</td>
                <td>{{ $sale->client->name ?? 'Consumidor Final' }}</td>
                <td class="text-center">
                    @php
                        $methodName = str_replace(['cash', 'bank', 'transfer', 'credit'], ['EFECTIVO', 'TRANSF.', 'TRANSF.', 'CRÉDITO'], $sale->payment_method);
                        $badgeClass = match($sale->payment_method) {
                            'cash' => 'badge-cash',
                            'credit' => 'badge-credit',
                            default => 'badge-bank'
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">
                        {{ $methodName }}
                    </span>
                </td>
                <td class="text-right">$ {{ number_format($sale->total_amount, 0) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center">No hubo ventas hoy.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #fafafa; font-weight: bold;">
                <td colspan="4" class="text-right">TOTAL VENTAS:</td>
                <td class="text-right">$ {{ number_format($totalSales, 0) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- DETALLE DE GASTOS Y COMPRAS -->
    <div style="display: table; width: 100%;">
        <div style="display: table-cell; width: 48%; vertical-align: top; padding-right: 2%;">
            <div class="section-title">GASTOS VARIOS</div>
            <table>
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expensesTodayList as $expense)
                    <tr>
                        <td>{{ $expense->description }}</td>
                        <td class="text-right">$ {{ number_format($expense->amount, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-center">No hubo gastos hoy.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display: table-cell; width: 48%; vertical-align: top; padding-left: 2%;">
            <div class="section-title">COMPRAS DEL DÍA</div>
            <table>
                <thead>
                    <tr>
                        <th>Prov. / Detalle</th>
                        <th>Método</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchasesToday as $purchase)
                    @php
                        $productNames = $purchase->movements->map(function($mov) {
                            return $mov->product->name ?? 'Producto Desconocido';
                        })->unique()->implode(', ');
                        
                        $pm = $purchase->movements->first()->payment_method ?? 'cash';
                    @endphp
                    <tr>
                        <td>
                            <strong style="font-size: 10px; display: block;">{{ $productNames }}</strong>
                            <small style="color: #666; font-size: 8px;">Prov: {{ $purchase->provider->name ?? 'Sin Prov.' }}</small>
                        </td>
                        <td class="text-center" style="font-size: 9px;">
                            {{ str_replace(['cash', 'bank', 'transfer', 'credit'], ['EFE', 'TRA', 'TRA', 'CRE'], $pm) }}
                        </td>
                        <td class="text-right">$ {{ number_format($purchase->total_amount, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center">No hubo compras hoy.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- DETALLE DE ABONOS -->
    <div style="display: table; width: 100%; margin-top: 10px;">
        <div style="display: table-cell; width: 48%; vertical-align: top; padding-right: 2%;">
            @if($paymentsTodayList->count() > 0)
            <div class="section-title">ABONOS RECIBIDOS (Ventas)</div>
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Método</th>
                        <th class="text-right">Abono</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentsTodayList as $payment)
                    <tr>
                        <td>{{ $payment->credit->client->name ?? 'N/A' }}</td>
                        <td class="text-center" style="font-size: 9px;">{{ $payment->payment_method == 'cash' ? 'EFE' : 'TRA' }}</td>
                        <td class="text-right">$ {{ number_format($payment->amount, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
        <div style="display: table-cell; width: 48%; vertical-align: top; padding-left: 2%;">
            @if($purchasePaymentsTodayList->count() > 0)
            <div class="section-title">ABONOS REALIZADOS (Compras)</div>
            <table>
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Método</th>
                        <th class="text-right">Abono</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchasePaymentsTodayList as $payment)
                    <tr>
                        <td>{{ $payment->accountPayable->provider->name ?? 'N/A' }}</td>
                        <td class="text-center" style="font-size: 9px;">{{ $payment->payment_method == 'cash' ? 'EFE' : 'TRA' }}</td>
                        <td class="text-right">$ {{ number_format($payment->amount, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; color: #999; font-size: 9px; text-align: center;">
        Este documento es un registro oficial de movimientos diarios para Carnicería Salomé.
    </div>
</body>
</html>
