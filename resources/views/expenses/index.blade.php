@extends('layouts.app')

@section('content')
<div class="card" style="border-radius: 20px; border: 1px solid #ebf0f5; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px;">
    <div class="card-header" style="background: white; border-bottom: 1px solid #f0f0f0; border-radius: 20px 20px 0 0; padding: 25px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: #333; font-weight: 700;">Control de Gastos</h2>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('expense-categories.index') }}" class="btn" style="background: #8B0000; color: white; padding: 10px 20px; border-radius: 12px; font-weight: bold; font-size: 1rem;">
                🏷️ Categorías
            </a>
            <a href="{{ route('expenses.create') }}" class="btn btn-primary open-expense-form" data-title="Registrar Nuevo Gasto" style="padding: 10px 20px; border-radius: 12px; font-weight: bold; font-size: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                + Registrar Gasto
            </a>
        </div>
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

    <div class="card-body" style="padding: 25px;">
        <!-- Filter & Summary Module (Top) -->
        <div class="card mb-4 border-0 bg-light shadow-sm" style="border-radius: 15px;">
            <div class="card-body p-4">
                <form action="{{ route('expenses.index') }}" method="GET" id="expenseFilterForm" class="row g-3 align-items-end">
                    
                    <!-- Week Selection -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Rango de Consulta</label>
                        <div class="input-group shadow-sm" style="border-radius: 12px; overflow: hidden; border: 2px solid #8B0000;">
                            <input type="date" name="start_date" id="startDate" class="form-control border-0" 
                                   value="{{ $startDate }}" 
                                   onchange="this.form.submit()"
                                   style="padding: 12px;">
                            <span class="input-group-text border-0 bg-white text-muted">➜</span>
                            <input type="date" name="end_date" id="endDate" class="form-control border-0 bg-white" 
                                   value="{{ $endDate }}" onchange="this.form.submit()" style="padding: 12px;">
                        </div>
                    </div>

                    <!-- Stats Display -->
                    <div class="col text-end">
                        <div class="text-muted small text-uppercase fw-bold">Gasto Total en Rango</div>
                        <h2 class="text-dark fw-bold mb-0 display-5" style="letter-spacing: -1px; color: #8B0000 !important;">
                            $ {{ number_format($totalFiltered, 0, ',', '.') }}
                        </h2>
                    </div>

                    @if(request('start_date'))
                        <div class="col-auto">
                            <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Limpiar</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <!-- Category Status Cards -->
        <div class="row g-3 mb-4">
            @php
                $categoryIcons = [
                    'ARRIENDO' => '🏠',
                    'SERVICIOS' => '⚡',
                    'PROVEEDORES' => '🚚',
                    'OTROS' => '📦',
                    'PRUEBA' => '🧪'
                ];
                $categoryColors = [
                    'ARRIENDO' => '#1976d2',
                    'SERVICIOS' => '#fbc02d',
                    'PROVEEDORES' => '#388e3c',
                    'OTROS' => '#7b1fa2',
                    'PRUEBA' => '#607d8b'
                ];
            @endphp

            @foreach($categories as $cat)
                @php 
                    $total = $categoryTotals[$cat->id] ?? 0;
                    $icon = $categoryIcons[strtoupper($cat->name)] ?? '📊';
                    $color = $categoryColors[strtoupper($cat->name)] ?? '#8B0000';
                @endphp
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm transition-hover cursor-pointer open-category-details" 
                         style="border-radius: 15px; border-left: 5px solid {{ $color }} !important; cursor: pointer;"
                         data-category-id="{{ $cat->id }}">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <span class="fs-3 me-2">{{ $icon }}</span>
                                    <h6 class="mb-0 fw-bold text-uppercase" style="color: {{ $color }}; letter-spacing: 0.5px;">{{ $cat->name }}</h6>
                                </div>
                                <span class="badge rounded-pill text-muted border small">Total Periodo</span>
                            </div>
                            
                            <div class="mb-0">
                                <span class="h2 fw-bold" style="color: #333;">$ {{ number_format($total, 0, ',', '.') }}</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-2 border-top pt-2 opacity-75">
                                <small class="text-muted" style="font-size: 0.75rem;">Ver transacciones</small>
                                <small class="text-primary fw-bold" style="font-size: 0.75rem;">Detalles →</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Table removed for cleaner look, history available in modals --}}
        @if($categories->isEmpty())
            <div class="text-center py-5">
                <h4>No hay categorías configuradas.</h4>
                <a href="{{ route('expense-categories.index') }}" class="btn btn-primary mt-3">Configurar Categorías</a>
            </div>
        @endif
    </div>
</div>

<!-- Modal for Creating/Editing -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="expenseModalTitle">Registrar Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="expenseModalBody">
                <!-- Content loaded via Ajax -->
            </div>
        </div>
    </div>
</div>

<!-- Modal for Category Details -->
<div class="modal fade" id="categoryDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <div id="categoryDetailsContent">
                <!-- Content loaded via Ajax -->
                <div class="p-5 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Ajax Modal Logic (For Form)
    document.addEventListener('click', function(e) {
        // Centralized Expense Form Loader (Create or Edit)
        const expenseTrigger = e.target.closest('.open-expense-form') || e.target.closest('.open-expense-modal');
        
        if (expenseTrigger) {
            e.preventDefault();
            const url = expenseTrigger.getAttribute('href');
            const title = expenseTrigger.getAttribute('data-title');
            
            document.getElementById('expenseModalTitle').innerText = title;
            
            // Close details modal if open
            const detailsModalEl = document.getElementById('categoryDetailsModal');
            let detailsModal = bootstrap.Modal.getInstance(detailsModalEl);
            if (detailsModal) detailsModal.hide();

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('expenseModalBody').innerHTML = html;
                    const bsm = new bootstrap.Modal(document.getElementById('expenseModal'));
                    bsm.show();
                });
                
        }

        // Category Details Modal Logic
        const categoryTrigger = e.target.closest('.open-category-details');
        if (categoryTrigger) {
            const catId = categoryTrigger.getAttribute('data-category-id');
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            const modalEl = document.getElementById('categoryDetailsModal');
            const bsm = new bootstrap.Modal(modalEl);
            
            document.getElementById('categoryDetailsContent').innerHTML = '<div class="p-5 text-center"><div class="spinner-border text-primary" role="status"></div></div>';
            bsm.show();

            fetch(`/expenses/details?category_id=${catId}&start_date=${startDate}&end_date=${endDate}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('categoryDetailsContent').innerHTML = html;
                });
        }

        // Delete Button Logic (Delegated)
        if (e.target.classList.contains('delete-expense-btn')) {
            const id = e.target.getAttribute('data-id');
            confirmDeleteExpense(id);
        }
    });

    function confirmDeleteExpense(id) {
        Swal.fire({
            title: '¿Eliminar registro?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/expenses/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    Swal.fire('Eliminado', data.message, 'success').then(() => {
                        location.reload();
                    });
                });
            }
        });
    }

    // Modal Form Submission
    window.submitFormAjax = function(form) {
        const formData = new FormData(form);
        const url = form.action;
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            Swal.fire('¡Éxito!', data.message, 'success').then(() => {
                location.reload();
            });
        })
        .catch(err => {
            Swal.fire('Error', 'Hubo un problema al guardar.', 'error');
        });
        return false;
    }
</script>
@endpush
@endsection
