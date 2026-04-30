<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #8B0000;
            color: #ffffff;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #8B0000;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="title">{{ \App\Models\Setting::getBusinessName() }} - Historial de Compras y Ventas</div>
    <p>
        <strong>Generado:</strong> {{ date('d/m/Y H:i') }}<br>
        <strong>Periodo:</strong> {{ $startDate }} al {{ $endDate }}
    </p>

    <table>
        <thead>
            <tr>
                <th>N° Factura</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio Und.</th>
                <th>Total</th>
                <th>Método</th>
                <th>Cliente / Prov.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td>
                        #{{ str_pad($t->id, 6, '0', STR_PAD_LEFT) }}
                    </td>
                    <td>{{ $t->type == 'sale' ? 'Venta' : 'Compra' }}</td>
                    <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @php
                            $productCount = $t->movements->count();
                            $firstProduct = $t->movements->first()->product->name ?? 'N/A';
                        @endphp
                        {{ $firstProduct }} @if($productCount > 1) (+{{ $productCount - 1 }} más) @endif
                    </td>
                    <td>{{ $t->movements->sum('quantity') }}</td>
                    <td>-</td>
                    <td>${{ number_format($t->total_amount, 0) }}</td>
                    <td>
                        @if($t->type == 'sale')
                            @if($t->payment_method == 'cash') Efectivo
                            @elseif($t->payment_method == 'bank') Transf.
                            @elseif($t->payment_method == 'credit') Crédito
                            @else {{ $t->payment_method }} @endif
                        @else
                            @php
                                $pm = $t->movements->first()->payment_method ?? 'N/A';
                                $pmLabel = 'N/A';
                                if ($pm == 'cash')
                                    $pmLabel = 'Efectivo';
                                elseif ($pm == 'bank' || $pm == 'transfer')
                                    $pmLabel = 'Transf.';
                                elseif ($pm == 'credit')
                                    $pmLabel = 'Crédito';
                                else
                                    $pmLabel = ucfirst($pm);
                            @endphp
                            {{ $pmLabel }}
                        @endif
                    </td>
                    <td>{{ $t->type == 'sale' ? ($t->client->name ?? 'Consumidor Final') : ($t->provider->name ?? 'Proveedor') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>