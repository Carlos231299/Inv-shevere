@extends('layouts.app')

@section('content')
<div class="card" style="border-radius: 20px; border: 1px solid #ebf0f5; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px;">
    <div class="card-header" style="background: white; border-bottom: 1px solid #f0f0f0; border-radius: 20px 20px 0 0; padding: 25px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: #333; font-weight: 700;">👷 Control de Trabajadores</h2>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('workers.create') }}" class="btn btn-primary open-worker-modal" data-title="Registrar Pago a Trabajador" style="padding: 10px 20px; border-radius: 12px; font-weight: bold; font-size: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                + Registrar Pago
            </a>
        </div>
    </div>

    <div class="card-body" style="padding: 25px;">
        <!-- Filter & Summary Module (Now on Top) -->
        <div class="card mb-4 border-0 bg-light shadow-sm" style="border-radius: 15px;">
            <div class="card-body p-4">
                <form action="{{ route('workers.index') }}" method="GET" id="workerFilterForm" class="row g-3 align-items-end">
                    
                    <!-- Week Selection -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Semana de Consulta (Lunes - Domingo)</label>
                        <div class="input-group shadow-sm" style="border-radius: 12px; overflow: hidden; border: 2px solid #1976d2;">
                            <input type="date" name="start_date" id="startDate" class="form-control border-0" 
                                   value="{{ $startDate }}" 
                                   onchange="snapToWeek(this)"
                                   style="padding: 12px;">
                            <span class="input-group-text border-0 bg-white text-muted">➜</span>
                            <input type="date" name="end_date" id="endDate" class="form-control border-0 bg-white" 
                                   value="{{ $endDate }}" readonly style="padding: 12px;">
                        </div>
                    </div>

                    <!-- Stats Display -->
                    <div class="col text-end">
                        <div class="text-muted small text-uppercase fw-bold">Gasto Total en Semana</div>
                        <h2 class="text-dark fw-bold mb-0 display-5" style="letter-spacing: -1px;">
                            $ {{ number_format($totalFiltered, 0, ',', '.') }}
                        </h2>
                    </div>

                    @if(request('start_date'))
                        <div class="col-auto">
                            <a href="{{ route('workers.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Limpiar</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <!-- Worker Status Cards (Now Clickable) -->
        <div class="row g-3 mb-4">
            @php
                $workerConfig = [
                    'BREINER' => ['icon' => '👷', 'color' => '#1976d2', 'bg' => 'linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%)'],
                    'ANDRES' => ['icon' => '👨‍🔧', 'color' => '#2e7d32', 'bg' => 'linear-gradient(135deg, #f1f8e9 0%, #ffffff 100%)'],
                    'JAIR' => ['icon' => '👨‍🏭', 'color' => '#7b1fa2', 'bg' => 'linear-gradient(135deg, #f3e5f5 0%, #ffffff 100%)'],
                ];
                $cap = 420000;
            @endphp

            @foreach($workerTotals as $name => $total)
                @php 
                    $config = $workerConfig[$name] ?? ['icon' => '👤', 'color' => '#333', 'bg' => '#fff'];
                    $percentage = min(($total / $cap) * 100, 100);
                    $isOver = $total > $cap;
                @endphp
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm transition-hover cursor-pointer open-worker-details" 
                         style="border-radius: 15px; background: {{ $config['bg'] }}; border-left: 5px solid {{ $isOver ? '#d32f2f' : $config['color'] }} !important; cursor: pointer;"
                         data-worker="{{ $name }}">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="fs-4 me-2">{{ $config['icon'] }}</span>
                                    <h6 class="mb-0 fw-bold text-uppercase" style="color: {{ $config['color'] }}; letter-spacing: 0.5px;">{{ $name }}</h6>
                                </div>
                                @if($isOver)
                                    <span class="badge bg-danger rounded-pill shadow-sm">⚠️ EXCEDIDO</span>
                                @else
                                    <span class="badge rounded-pill text-muted border small">Semana Actual</span>
                                @endif
                            </div>
                            
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-end">
                                    <span class="display-6 fw-bold" style="color: #333; font-size: 1.6rem;">$ {{ number_format($total, 0, ',', '.') }}</span>
                                    <span class="small text-muted">/ $ {{ number_format($cap/1000, 0) }}k</span>
                                </div>
                            </div>

                            <div class="progress shadow-sm" style="height: 8px; border-radius: 10px; background: rgba(0,0,0,0.05);">
                                <div class="progress-bar {{ $isOver ? 'bg-danger' : '' }}" 
                                     role="progressbar" 
                                     style="width: {{ $percentage }}%; background-color: {{ $isOver ? '' : $config['color'] }}; border-radius: 10px;" 
                                     aria-valuenow="{{ $percentage }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted" style="font-size: 0.7rem;">Acumulado Semanal</small>
                                <small class="fw-bold" style="font-size: 0.7rem; color: {{ $isOver ? '#d32f2f' : $config['color'] }}">
                                    {{ $isOver ? 'Excedido por $' . number_format($total - $cap, 0) : 'Disponible: $' . number_format($cap - $total, 0) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Table removed as per user request. Details are now visible in modals. --}}
    </div>
