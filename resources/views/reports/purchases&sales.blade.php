@extends('layouts.app')

@section('content')
    <!-- Top Header & Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="{{ route('reports.index') }}" class="btn" style="background: #f0f0f0; color: #555; border-radius: 12px; font-weight: 600;">⬅️ Volver</a>
            <h1 style="margin: 0; color: var(--color-primary-dark);">Reporte de Compras y Ventas</h1>
        </div>
        <div style="display: flex; gap: 10px;">
        </div>
    </div>

<div class="card">
    <div class="card-body">
        
        <form action="{{ route('reports.purchases&sales') }}" method="GET" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Tipo:</label>
                <select name="type" class="form-control" style="background-color: #217346; color: white; border-color: #217346;">
                    <option value="all" {{ ($type ?? 'all') == 'all' ? 'selected' : '' }} style="background-color: white; color: black;">Todos</option>
                    <option value="sale" {{ ($type ?? 'all') == 'sale' ? 'selected' : '' }} style="background-color: white; color: black;">Ventas</option>
                    <option value="purchase" {{ ($type ?? 'all') == 'purchase' ? 'selected' : '' }} style="background-color: white; color: black;">Compras</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Desde:</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" autocomplete="off">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Hasta:</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-success" style="background-color: #217346; border-color: #217346;">🔍 Filtrar</button>
            <button type="button" onclick="window.location.href='{{ route('reports.purchases&sales.pdf') }}' + window.location.search" class="btn btn-danger" style="background-color: #dc3545; border-color: #dc3545;">📄 Exportar PDF</button>
            <button type="button" onclick="window.location.href='{{ route('reports.purchases&sales.export') }}' + window.location.search" class="btn btn-success" style="background-color: #217346; border-color: #217346;">📊 Exportar Excel</button>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const startInput = document.querySelector('input[name="start_date"]');
                const endInput = document.querySelector('input[name="end_date"]');

                if (startInput && endInput) {
                    function updateEndDateMin() {
                        if (startInput.value) {
                            endInput.min = startInput.value;
                            if (endInput.value && endInput.value < startInput.value) {
                                endInput.value = startInput.value;
                            }
                        }
                    }

                    startInput.addEventListener('change', updateEndDateMin);
                    // Initial run
                    updateEndDateMin();
                }
            });
        </script>

        <table class="table">
            <thead>
                <tr>
                    <th>N° de Factura</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Total</th>
                    <th>Método Pago</th>
                    <th>Cliente / Prov.</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                <tr>
                    <td>
                        <strong>#{{ str_pad($t->id, 6, '0', STR_PAD_LEFT) }}</strong>
                    </td>
                    <td>
                        @if($t->type == 'sale')
                            <span class="badge bg-success">Venta</span>
                        @else
                            <span class="badge bg-warning text-dark">Compra</span>
                        @endif
                    </td>
                    <td>{{ $t->created_at->format('d/m/Y h:i A') }}</td>
                    <td>
                        @php
                            $productCount = $t->movements->count();
                            $firstProduct = $t->movements->first()->product->name ?? 'N/A';
                        @endphp
                        @if($productCount > 1)
                            {{ $firstProduct }} <span class="text-muted">(+{{ $productCount - 1 }} más)</span>
                        @else
                            {{ $firstProduct }}
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $t->movements->sum('quantity') }}
                    </td>
                    <td class="text-center">-</td>
                    <td><strong>${{ number_format($t->total_amount, 0) }}</strong></td>
                    <td>
                        @php
                            $isPartial = $t->payment_status == 'partial';
                            $isCredit = $t->payment_status == 'credit' || $t->payment_method == 'credit';
                            
                            // Get deposit from related models
                            $deposit = 0;
                            if ($t->type == 'sale' && $t->credit) {
                                $deposit = $t->credit->paid_amount;
                            } elseif ($t->type == 'purchase') {
                                // For purchases we can try to find the account payable
                                $ap = \App\Models\AccountPayable::where('description', "Compra #{$t->id}")->first();
                                if ($ap) $deposit = $ap->paid_amount;
                            }
                        @endphp

                        @if($isCredit || $isPartial)
                            <span class="badge bg-danger">Crédito</span>
                            @if($deposit > 0)
                                <br><small class="text-muted">Abonó: ${{ number_format($deposit, 0) }}</small>
                            @endif
                        @else
                            @if($t->type == 'sale')
                                @if($t->payment_method == 'cash') <span class="badge bg-success">Efectivo 💵</span>
                                @elseif($t->payment_method == 'bank' || $t->payment_method == 'transfer') <span class="badge bg-info text-dark">Transf. 🏦</span>
                                @else <span class="badge bg-secondary">{{ $t->payment_method }}</span> @endif
                            @else
                                @php
                                    $pm = $t->movements->first()->payment_method ?? 'N/A';
                                @endphp
                                @if($pm == 'cash') <span class="badge bg-success">Efectivo 💵</span>
                                @elseif($pm == 'bank' || $pm == 'transfer') <span class="badge bg-info text-dark">Transf. 🏦</span>
                                @else <span class="badge bg-secondary">{{ ucfirst($pm) }}</span> @endif
                            @endif
                        @endif
                    </td>
                    <td>
                        {{ $t->type == 'sale' ? ($t->client->name ?? 'Consumidor Final') : ($t->provider->name ?? 'Proveedor') }}
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            @if($t->type == 'sale')
                                <a href="{{ route('sales.ticket', $t->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Factura">🖨️</a>
                                <button onclick="editTransaction('sale', {{ $t->id }})" class="btn btn-sm btn-outline-primary" title="Editar Venta">✏️</button>
                                <button onclick="deleteTransaction('sale', {{ $t->id }})" class="btn btn-sm btn-outline-danger" title="Eliminar Venta">🗑️</button>
                            @elseif($t->type == 'purchase')
                                <a href="{{ route('purchases.ticket', $t->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Comprobante">🖨️</a>
                                <button onclick="editTransaction('purchase', {{ $t->id }})" class="btn btn-sm btn-outline-primary" title="Editar Compra">✏️</button>
                                <button onclick="deleteTransaction('purchase', {{ $t->id }})" class="btn btn-sm btn-outline-danger" title="Eliminar Compra">🗑️</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align: center;">No hay registros en este rango de fechas.</td></tr>
                @endforelse
            </tbody>
        </table>
        
        {{ $transactions->links() }}
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function editTransaction(type, id) {
    if (type === 'sale') {
        window.location.href = `/sales?edit=${id}`;
    } else {
        window.location.href = `/purchases?edit=${id}`;
    }
}

function deleteTransaction(type, id) {
    const title = type === 'sale' ? '¿Eliminar Venta?' : '¿Eliminar Compra?';
    const text = type === 'sale' 
        ? 'Esto devolverá los productos al stock y anulará la venta.' 
        : 'Esto descontará los productos del stock y anulará la compra.';

    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = type === 'sale' ? `/sales/${id}` : `/purchases/${id}`;
            
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.fire('¡Eliminado!', data.message, 'success').then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo eliminar el registro.', 'error');
            });
        }
    });
}
</script>
@endsection
