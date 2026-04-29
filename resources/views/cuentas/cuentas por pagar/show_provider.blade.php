@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('cuentas-por-pagar.index') }}" class="btn btn-outline-secondary mb-3">
            ← Volver a Cuentas por Pagar
        </a>
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
            <div class="card-body d-flex justify-content-between align-items-center p-4">
                <div>
                    <h6 class="text-uppercase text-muted mb-1" style="font-size: 0.85rem; letter-spacing: 1px;">Proveedor</h6>
                    <h1 class="display-5 font-weight-bold mb-0 text-dark">{{ $provider->name }}</h1>
                </div>
                <div class="text-end">
                    <h6 class="text-uppercase text-muted mb-1" style="font-size: 0.85rem; letter-spacing: 1px;">Deuda Total Pendiente</h6>
                    <h2 class="display-4 font-weight-bold mb-0 text-danger">$ {{ number_format($totalPending, 0, ',', '.') }}</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 font-weight-bold">📜 Facturas / Cuentas</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Descripción</th>
                        <th>Fecha Creación</th>
                        <th>Vencimiento</th>
                        <th>Total</th>
                        <th>Abonado</th>
                        <th>Pendiente</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cuentas as $cuenta)
                        @php
                            $pending = $cuenta->amount - $cuenta->paid_amount;
                            $rowClass = $cuenta->status == 'paid' ? 'bg-light text-muted' : '';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="ps-4 font-weight-bold">#{{ $cuenta->id }}</td>
                            <td>{{ $cuenta->description }}</td>
                            <td>{{ $cuenta->created_at->format('d/m/Y') }}</td>
                            <td>
                                @if(\Carbon\Carbon::parse($cuenta->due_date)->isPast() && $cuenta->status != 'paid')
                                    <span class="text-danger font-weight-bold">Make due check logic here if desired</span>
                                    {{ \Carbon\Carbon::parse($cuenta->due_date)->format('d/m/Y') }}
                                @else
                                    {{ \Carbon\Carbon::parse($cuenta->due_date)->format('d/m/Y') }}
                                @endif
                            </td>
                            <td>$ {{ number_format($cuenta->amount, 0) }}</td>
                            <td class="text-success">$ {{ number_format($cuenta->paid_amount, 0) }}</td>
                            <td class="font-weight-bold {{ $pending > 0 ? 'text-danger' : 'text-success' }}">
                                $ {{ number_format($pending, 0) }}
                            </td>
                            <td>
                                @if($cuenta->status == 'paid')
                                    <span class="badge bg-success rounded-pill">Pagado</span>
                                @elseif($cuenta->status == 'pending')
                                    <span class="badge bg-warning text-dark rounded-pill">Pendiente</span>
                                @else
                                    <span class="badge bg-danger rounded-pill">Vencido</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    @if($cuenta->status != 'paid')
                                        <button class="btn btn-primary btn-sm rounded-pill px-3" 
                                                onclick="openPaymentModal({{ $cuenta->id }}, '{{ $cuenta->description }}', {{ $pending }})">
                                            💳 Abonar
                                        </button>
                                    @endif
                                    <a href="{{ route('cuentas-por-pagar.show', $cuenta->id) }}" class="btn btn-outline-secondary btn-sm rounded-circle" title="Ver Historial">
                                        👁️
                                    </a>
                                    <form action="{{ route('cuentas-por-pagar.destroy', $cuenta->id) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" title="Eliminar" onclick="confirmDelete(this)">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <span class="text-muted">No hay registros para este proveedor.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('modals')
<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('cuentas-por-pagar.payment') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">💸 Registrar Abono</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="account_payable_id" id="modal_account_id">
                    
                    <div class="alert alert-info">
                        Abonando a: <strong id="modal_desc"></strong><br>
                        Saldo Pendiente: <strong id="modal_pending"></strong>
                    </div>

                    <div class="mb-3">
                        <label>Monto</label>
                        <input type="number" name="amount" class="form-control form-control-lg" required min="1" placeholder="$ 0">
                    </div>
                    <div class="mb-3">
                        <label>Método de Pago</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">💵 Efectivo</option>
                            <option value="nequi">📱 Nequi</option>
                            <option value="bancolombia">🏦 Bancolombia</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Confirmar Pago</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openPaymentModal(id, desc, pending) {
        document.getElementById('modal_account_id').value = id;
        document.getElementById('modal_desc').innerText = desc;
        document.getElementById('modal_pending').innerText = '$ ' + new Intl.NumberFormat('es-CO').format(pending);
        
        var myModal = new bootstrap.Modal(document.getElementById('paymentModal'));
        myModal.show();
    }
</script>
<script>
    function confirmDelete(button) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                button.closest('form').submit();
            }
        })
    }
</script>
@endsection
