@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Editar Producto</h2>
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

    <form action="{{ route('products.update', $product->sku) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label>Código de Barras / SKU</label>
            <input type="text" class="form-control" value="{{ $product->sku }}" disabled style="background-color: #f0f0f0;">
            <small>El código no se puede editar una vez creado.</small>
        </div>

        <div class="form-group">
            <label for="name">Nombre del Producto *</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $product->name) }}" required autocomplete="off">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="measure_type">Tipo de Medida *</label>
                <select name="measure_type" id="measure_type" class="form-control" required>
                    <option value="kg" {{ old('measure_type', $product->measure_type) == 'kg' ? 'selected' : '' }}>Kilogramo (Kg)</option>
                    <option value="unit" {{ old('measure_type', $product->measure_type) == 'unit' ? 'selected' : '' }}>Unidad</option>
                </select>
            </div>

            <div class="form-group">
                <label for="min_stock">Stock Mínimo (Alerta) *</label>
                <input type="number" step="0.001" name="min_stock" id="min_stock" class="form-control" value="{{ old('min_stock', $product->min_stock) }}" required autocomplete="off">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="cost_price">Precio de Compra ($) *</label>
                <input type="number" step="0.01" name="cost_price" id="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price) }}" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="sale_price">Precio de Venta ($) *</label>
                <input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control" value="{{ old('sale_price', $product->sale_price) }}" required autocomplete="off">
            </div>
        </div>

        <div class="form-group">
            <label for="status">Estado</label>
            <select name="status" id="status" class="form-control">
                <option value="active" {{ $product->status == 'active' ? 'selected' : '' }}>Activo</option>
                <option value="inactive" {{ $product->status == 'inactive' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn btn-primary">Actualizar Producto</button>
        </div>
    </form>
</div>
@endsection
