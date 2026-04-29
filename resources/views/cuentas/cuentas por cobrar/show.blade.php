@extends('layouts.app')

@section('content')
<div class="row">
    <!-- Payment & Info Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>👤 Cliente</h4>
            </div>
            <div class="card-body">
                <h2 style="margin-top: 0;">{{ $client->name }}</h2>
                <p class="text-muted">{{ $client->phone ?? 'Sin teléfono' }}</p>
                
                <hr>

                <div style="text-align: center; margin: 20px 0;">
                    <small>DEUDA TOTAL</small>
                    <h1 style="color: #dc3545; font-size: 3rem; margin: 0;">
                        ${{ number_format($client->total_debt, 0) }}
                    </h1>
                </div>

                <hr>

                @if($client->total_debt > 0)
                <h5>💸 Registrar Abono General</h5>
                <p class="text-muted small">El abono se aplicará a las deudas más antiguas.</p>
                
                <form action="{{ route('credits.payment') }}" method="POST">
                    @csrf
                    <input type="hidden" name="client_id" value="{{ $client->id }}">
                    
                    <div class="form-group mb-3">
                        <label>Método de Pago</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Efectivo 💵</option>
                            <option value="nequi">Nequi 📱</option>
                            <option value="bancolombia">Bancolombia 🏦</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="m-0" style="font-weight: 500;">Monto a Abonar</label>
                            <input type="number" id="payment-amount" name="amount" class="form-control" placeholder="$ 0" min="1" max="{{ $client->total_debt }}" required style="font-size: 1.5rem; text-align: center; width: 60%;">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('payment-amount').value = '{{ $client->total_debt }}'">
                                ✅ Todo
                            </button>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="button" class="btn btn-success" style="width: 100%; font-size: 1.2rem;" onclick="
                            const form = this.closest('form');
                            Swal.fire({
                                title: '¿Estás seguro?',
                                text: '¿Deseas confirmar el abono?',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#28a745',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: 'Sí, confirmar',
                                cancelButtonText: 'Cancelar'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    form.submit();
                                }
                            })">
                            ✅ Confirmar Abono
                        </button>
                    </div>
                </form>
                @else
                    <div class="alert alert-success text-center">
                        ✅ Este cliente está al día.
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- History Table -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h5 class="m-0">📜 Historial de Créditos y Abonos</h5>
                <a href="{{ route('credits.index') }}" class="btn" style="background: #f0f0f0; color: #555; border-radius: 12px; font-weight: 600;">⬅️ Volver</a>
            </div>
            <div class="card-body p-0">
                @if(session('success'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: "{{ session('success') }}",
                                timer: 2500,
                                showConfirmButton: false
                            });
                        });
                    </script>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger m-2 py-1">{{ session('error') }}</div>
                @endif

                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-sm table-striped table-hover m-0" style="font-size: 0.9rem;">
                        <thead style="background: #f8f9fa; position: sticky; top: 0;">
                            <tr>
                                <th style="width: 20%;">Fecha</th>
                                <th style="width: 40%;">Detalle</th>
                                <th class="text-end" style="width: 20%;">Monto</th>
                                <th class="text-end" style="width: 20%;">Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history->values() as $item)
                            <tr>
                                <td style="vertical-align: middle;">{{ $item['date'] ? $item['date']->format('d/m/Y') : 'N/A' }}</td>
                                <td style="vertical-align: middle;">{!! $item['description'] !!}</td>
                                <td class="text-end" style="font-weight: bold; vertical-align: middle; color: {{ $item['type'] == 'debt' ? '#dc3545' : '#28a745' }};">
                                    ${{ number_format($item['amount'], 0) }}
                                </td>
                                <td class="text-end" style="vertical-align: middle;">
                                    @if($item['type'] == 'debt')
                                        <span class="badge bg-danger">Deuda</span>
                                        @if(isset($item['balance']) && $item['balance'] > 0)
                                            <small class="d-block text-muted">Pend: ${{ number_format($item['balance'], 0) }}</small>
                                        @elseif(isset($item['balance']) && $item['balance'] <= 0)
                                            <span class="badge bg-success" style="font-size:0.6rem">PAGADO</span>
                                        @endif
                                    @else
                                        <span class="badge bg-success">Abono</span>
                                        @if(isset($item['id']))
                                            <button type="button" class="btn btn-sm btn-link text-primary p-0 ms-1" onclick="editPayment({{ $item['id'] }})">
                                                ✏️
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach

                            @if($history->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center p-3 text-muted">No hay movimientos registrados.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">✏️ Editar Abono de Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPaymentForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Monto</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" required min="1">
                        <small class="text-muted">Nota: Al editar, se verifica que no supere la deuda original de la venta asociada.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método de Pago</label>
                        <select name="payment_method" id="edit_payment_method" class="form-control">
                            <option value="cash">💵 Efectivo</option>
                            <option value="nequi">📱 Nequi</option>
                            <option value="bancolombia">🏦 Bancolombia</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editPayment(id) {
        fetch(`{{ url('credits/payment') }}/${id}/edit`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_amount').value = data.amount;
            document.getElementById('edit_payment_method').value = data.payment_method;
            const form = document.getElementById('editPaymentForm');
            form.action = `{{ url('credits/payment') }}/${data.id}`;
            new bootstrap.Modal(document.getElementById('editPaymentModal')).show();
        })
        .catch(error => Swal.fire('Error', 'No se pudo cargar la información.', 'error'));
    }
</script>
@endpush
@endsection
