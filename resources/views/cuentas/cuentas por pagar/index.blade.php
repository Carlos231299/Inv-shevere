@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>📂 Cuentas por Pagar (Por Proveedor)</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPayableModal">
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
                            <h6 class="text-uppercase text-muted fw-bold mb-0" style="letter-spacing: 0.5px;">Total por Pagar</h6>
                            <span class="fs-4">📉</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">$ {{ number_format($totalPending, 0, ',', '.') }}</h3>
                        <small class="text-muted">Deuda total a proveedores</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius: 15px; border-left: 5px solid #fd7e14 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-uppercase text-muted fw-bold mb-0" style="letter-spacing: 0.5px;">Nuevas Deudas (Mes)</h6>
                            <span class="fs-4">🆕</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">$ {{ number_format($newDebtsMonth, 0, ',', '.') }}</h3>
                        <small class="text-muted">Compras a crédito este mes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius: 15px; border-left: 5px solid #198754 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-uppercase text-muted fw-bold mb-0" style="letter-spacing: 0.5px;">Pagado (Mes)</h6>
                            <span class="fs-4">✅</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">$ {{ number_format($totalPaidMonth, 0, ',', '.') }}</h3>
                        <small class="text-muted">Pagos realizados este mes</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-4">
            <input type="text" id="provider-search" class="form-control form-control-lg" placeholder="🔍 Buscar proveedor..." style="border-radius: 10px; font-size: 1.1rem;">
        </div>

        <div id="providers-grid" class="row row-cols-1 row-cols-md-3 g-4">
            @forelse($providers as $provider)
                <div class="col provider-card-container">
                    <a href="{{ route('cuentas-por-pagar.showProvider', $provider->id) }}" style="text-decoration: none; color: inherit;">
                        <input type="hidden" class="provider-name" value="{{ $provider->name }}">
                        <div class="card h-100 hover-shadow" style="border: 1px solid #e0e0e0; transition: transform 0.2s; border-radius: 12px; cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div style="font-size: 3rem; margin-bottom: 10px;">🏢</div>
                                <h4 class="card-title font-weight-bold mb-3" style="color: #333;">{{ $provider->name }}</h4>
                                <div class="mt-3">
                                    <span class="text-muted" style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; display: block;">Deuda Total</span>
                                    <span style="font-size: 1.8rem; font-weight: 800; color: #dc3545;">
                                        $ {{ number_format($provider->total_pending, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 text-center pb-4">
                                <span class="btn btn-outline-primary rounded-pill btn-sm px-4">Ver Detalles</span>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <div style="font-size: 3rem; color: #ddd;">✅</div>
                    <h3 class="text-muted mt-3">No hay deudas pendientes</h3>
                    <p class="text-muted">Excelente, estás al día con tus proveedores.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Simple Client-Side Filter (since we load all upfront currently)
    // If list gets too big, switch to AJAX in controller as implemented.
    // For now, let's use the AJAX endpoint for robust search if filtered.
    
    const searchInput = document.getElementById('provider-search');
    const grid = document.getElementById('providers-grid');

    searchInput.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        
        // Use Javascript Filter for instant feel if we have the items formatted
        // Or fetch from server. Given typical scale, client filter is snappier initially.
        // But let's use the server method I implemented for "real search" feel.

        fetch(`{{ route('cuentas-por-pagar.index') }}?q=${term}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            grid.innerHTML = '';
            if(data.length === 0) {
                grid.innerHTML = `<div class="col-12 text-center py-5"><h4 class="text-muted">No se encontraron proveedores</h4></div>`;
                return;
            }

            data.forEach(p => {
                // Format Money
                const money = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(p.total_pending);

                const html = `
                    <div class="col provider-card-container">
                        <a href="/cuentas-por-pagar/provider/${p.id}" style="text-decoration: none; color: inherit;">
                            <div class="card h-100 hover-shadow" style="border: 1px solid #e0e0e0; transition: transform 0.2s; border-radius: 12px; cursor: pointer;">
                                <div class="card-body text-center p-4">
                                    <div style="font-size: 3rem; margin-bottom: 10px;">🏢</div>
                                    <h4 class="card-title font-weight-bold mb-3" style="color: #333;">${p.name}</h4>
                                    <div class="mt-3">
                                        <span class="text-muted" style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; display: block;">Deuda Total</span>
                                        <span style="font-size: 1.8rem; font-weight: 800; color: #dc3545;">${money}</span>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top-0 text-center pb-4">
                                    <span class="btn btn-outline-primary rounded-pill btn-sm px-4">Ver Detalles</span>
                                </div>
                            </div>
                        </a>
                    </div>
                `;
                grid.innerHTML += html;
            });
        });
    });

    function toggleNewProvider(isNew) {
        const selectContainer = document.getElementById('existing-provider-container');
        const inputContainer = document.getElementById('new-provider-container');
        const providerSelect = document.getElementById('provider_select');
        const providerNameInput = document.getElementById('new_provider_name');

        if (isNew) {
            selectContainer.style.display = 'none';
            inputContainer.style.display = 'block';
            providerSelect.value = '';
            providerNameInput.required = true;
        } else {
            selectContainer.style.display = 'block';
            inputContainer.style.display = 'none';
            providerNameInput.value = '';
            providerNameInput.required = false;
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
<!-- Modal Create Payables (Reused) -->
<div class="modal fade" id="createPayableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('cuentas-por-pagar.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">🆕 Nueva Cuenta por Pagar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Provider Switch -->
                    <div class="mb-3 text-center">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="provider_type" id="prov_existing" autocomplete="off" checked onclick="toggleNewProvider(false)">
                            <label class="btn btn-outline-primary" for="prov_existing">Proveedor Existente</label>

                            <input type="radio" class="btn-check" name="provider_type" id="prov_new" autocomplete="off" onclick="toggleNewProvider(true)">
                            <label class="btn btn-outline-primary" for="prov_new">Nuevo Proveedor</label>
                        </div>
                    </div>

                    <!-- Existing Provider -->
                    <div class="mb-3" id="existing-provider-container">
                        <label class="form-label">Seleccionar Proveedor</label>
                        <select name="provider_id" id="provider_select" class="form-select">
                            <option value="">-- Buscar Proveedor --</option>
                            @foreach(\App\Models\Provider::orderBy('name')->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- New Provider -->
                    <div id="new-provider-container" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Proveedor *</label>
                            <input type="text" name="new_provider_name" id="new_provider_name" class="form-control" placeholder="Ej: Distribuidora carnes...">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción *</label>
                        <input type="text" name="description" class="form-control" placeholder="Ej: Factura #1234">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Monto ($) *</label>
                            <input type="number" name="amount" class="form-control" required min="1" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha Vencimiento *</label>
                            <input type="date" name="due_date" class="form-control" required value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                        </div>
                    </div>

                    <input type="hidden" name="status" value="pending">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

