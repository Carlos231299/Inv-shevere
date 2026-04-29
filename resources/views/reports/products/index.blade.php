@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header & Filters -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 fw-bold text-primary">📊 Reporte de Ventas por Producto</h2>
        
        <form action="{{ route('reports.products') }}" method="GET" class="d-flex gap-2 align-items-center">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-calendar-alt text-muted"></i></span>
                <input type="date" name="start_date" class="form-control border-start-0 ps-0" value="{{ $startDate }}">
            </div>
            <span class="text-muted fw-bold">-</span>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-calendar-alt text-muted"></i></span>
                <input type="date" name="end_date" class="form-control border-start-0 ps-0" value="{{ $endDate }}">
            </div>
            <button type="submit" class="btn btn-primary fw-bold px-4">
                <i class="fas fa-filter me-2"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <!-- Total Sales -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #28a745 !important;">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <h6 class="text-muted mb-1 fw-bold text-uppercase">Ventas Totales</h6>
                        <h3 class="mb-0 fw-bold text-dark">$ {{ number_format($totalSales, 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profit -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #17a2b8 !important;">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <h6 class="text-muted mb-1 fw-bold text-uppercase">Utilidad Estimada</h6>
                        <h3 class="mb-0 fw-bold text-dark">$ {{ number_format($totalProfit, 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Best Seller -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #ffc107 !important;">
                <div class="card-body d-flex align-items-center">
                    <div style="overflow: hidden;">
                        <h6 class="text-muted mb-1 fw-bold text-uppercase">Más Vendido</h6>
                        @if($bestSeller)
                            <h5 class="mb-0 fw-bold text-dark text-truncate">{{ $bestSeller->product_name }}</h5>
                            <small class="text-muted">{{ $bestSeller->total_quantity }} {{ $bestSeller->measure_type == 'kg' ? 'Kg' : 'Unid' }}</small>
                        @else
                            <h5 class="mb-0 fw-bold text-dark">-</h5>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-4 py-3" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Producto</th>
                            <th class="text-center py-3" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Cantidad</th>
                            <th class="text-end py-3" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Precio Prom.</th>
                            <th class="text-end py-3" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Total Venta</th>
                            <th class="text-end pe-4 py-3" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Utilidad Estimada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($finalData as $row)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $row->product_name }}</div>
                                    <small class="text-muted">{{ $row->product_sku }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border border-secondary fw-bold px-3 py-2">
                                        {{ number_format($row->total_quantity, 2) }} 
                                        <small class="text-muted ms-1">{{ $row->measure_type == 'kg' ? 'kg' : 'unid' }}</small>
                                    </span>
                                </td>
                                <td class="text-end text-muted fw-bold">
                                    $ {{ number_format($row->avg_price, 0) }}
                                </td>
                                <td class="text-end fw-bold text-success" style="font-size: 1.1rem;">
                                    $ {{ number_format($row->total_revenue, 0) }}
                                </td>
                                <td class="text-end pe-4 fw-bold text-info">
                                    $ {{ number_format($row->estimated_profit, 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-bold">No se encontraron ventas en este periodo.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
