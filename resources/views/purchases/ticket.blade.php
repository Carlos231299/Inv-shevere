<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Compra</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <style>
        html {
            background: #eee;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 20px auto;
            padding: 10px;
            width: 76mm;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            min-height: 200px;
        }
        @media print {
            html, body {
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
            .no-print { display: none; }
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        
        .header { margin-bottom: 10px; }
        .items { margin-bottom: 10px; }
        
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
        <h3 style="margin: 0;">CARNICERÍA SALOMÉ</h3>
        <div>NIT: 9015980377</div>
        <div class="line"></div>
        <div class="bold">COMPROBANTE DE COMPRA #{{ str_pad($purchase->id, 6, '0', STR_PAD_LEFT) }}</div>
        <div>Fecha: {{ $purchase->created_at->format('d/m/Y h:i A') }}</div>
        <div>Proveedor: {{ $purchase->provider ? $purchase->provider->name : 'General' }}</div>
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
                $groupedMovements = $purchase->movements->groupBy('product_sku')->map(function ($items) {
                    return [
                        'name' => $items->first()->product->name ?? 'Producto Borrado',
                        'quantity' => $items->sum('quantity'),
                        'measure_type' => $items->first()->product->measure_type ?? 'Und',
                        'price' => $items->first()->cost_at_moment, // Purchase uses Cost
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
        <tr style="font-size: 14px;">
            <td class="bold">TOTAL COMPRA:</td>
            <td class="text-right bold">${{ number_format($purchase->total_amount, 0) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="line"></td>
        </tr>
        <tr>
            <td>Método Pago:</td>
            <td class="text-right">
                @php
                    // Purchase might have payment method on first movement if not structured in header
                    $pm = 'N/A';
                    if ($purchase->movements->isNotEmpty()) {
                        $pm = $purchase->movements->first()->payment_method ?? 'cash';
                    }
                    
                    $ap = \App\Models\AccountPayable::where('description', "Compra #{$purchase->id}")->first();
                    $isPartial = $ap && $ap->paid_amount > 0 && ($ap->amount - $ap->paid_amount > 0);

                    if ($isPartial) {
                        echo "Crédito (Abono)";
                    } else {
                        $methods = [
                            'cash' => 'Efectivo',
                            'credit' => 'Crédito',
                            'transfer' => 'Transferencia',
                            'bank' => 'Transferencia',
                            'card' => 'Tarjeta'
                        ];
                        echo $methods[$pm] ?? ucfirst($pm);
                    }
                @endphp
            </td>
        </tr>

        @if($ap)
        <tr>
            <td>Abono Inicial:</td>
            <td class="text-right">${{ number_format($ap->paid_amount, 0) }}</td>
        </tr>
        <tr>
            <td class="bold">SALDO PENDIENTE:</td>
            <td class="text-right bold">${{ number_format($ap->amount - $ap->paid_amount, 0) }}</td>
        </tr>
        @endif
    </table>

    <div class="text-center" style="margin-top: 20px;">
        <div class="line"></div>
        <p style="margin: 5px 0;">CONSTANCIA DE INGRESO</p>
    </div>

    <script>
        // Auto-print logic
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