</div>

<!-- Modal for Creating/Editing -->
<div class="modal fade" id="workerExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="workerModalTitle">Registrar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="workerModalBody">
                <!-- Content loaded via Ajax -->
            </div>
        </div>
    </div>
</div>

<!-- Modal for Worker Details -->
<div class="modal fade" id="workerDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <div id="workerDetailsContent">
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
    function getMonday(d) {
        d = new Date(d);
        var day = d.getDay(),
            diff = d.getDate() - day + (day == 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    }

    function formatDate(date) {
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    function snapToWeek(input) {
        if (!input.value) return;
        const selectedDate = new Date(input.value + 'T00:00:00');
        const monday = getMonday(selectedDate);
        input.value = formatDate(monday);
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        document.getElementById('endDate').value = formatDate(sunday);
        document.getElementById('workerFilterForm').submit();
    }

    // Ajax Modal Logic (For Form)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('open-worker-modal') || e.target.closest('.open-worker-modal')) {
            e.preventDefault();
            const btn = e.target.classList.contains('open-worker-modal') ? e.target : e.target.closest('.open-worker-modal');
            const url = btn.getAttribute('href');
            const title = btn.getAttribute('data-title');
            
            document.getElementById('workerModalTitle').innerText = title;
            
            // Close details modal if open (shorthand to avoid complex logic)
            const detailsModalEl = document.getElementById('workerDetailsModal');
            const detailsModal = bootstrap.Modal.getInstance(detailsModalEl);
            if (detailsModal) detailsModal.hide();

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('workerModalBody').innerHTML = html;
                    const bsm = new bootstrap.Modal(document.getElementById('workerExpenseModal'));
                    bsm.show();
                });
        }

        // Worker Details Modal Logic
        if (e.target.classList.contains('open-worker-details') || e.target.closest('.open-worker-details')) {
            const card = e.target.classList.contains('open-worker-details') ? e.target : e.target.closest('.open-worker-details');
            const worker = card.getAttribute('data-worker');
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            const modalEl = document.getElementById('workerDetailsModal');
            const bsm = new bootstrap.Modal(modalEl);
            
            document.getElementById('workerDetailsContent').innerHTML = '<div class="p-5 text-center"><div class="spinner-border text-primary" role="status"></div></div>';
            bsm.show();

            fetch(`/workers/details?worker=${worker}&start_date=${startDate}&end_date=${endDate}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('workerDetailsContent').innerHTML = html;
                });
        }

        // Delete Button Logic (Delegated)
        if (e.target.classList.contains('delete-worker-btn')) {
            confirmDeleteWorker(e.target.getAttribute('data-id'));
        }
    });

    // Handle Form Submission with Cap Check
    function submitWorkerForm(form, force = false) {
        const formData = new FormData(form);
        if (force) formData.append('force_save', '1');
        
        const url = form.action;
        const method = form.querySelector('input[name="_method"]')?.value || 'POST';

        fetch(url, {
            method: 'POST', // Use POST but honor _method for PUT
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.status === 409) {
                return response.json().then(data => {
                    Swal.fire({
                        title: '⚠️ ¡Tope Semanal Superado!',
                        html: `El trabajador <b>${data.worker}</b> ya tiene acumulado <b>$${new Intl.NumberFormat('es-CO').format(data.current_total)}</b> esta semana.<br><br>Este pago de <b>$${new Intl.NumberFormat('es-CO').format(formData.get('amount'))}</b> hará que el total sea <b>$${new Intl.NumberFormat('es-CO').format(data.new_total)}</b>.<br><br>¿Estás seguro de superar el límite de $420,000?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, registrar de todos modos',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitWorkerForm(form, true);
                        }
                    });
                    throw new Error('CAP_EXCEEDED');
                });
            }
            if (!response.ok) return response.json().then(err => { throw err; });
            return response.json();
        })
        .then(data => {
            if (data.message) {
                Swal.fire('¡Hecho!', data.message, 'success').then(() => {
                    location.reload();
                });
            }
        })
        .catch(err => {
            if (err.message !== 'CAP_EXCEEDED' && err.errors) {
                let errorMsgs = Object.values(err.errors).flat().join('<br>');
                Swal.fire('Error', errorMsgs, 'error');
            }
        });
    }

    function confirmDeleteWorker(id) {
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
                fetch(`/workers/${id}`, {
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
</script>
@endpush
@endsection
