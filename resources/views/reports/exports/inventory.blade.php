<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #8B0000; color: #ffffff; }
        .title { font-size: 18px; font-weight: bold; color: #8B0000; text-align: center; }
        .low-stock { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="title">{{ \App\Models\Setting::getBusinessName() }} - Inventario Valorizado</div>
    <p><strong>Fecha Corte:</strong> {{ date('d/m/Y H:i') }}</p>
    <p><strong>Valor Total Inventario:</strong> ${{ number_format($totalValue, 0) }}</p>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Producto</th>
                <th>Stock</th>
                <th>Unidad</th>
                <th>Costo Unit.</th>
                <th>Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>{{ $product->sku }}</td>
                <td>{{ $product->name }}</td>
                <td class="{{ $product->stock <= $product->min_stock ? 'low-stock' : '' }}">
                    {{ $product->stock }}
                </td>
                <td>{{ $product->measure_type }}</td>
                <td>${{ number_format($product->cost_price, 0) }}</td>
                <td>${{ number_format($product->stock * $product->cost_price, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
