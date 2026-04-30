<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Compra</title>
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
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
        <!-- Logo Textual Monocromático para Impresora Térmica -->
        <div style="border: 2px solid #000; padding: 6px; margin: 0 auto 10px auto; display: inline-block; min-width: 60%;">
            <div style="font-size: 24px; font-weight: 900; font-family: 'Arial Black', sans-serif; line-height: 1;">
                <span style="font-size: 28px;">$</span>HEVERE
            </div>
            <div style="font-size: 7.5px; font-weight: bold; letter-spacing: 0.5px; border-top: 2px solid #000; margin-top: 4px; padding-top: 4px;">
                HOGAR &middot; CANASTA FAMILIAR &middot; MÁS
            </div>
        </div>

        <div style="font-size: 12px; font-weight: bold; margin-bottom: 2px;">{{ \App\Models\Setting::getBusinessName() }}</div>
        <div style="font-size: 11px;">NIT: {{ \App\Models\Setting::getBusinessNit() }}</div>
        <div class="bold" style="font-size: 11px; margin-top: 2px;">Soporte de Entrada</div>

        <div class="line"></div>
        <div class="bold" style="padding: 5px; margin-top: 5px; border: 1px solid #000; text-align: center;">COMPROBANTE DE COMPRA #{{ str_pad($purchase->id, 6, '0', STR_PAD_LEFT) }}</div>
        <div style="font-size: 11px; margin-top: 5px;">Fecha: {{ $purchase->created_at->format('d/m/Y h:i A') }}</div>
        <div class="bold" style="margin-top: 2px;">Proveedor: {{ $purchase->provider ? $purchase->provider->name : 'General' }}</div>
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
