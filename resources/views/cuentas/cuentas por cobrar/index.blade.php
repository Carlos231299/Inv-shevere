@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>📂 Cuentas por Cobrar (Por Cliente)</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCreditModal">
            ➕ Nueva Cuenta
        </button>
    </div>

    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    @endif

    <div class="card-body">
        <!-- Monthly Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius: 15px; border-left: 5px solid #dc3545 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-uppercase text-muted fw-bold mb-0" style="letter-spacing: 0.5px;">Total por Cobrar</h6>
                            <span class="fs-4">📉</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">$ {{ number_format($totalPending, 0, ',', '.') }}</h3>
                        <small class="text-muted">Deuda total pendiente</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius: 15px; border-left: 5px solid #ffc107 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-uppercase text-muted fw-bold mb-0" style="letter-spacing: 0.5px;">Nuevos Créditos (Mes)</h6>
                            <span class="fs-4">🆕</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">$ {{ number_format($newCreditsMonth, 0, ',', '.') }}</h3>
                        <small class="text-muted">Créditos otorgados este mes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius: 15px; border-left: 5px solid #198754 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-uppercase text-muted fw-bold mb-0" style="letter-spacing: 0.5px;">Recaudado (Mes)</h6>
                            <span class="fs-4">💵</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">$ {{ number_format($totalCollectedMonth, 0, ',', '.') }}</h3>
                        <small class="text-muted">Abonos recibidos este mes</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-4">
            <input type="text" id="client-search" class="form-control form-control-lg" placeholder="🔍 Buscar cliente..." style="border-radius: 10px; font-size: 1.1rem;">
        </div>

        <div id="clients-grid" class="row row-cols-1 row-cols-md-3 g-4">
            @forelse($clients as $client)
                <div class="col client-card-container">
                    <a href="{{ route('credits.showClient', $client->id) }}" style="text-decoration: none; color: inherit;">
                        <input type="hidden" class="client-name" value="{{ $client->name }}">
                        <div class="card h-100 hover-shadow" style="border: 1px solid #e0e0e0; transition: transform 0.2s; border-radius: 12px; cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div style="font-size: 3rem; margin-bottom: 10px;">👤</div>
                                <h4 class="card-title font-weight-bold mb-3" style="color: #333;">{{ $client->name }}</h4>
                                <div class="mt-3">
                                    <span class="text-muted" style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; display: block;">Deuda Total</span>
                                    <span style="font-size: 1.8rem; font-weight: 800; color: #dc3545;">
                                        $ {{ number_format($client->total_pending, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 text-center pb-4">
                                <span class="btn btn-outline-success rounded-pill btn-sm px-4">Ver Detalles</span>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <div style="font-size: 3rem; color: #ddd;">✅</div>
                    <h3 class="text-muted mt-3">No hay cuentas pendientes</h3>
                    <p class="text-muted">Excelente, todos tus clientes están al día.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const searchInput = document.getElementById('client-search');
    const grid = document.getElementById('clients-grid');

    searchInput.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        
        fetch(`{{ route('credits.index') }}?q=${term}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            grid.innerHTML = '';
            if(data.length === 0) {
                grid.innerHTML = `<div class="col-12 text-center py-5"><h4 class="text-muted">No se encontraron clientes</h4></div>`;
                return;
            }

            data.forEach(c => {
                const money = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(c.total_pending);

                const html = `
                    <div class="col client-card-container">
                        <a href="/credits/client/${c.id}" style="text-decoration: none; color: inherit;">
                            <div class="card h-100 hover-shadow" style="border: 1px solid #e0e0e0; transition: transform 0.2s; border-radius: 12px; cursor: pointer;">
                                <div class="card-body text-center p-4">
                                    <div style="font-size: 3rem; margin-bottom: 10px;">👤</div>
                                    <h4 class="card-title font-weight-bold mb-3" style="color: #333;">${c.name}</h4>
                                    <div class="mt-3">
                                        <span class="text-muted" style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; display: block;">Deuda Total</span>
                                        <span style="font-size: 1.8rem; font-weight: 800; color: #dc3545;">${money}</span>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top-0 text-center pb-4">
                                    <span class="btn btn-outline-success rounded-pill btn-sm px-4">Ver Detalles</span>
                                </div>
                            </div>
                        </a>
                    </div>
                `;
                grid.innerHTML += html;
            });
        });
    });

    function toggleNewClient(isNew) {
        const selectContainer = document.getElementById('existing-client-container');
        const inputContainer = document.getElementById('new-client-container');
        const clientSelect = document.getElementById('client_select');
        const clientNameInput = document.getElementById('new_client_name');

        if (isNew) {
            selectContainer.style.display = 'none';
            inputContainer.style.display = 'block';
            clientSelect.value = '';
            clientNameInput.required = true;
        } else {
            selectContainer.style.display = 'block';
            inputContainer.style.display = 'none';
            clientNameInput.value = '';
            clientNameInput.required = false;
        }
    }
</script>
<style>
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
</style>
@endsection

@section('modals')
<!-- Modal Create New Credit -->
<div class="modal fade" id="createCreditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('credits.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">🆕 Nueva Cuenta por Cobrar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Client Switch -->
                    <div class="mb-3 text-center">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="client_type" id="client_existing" autocomplete="off" checked onclick="toggleNewClient(false)">
                            <label class="btn btn-outline-primary" for="client_existing">Cliente Existente</label>

                            <input type="radio" class="btn-check" name="client_type" id="client_new" autocomplete="off" onclick="toggleNewClient(true)">
                            <label class="btn btn-outline-primary" for="client_new">Nuevo Cliente</label>
                        </div>
                    </div>

                    <!-- Existing Client -->
                    <div class="mb-3" id="existing-client-container">
                        <label class="form-label">Seleccionar Cliente</label>
                        <select name="client_id" id="client_select" class="form-select">
                            <option value="">-- Buscar Cliente --</option>
                            @foreach(\App\Models\Client::orderBy('name')->get() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- New Client -->
                    <div id="new-client-container" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Cliente *</label>
                            <input type="text" name="new_client_name" id="new_client_name" class="form-control" placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono (Opcional)</label>
                            <input type="text" name="new_client_phone" class="form-control" placeholder="Ej: 300 123 4567">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción / Concepto *</label>
                        <input type="text" name="description" class="form-control" required placeholder="Ej: Fiado de carne #123">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Monto ($) *</label>
                        <input type="number" name="amount" class="form-control" required min="1" placeholder="0">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

