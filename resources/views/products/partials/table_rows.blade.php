@forelse($products as $product)
<tr>
    <td>{{ $product->sku }}</td>
    <td>{{ $product->name }}</td>
    <td>
        @if($product->measure_type == 'kg')
            <span class="badge" style="background: #e3f2fd; color: #0d47a1; padding: 4px 8px; border-radius: 4px;">Kilogramo</span>
        @else
            <span class="badge" style="background: #e8f5e9; color: #1b5e20; padding: 4px 8px; border-radius: 4px;">Unidad</span>
        @endif
    </td>
    <td>$ {{ number_format($product->cost_price, 0) }}</td>
    <td>$ {{ number_format($product->average_sale_price, 0) }}</td>
    <td>$ {{ number_format($product->sale_price, 0) }}</td>
    <td>
        @if($product->stock <= $product->min_stock)
            <span style="color: var(--color-danger); font-weight: bold;">
                {{ $product->stock }} (Bajo)
            </span>
        @else
             {{ $product->stock }}
        @endif
    </td>
    <td>
        @if($product->status == 'active')
            <span style="color: var(--color-success);">Activo</span>
        @else
            <span style="color: var(--color-gray-dark);">Inactivo</span>
        @endif
    </td>
    <td>
        <div style="display: flex; gap: 5px;">
            <a href="{{ route('products.show', $product->sku) }}" class="btn btn-info open-modal" data-title="Detalle de Producto: {{ $product->name }}" style="background: #17a2b8; color: white; padding: 5px 10px; font-size: 0.8rem;">Ver</a>
            
            @if(auth()->user()->role === 'admin')
            <button class="btn btn-sm btn-secondary adjust-stock-btn" 
                data-sku="{{ $product->sku }}" 
                data-name="{{ $product->name }}" 
                data-stock="{{ $product->stock }}"
                style="background: #6c757d; color: white; padding: 5px 10px; font-size: 0.8rem;"
                title="Ajuste de Inventario (Silencioso)">
                Ajustar
            </button>    

            <a href="{{ route('products.edit', $product->sku) }}" class="btn open-modal" data-title="Editar Producto: {{ $product->name }}" style="background: var(--color-warning); padding: 5px 10px; font-size: 0.8rem;">Editar</a>
            
            <form id="delete-product-{{ $product->sku }}" action="{{ route('products.destroy', $product->sku) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="button" class="btn" style="background: var(--color-danger); color: white; padding: 5px 10px; font-size: 0.8rem;" onclick="confirmFormSubmit('delete-product-{{ $product->sku }}')">Eliminar</button>
            </form>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="{{ auth()->user()->role === 'admin' ? '8' : '7' }}" style="text-align: center; padding: 20px;">No hay productos registrados.</td>
</tr>
@endforelse
<tr>
    <td colspan="{{ auth()->user()->role === 'admin' ? '8' : '7' }}">
        <div class="custom-pagination">
            {{ $products->appends(request()->query())->links() }}
        </div>
    </td>
</tr>
