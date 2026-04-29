@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Nuevo Producto</h2>
        <a href="{{ route('products.index') }}" class="btn" style="background: #f0f0f0; color: #555; border-radius: 12px; font-weight: 600;">⬅️ Volver</a>
    </div>

    @if ($errors->any())
        <div style="background: #ffe6e6; border: 1px solid red; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
            <ul style="margin-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('products.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="sku">Código de Barras / SKU *</label>
            <input type="text" name="sku" id="sku" class="form-control" value="{{ old('sku') }}" required autofocus autocomplete="off">
            <small style="color: #666;">Usa el lector de código de barras o escribe uno manualmente.</small>
        </div>

        <div class="form-group">
            <label for="name">Nombre del Producto *</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autocomplete="off">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="measure_type">Tipo de Medida *</label>
                <select name="measure_type" id="measure_type" class="form-control" required>
                    <option value="kg" {{ old('measure_type') == 'kg' ? 'selected' : '' }}>Kilogramo (Kg)</option>
                    <option value="unit" {{ old('measure_type') == 'unit' ? 'selected' : '' }}>Unidad</option>
                </select>
            </div>

            <div class="form-group">
                <label for="min_stock">Stock Mínimo (Alerta) *</label>
                <input type="number" step="0.001" name="min_stock" id="min_stock" class="form-control" value="{{ old('min_stock', 1) }}" required autocomplete="off">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="cost_price">Precio de Compra ($) *</label>
                <input type="text" name="cost_price" id="cost_price" class="form-control" value="{{ old('cost_price') }}" required autocomplete="off" placeholder="0.000,00" onblur="formatCurrency(this)">
            </div>

            <div class="form-group">
                <label for="sale_price">Precio de Venta ($) *</label>
                <input type="text" name="sale_price" id="sale_price" class="form-control" value="{{ old('sale_price') }}" required autocomplete="off" placeholder="0.000,00" onblur="formatCurrency(this)">
            </div>
        </div>

        <script>
            function formatCurrency(input) {
                let value = input.value;
                if(!value) return;
                
                // Remove formatting to parse
                let clean = value.replace(/\./g, '').replace(',', '.');
                if(!isNaN(clean) && clean !== '') {
                    // Format back to Es-CO
                    input.value = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(clean);
                }
            }
        </script>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn btn-primary">Guardar Producto</button>
        </div>
    </form>
</div>
@endsection
