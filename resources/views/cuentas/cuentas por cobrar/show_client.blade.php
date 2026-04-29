@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('credits.index') }}" class="btn btn-outline-secondary mb-3">
            ← Volver a Cuentas por Cobrar
        </a>
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f0f7ff 0%, #e3f2fd 100%);">
            <div class="card-body d-flex justify-content-between align-items-center p-4">
                <div>
                    <h6 class="text-uppercase text-muted mb-1" style="font-size: 0.85rem; letter-spacing: 1px;">Cliente</h6>
                    <h1 class="display-5 font-weight-bold mb-0 text-dark">{{ $client->name }}</h1>
                    <p class="text-muted mb-0">{{ $client->phone ?? 'Sin teléfono' }}</p>
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
        <h5 class="mb-0 font-weight-bold">📜 Historial de Créditos / Ventas</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Abonado</th>
                        <th>Pendiente</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($credits as $credit)
                        @php
                            $pending = $credit->total_debt - $credit->paid_amount;
                            $rowClass = $credit->status == 'paid' ? 'bg-light text-muted' : '';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="ps-4 font-weight-bold">#{{ $credit->id }}</td>
                            <td>
                                {{ $credit->description ?? ($credit->sale_id ? "Venta #{$credit->sale_id}" : "Deuda Manual") }}
                            </td>
                            <td>{{ $credit->created_at->format('d/m/Y') }}</td>
                            <td>$ {{ number_format($credit->total_debt, 0) }}</td>
                            <td class="text-success">$ {{ number_format($credit->paid_amount, 0) }}</td>
                            <td class="font-weight-bold {{ $pending > 0 ? 'text-danger' : 'text-success' }}">
                                $ {{ number_format($pending, 0) }}
                            </td>
                            <td>
                                @if($credit->status == 'paid')
                                    <span class="badge bg-success rounded-pill">Pagado</span>
                                @else
                                    <span class="badge bg-warning text-dark rounded-pill">Pendiente</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    @if($credit->status != 'paid')
                                        <button class="btn btn-success btn-sm rounded-pill px-3" 
                                                onclick="openPaymentModal({{ $credit->id }}, '{{ $credit->description ?? 'Venta #' . $credit->sale_id }}', {{ $pending }})">
                                            💳 Abonar
                                        </button>
                                    @endif
                                    <a href="{{ route('credits.show', $client->id) }}" class="btn btn-outline-primary btn-sm rounded-circle" title="Ver Historial Completo">
                                        👁️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <span class="text-muted">No hay registros de créditos para este cliente.</span>
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
        <form action="{{ route('credits.payment') }}" method="POST">
            @csrf
            <!-- We need to pass client_id for FIFO if we want, but controller storePayment expects client_id. 
                 Wait, storePayment in CreditController actually takes client_id and distributes? 
                 Let's check storePayment logic. -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">💸 Registrar Abono</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="client_id" value="{{ $client->id }}">
                    <input type="hidden" name="credit_id" id="modal_credit_id">
                    
                    <div class="alert alert-info">
                        Registrando abono para: <strong>{{ $client->name }}</strong><br>
                        Saldo Pendiente Total: <strong>$ {{ number_format($totalPending, 0, ',', '.') }}</strong>
                    </div>

                    <div class="mb-3">
                        <label>Monto del Abono</label>
                        <input type="number" name="amount" id="modal_amount" class="form-control form-control-lg" required min="1" placeholder="$ 0">
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
                    <button type="submit" class="btn btn-success">Confirmar Pago</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openPaymentModal(creditId, desc, pending) {
        document.getElementById('modal_credit_id').value = creditId;
        document.getElementById('modal_amount').value = pending;
        
        var myModal = new bootstrap.Modal(document.getElementById('paymentModal'));
        myModal.show();
    }
</script>
@endsection
