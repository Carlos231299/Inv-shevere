<div class="product-detail">
    <div class="row mb-3" style="display: flex; gap: 20px; margin-bottom: 20px;">
        <div style="flex: 1;">
            <p style="margin: 5px 0;"><strong>SKU:</strong> {{ $product->sku }}</p>
            <p style="margin: 5px 0;"><strong>Nombre:</strong> {{ $product->name }}</p>
            <p style="margin: 5px 0;"><strong>Stock Actual:</strong> {{ $product->stock }} {{ $product->measure_type }}</p>
        </div>
        <div style="flex: 1;">
            <p style="margin: 5px 0;"><strong>Precio Compra:</strong> ${{ number_format($product->cost_price, 0) }}</p>
            <p style="margin: 5px 0;"><strong>Precio Promedio de Venta:</strong> ${{ number_format($product->average_sale_price, 0) }}</p>
            <p style="margin: 5px 0;"><strong>Precio Venta:</strong> ${{ number_format($product->sale_price, 0) }}</p>
        </div>
    </div>

    <h5 style="border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-top: 20px;">Lotes / Entradas Activas</h5>
    <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Lote</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Fecha</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Cant. Inicial</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Cant. Actual</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Costo</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Venta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                <tr>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">{{ $batch->batch_number }}</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">{{ $batch->created_at->format('d/m/Y') }}</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">{{ $batch->initial_quantity }}</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">{{ $batch->current_quantity }}</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">${{ number_format($batch->cost_price, 0) }}</td>
                    <td style="padding: 10px; border: 1px solid #dee2e6;">${{ number_format($batch->sale_price, 0) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 20px; text-align: center; border: 1px solid #dee2e6;">No hay lotes activos.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
