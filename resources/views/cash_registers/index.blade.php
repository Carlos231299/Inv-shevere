@extends('layouts.app')

@section('content')
<div class="container-fluid" style="padding: 20px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold" style="color: #333;">📜 Historial de Cuadres de Caja</h2>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Cajero</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th class="text-center">Base (Efectivo)</th>
                            <th class="text-center">Diferencia Efectivo</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registers as $reg)
                        <tr>
                            <td class="ps-4">#{{ str_pad($reg->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="fw-bold text-primary">{{ $reg->user->name }}</td>
                            <td>{{ $reg->opened_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($reg->closed_at)
                                    {{ $reg->closed_at->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-muted italic">En curso...</span>
                                @endif
                            </td>
                            <td class="text-center fw-bold">$ {{ number_format($reg->initial_cash, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($reg->status == 'closed')
                                    @php $diff = $reg->physical_cash - $reg->system_cash; @endphp
                                    <span class="badge {{ $diff >= 0 ? 'bg-success' : 'bg-danger' }}" style="font-size: 0.85rem;">
                                        {{ $diff >= 0 ? '+' : '' }}$ {{ number_format($diff, 0, ',', '.') }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                @if($reg->status == 'open')
                                    <span class="badge bg-success rounded-pill">Abierta</span>
                                @else
                                    <span class="badge bg-secondary rounded-pill">Cerrada</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                @if($reg->status == 'closed')
                                    <a href="{{ route('cash-registers.ticket', $reg->id) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Ver Ticket">
                                        🖨️ Ver Ticket
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="mb-2" style="font-size: 2rem;">📭</div>
                                No hay registros de caja todavía.
                            </td>
                        </tr>
                        @endempty
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4 d-flex justify-content-center">
        {{ $registers->links() }}
    </div>
</div>
@endsection
