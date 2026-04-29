@extends('layouts.app')

@section('content')
<div style="display: flex; flex-direction: column; gap: 20px;">
    
    <!-- Top Header & Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="{{ route('reports.index') }}" class="btn" style="background: #f0f0f0; color: #555; border-radius: 12px; font-weight: 600;">⬅️ Volver</a>
            <h1 style="margin: 0; color: var(--color-primary-dark);">Reporte de Inventario</h1>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('reports.inventory.pdf') }}" class="btn btn-danger" style="display: flex; align-items: center; gap: 8px; background-color: #2fca75ff; border-color: #2fca75ff;">
                📄 Descargar PDF
            </a>
            <a href="{{ route('reports.inventory.export') }}" class="btn btn-success" style="display: flex; align-items: center; gap: 8px; background-color: #2fca75ff; border-color: #2fca75ff;">
                📊 Exportar Excel
            </a>
            <a href="{{ route('reports.inventory.barcodes') }}" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px; background-color: #333; border-color: #333;">
                🏷️ Catálogo Códigos
            </a>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="card" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; border-radius: 20px; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);">
        <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; padding: 25px;">
            <div>
                <h3 style="margin: 0; color: rgba(255,255,255,0.8); font-size: 1.1rem;">Valor Total del Inventario (Costo)</h3>
                <small style="color: rgba(255,255,255,0.6);">Suma de (Stock * Precio Costo)</small>
            </div>
            <div style="font-size: 2.5rem; font-weight: 800;">
                ${{ number_format($totalValue, 0) }}
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table class="table table-hover" style="margin: 0;">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th style="padding: 20px;">Codigo</th>
                        <th style="padding: 20px;">Producto</th>
                        <th style="padding: 20px;">Stock Actual</th>
                        <th style="padding: 20px;">Costo Unit.</th>
                        <th style="padding: 20px;">Precio Venta</th>
                        <th style="padding: 20px; text-align: right;">Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 15px 20px;">
                            <div style="font-weight: 500;">{{ $product->sku }}</div>
                            <svg class="barcode"
                                 jsbarcode-format="CODE128"
                                 jsbarcode-value="{{ $product->sku }}"
                                 jsbarcode-textmargin="0"
                                 jsbarcode-fontoptions="bold"
                                 jsbarcode-displayValue="false"
                                 style="width: 100%; max-width: 150px; height: 40px; display: block;">
                            </svg>
                        </td>
                        <td style="padding: 15px 20px;">{{ $product->name }}</td>
                        <td style="padding: 15px 20px;">
                            <span style="padding: 5px 10px; border-radius: 10px; background: {{ $product->stock <= $product->min_stock ? '#ffebee' : '#e8f5e9' }}; color: {{ $product->stock <= $product->min_stock ? '#c62828' : '#2e7d32' }}; font-weight: bold;">
                                {{ $product->stock }} {{ $product->measure_type }}
                            </span>
                        </td>
                        <td style="padding: 15px 20px;">${{ number_format($product->cost_price, 0) }}</td>
                        <td style="padding: 15px 20px;">${{ number_format($product->sale_price, 0) }}</td>
                        <td style="padding: 15px 20px; text-align: right; font-weight: bold;">${{ number_format($product->stock * $product->cost_price, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        JsBarcode(".barcode").init();
    });
</script>
@endsection
