<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Factura de Venta</title>
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <style>
        html {
            background: #eee;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            font-weight: normal;
            color: #222;
            margin: 20px auto;
            padding: 10px;
            width: 76mm;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            min-height: 200px;
        }

        @media print {

            html,
            body {
                background: none;
                margin: 0;
                padding: 0;
                width: 76mm;
                box-shadow: none;
            }

            @page {
                margin: 0;
                size: 80mm auto;
            }

            .no-print {
                display: none;
            }
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: 600;
        }

        .line {
            border-top: 1px dashed #222;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
        }

        .header {
            margin-bottom: 10px;
        }

        .items {
            margin-bottom: 10px;
        }

        .btn-print {
            padding: 10px 20px;
            background: #333;
            color: #fff;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <button class="btn-print no-print" onclick="window.print()">🖨️ IMPRIMIR</button>

    <div class="header text-center">
        <div style="border: 1.5px solid #222; display: inline-block; padding: 4px 12px; margin-bottom: 6px;">
            <div style="font-size: 20px; font-weight: 600; letter-spacing: 1px;">$HEVERE</div>
            <div style="font-size: 9px; letter-spacing: 0.5px;">HOGAR &middot; CANASTA FAMILIAR &middot; MÁS</div>
        </div>
        <br>
        <br>
        <div class="bold" style="font-size: 13px;">{{ \App\Models\Setting::getBusinessName() }}</div>
        <div style="font-size: 11px;">NIT: {{ \App\Models\Setting::getBusinessNit() }}</div>
        <div style="font-size: 11px;">Dirección: {{ \App\Models\Setting::getBusinessAddress() }}</div>
        <div class="bold" style="font-size: 11px;">Domicilios: {{ \App\Models\Setting::getBusinessPhone() }}</div>
        <div class="line"></div>
        <div class="bold">FACTURA DE VENTA #{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</div>
        <div>Fecha: {{ $sale->created_at->format('d/m/Y h:i A') }}</div>
        <div class="bold">Cliente: {{ $sale->client ? $sale->client->name : 'Consumidor Final' }}</div>
    </div>



    <div class="line"></div>

    <table class="items">
        <thead>
            <tr style="text-align: left;">
                <th colspan="2">Desc.</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $groupedMovements = $sale->movements->groupBy('product_sku')->map(function ($items) {
                    return [
                        'name' => $items->first()->product->name ?? 'Producto Borrado',
                        'quantity' => $items->sum('quantity'),
                        'measure_type' => $items->first()->product->measure_type ?? 'Und',
                        'price' => $items->first()->price_at_moment,
                        'total' => $items->sum('total')
                    ];
                });
            @endphp

            @foreach($groupedMovements as $item)
                <tr>
                    <td colspan="3">
                        {{ $item['name'] }}
                        <br>
                        {{ $item['quantity'] }} {{ $item['measure_type'] }} x ${{ number_format($item['price'], 0) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td class="text-right">${{ number_format($item['total'], 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <table style="margin-top: 5px; width: 100%;">
        @php
            $subtotal = $sale->total_amount + $sale->discount;
        @endphp

        <tr>
            <td class="bold">Subtotal:</td>
            <td class="text-right">${{ number_format($subtotal, 0) }}</td>
        </tr>

        @if($sale->discount > 0)
            <tr>
                <td>Descuento:</td>
                <td class="text-right">-${{ number_format($sale->discount, 0) }}</td>
            </tr>
        @endif

        <tr style="font-size: 16px;">
            <td class="bold">TOTAL A PAGAR:</td>
            <td class="text-right bold">${{ number_format($sale->total_amount, 0) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="line"></td>
        </tr>
        <tr>
            <td>Método Pago:</td>
            <td class="text-right">
                @php
                    $isMixed = $sale->payment_method === 'mixed' || $sale->salePayments->count() > 1;
                    $isPartial = $sale->credit && $sale->credit->paid_amount > 0 && ($sale->total_amount - $sale->credit->paid_amount > 0);
                    $methods = [
                        'cash' => 'Efectivo',
                        'credit' => 'Crédito',
                        'transfer' => 'Transferencia',
                        'bank' => 'Transf.',
                        'card' => 'Tarjeta',
                        'nequi' => 'Nequi',
                        'bancolombia' => 'Bancolombia'
                    ];

                    if ($isPartial) {
                        // For partials, we might want to know HOW the abono was paid
                        $subMethod = '';
                        if ($sale->credit->payments->count() === 1) {
                            $m = $sale->credit->payments->first()->payment_method;
                            $subMethod = " (" . ($methods[$m] ?? ucfirst($m)) . ")";
                        } elseif ($sale->credit->payments->count() > 1) {
                            $subMethod = " (Mixto)";
                        }
                        echo "Crédito" . $subMethod;
                    } elseif ($isMixed) {
                        echo "<strong>Mixto</strong>";
                    } else {
                        echo $methods[$sale->payment_method] ?? ucfirst($sale->payment_method);
                    }
                @endphp
            </td>
        </tr>

        @if($sale->payment_method === 'mixed' || $sale->salePayments->count() > 1)
            @foreach($sale->salePayments as $payment)
                <tr>
                    <td style="padding-left: 10px;">-
                        {{ $payment->payment_method === 'cash' ? 'Efectivo' : ucfirst($payment->payment_method) }}:
                    </td>
                    <td class="text-right">${{ number_format($payment->amount, 0) }}</td>
                </tr>
            @endforeach
        @endif

        @if($sale->credit)
            <tr>
                <td>Abono Inicial:</td>
                <td class="text-right">${{ number_format($sale->credit->paid_amount, 0) }}</td>
            </tr>
            <tr>
                <td class="bold">SALDO PENDIENTE:</td>
                <td class="text-right bold">${{ number_format($sale->total_amount - $sale->credit->paid_amount, 0) }}</td>
            </tr>
        @endif

        {{-- Only show Received/Change for non-credit, non-mixed Cash sales --}}
        @if($sale->payment_method === 'cash' && !$sale->credit && !$isMixed)
            <tr>
                <td>Recibido:</td>
                <td class="text-right">${{ number_format($sale->received_amount, 0) }}</td>
            </tr>
            <tr>
                <td>Cambio:</td>
                <td class="text-right">${{ number_format($sale->change_amount, 0) }}</td>
            </tr>
        @endif
    </table>

    <div class="text-center" style="margin-top: 20px;">
        <div class="line"></div>
        <p style="margin: 5px 0; font-weight: 700;">¡GRACIAS POR SU COMPRA!</p>
        <p style="font-size: 9px; font-weight: normal;">Desarrollado por:</p>
        <p style="font-size: 9px; font-weight: normal;">Ing. Carlos Bastidas & Ing. Jarlin Esquivel</p>
        <p style="font-size: 9px; font-weight: normal;">304 218 9080 / 300 487 9915</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>

</html>