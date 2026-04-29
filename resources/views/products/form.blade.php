<form action="{{ isset($product) ? route('products.update', $product->sku) : route('products.store') }}" method="POST">
    @csrf
    @if(isset($product))
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="sku">Código de Barras / SKU *</label>
        <div style="display: flex; gap: 10px;">
             <input type="text" name="sku" id="sku" class="form-control" 
               value="{{ old('sku', $product->sku ?? '') }}" 
               required {{ !isset($product) ? 'autofocus' : '' }} autocomplete="off">
             <button type="button" class="btn btn-secondary" onclick="generateSku()" style="white-space: nowrap;">⚡ Generar</button>
        </div>
       
        <small style="color: #666;">Usa el lector de código de barras o escribe uno manualmente.</small>
    </div>

    <div class="form-group">
        <label for="name">Nombre del Producto *</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $product->name ?? '') }}" required autocomplete="off">
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group">
            <label for="measure_type">Tipo de Medida *</label>
            <select name="measure_type" id="measure_type" class="form-control" required>
                <option value="kg" {{ old('measure_type', $product->measure_type ?? '') == 'kg' ? 'selected' : '' }}>Kilogramo (Kg)</option>
                <option value="unit" {{ old('measure_type', $product->measure_type ?? '') == 'unit' ? 'selected' : '' }}>Unidad</option>
            </select>
        </div>

        <div class="form-group">
            <label for="min_stock">Stock Mínimo (Alerta) *</label>
            <input type="number" step="0.001" name="min_stock" id="min_stock" class="form-control" value="{{ old('min_stock', $product->min_stock ?? 1) }}" required autocomplete="off">
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
        <div class="form-group">
            <label for="cost_price">Compra ($)</label>
            <input type="number" step="0.01" name="cost_price" id="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price ?? '') }}" required autocomplete="off">
        </div>

        @if(isset($product))
        <div class="form-group">
            <label for="average_sale_price">Precio Promedio de Venta ($)</label>
            <input type="number" step="0.01" name="average_sale_price" id="average_sale_price" class="form-control" value="{{ old('average_sale_price', $product->average_sale_price ?? $product->cost_price ?? '') }}" readonly style="background-color: #e9ecef; cursor: not-allowed;">
            <small class="text-muted">Calculado automáticamente por el sistema.</small>
        </div>
        @endif

        <div class="form-group">
            <label for="sale_price">Venta ($)</label>
            <input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control" value="{{ old('sale_price', $product->sale_price ?? '') }}" required autocomplete="off">
        </div>
    </div>

    @if(isset($product))
    <div class="form-group">
        <label for="status">Estado</label>
        <select name="status" id="status" class="form-control">
            <option value="active" {{ ($product->status ?? '') == 'active' ? 'selected' : '' }}>Activo</option>
            <option value="inactive" {{ ($product->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>
    @endif

    <div style="margin-top: 20px; text-align: right;">
        <button type="submit" class="btn btn-primary">{{ isset($product) ? 'Actualizar Producto' : 'Guardar Producto' }}</button>
    </div>
</form>
