@extends('layouts.app')

@section('content')
    <!-- Top Header & Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="{{ route('reports.index') }}" class="btn" style="background: #f0f0f0; color: #555; border-radius: 12px; font-weight: 600;">⬅️ Volver</a>
            <h1 style="margin: 0; color: var(--color-primary-dark);">Estado de Resultados</h1>
        </div>
        <div style="display: flex; gap: 10px;">
        </div>
    </div>

<div class="card">
    <div class="card-body">

        <form method="GET" action="{{ route('reports.financial') }}" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end;">
            
            <div class="form-group" style="margin-bottom: 0;">
                <label>Desde:</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate }}" required autocomplete="off">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label>Hasta:</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate }}" required autocomplete="off">
            </div>

            <button type="submit" class="btn btn-primary">Filtrar</button>
            <button type="button" onclick="window.location.href='{{ route('reports.financial.pdf') }}' + window.location.search" class="btn btn-danger" style="background-color: #dc3545; border-color: #dc3545;">📄 Exportar PDF</button>
            <button type="button" onclick="window.location.href='{{ route('reports.financial.export') }}' + window.location.search" class="btn btn-success" style="background-color: #217346; border-color: #217346;">📊 Exportar Excel</button>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const startInput = document.getElementById('start_date');
                const endInput = document.getElementById('end_date');

                function updateMinDate() {
                    if (startInput.value) {
                        endInput.min = startInput.value;
                        if (endInput.value && endInput.value < startInput.value) {
                             endInput.value = startInput.value;
                        }
                    }
                }

                if (startInput && endInput) {
                    startInput.addEventListener('change', updateMinDate);
                    // Initialize
                    updateMinDate();
                }
            });

            function validateDates() {
                const start = document.getElementById('start_date').value;
                const end = document.getElementById('end_date').value;
                if (start && end && start > end) {
                    Swal.fire('Error de Fechas', 'La fecha final no puede ser menor a la inicial.', 'error');
                    return false;
                }
                return true;
            }
        </script>
        
        @if($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 20px; padding: 10px; background: #f8d7da; color: #721c24; border-radius: 5px;">
                {{ $errors->first('end_date') }}
            </div>
        @endif

        <div class="financial-statement" style="max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 25px; background: #fff;">
            <h3 style="text-align: center; text-transform: uppercase; color: #8B0000; margin-bottom: 30px;">Estado de Resultados Comparativo</h3>
            
            <table class="table table-borderless">
                <thead>
                    <tr style="border-bottom: 2px solid #8B0000;">
                        <th style="width: 40%;">Concepto</th>
                        <th style="width: 30%; text-align: right; color: #ffffffff;">Facturado</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>(+) Ingresos por Ventas</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($totalRealSales, 0) }}</td>
                        
                    </tr>
                    <tr>
                        <td>(-) Costo de Ventas</td>
                        <td style="text-align: right; color: #dc3545;">- ${{ number_format($totalCost, 0) }}</td>
                        
                    </tr>
                    
                    <tr style="border-top: 1px dashed #ccc;">
                        <td style="font-weight: bold;">(=) Utilidad Bruta</td>
                        <td style="text-align: right; font-weight: bold; font-size: 1.1rem;">${{ number_format($realGrossProfit, 0) }}</td>
                        
                    </tr>
                 
                    <tr>
                        <td style="font-weight: bold; color: #dc3545;">(-) Total Gastos</td>
                        <td style="text-align: right; font-weight: bold; color: #dc3545;">- ${{ number_format($totalExpenses, 0) }}</td>
                        
                    </tr>
                    <tr style="border-top: 2px solid #8B0000; background-color: #f8f9fa;">
                        <td style="font-weight: 900; font-size: 1.2rem; padding-top: 15px;">UTILIDAD OPERACIONAL</td>
                        <td style="text-align: right; font-weight: 900; font-size: 1.3rem; padding-top: 15px; {{ $operationalProfit >= 0 ? 'color: green;' : 'color: red;' }}">
                            ${{ number_format($operationalProfit, 0) }}
                        </td>
                        
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Sección: Distribución de Activos (Integrado) -->
             <div style="margin-top: 30px;">
                <h5 style="text-align: center; color: #555; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px;">
                    ¿Dónde está la Utilidad? (Distribución de Activos y Pasivos)
                </h5>
                <table class="table table-bordered">
                     <thead class="thead-light">
                        <tr>
                            <th>Concepto</th>
                            <th style="text-align: right;">Valor</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-weight: bold; color: #17a2b8;">(+) Inventario (Costo Actual)</td>
                            <td style="text-align: right; font-weight: bold;">${{ number_format($inventoryValuation, 0) }}</td>
                            <td class="text-muted"><small>Mercancía actualmente en bodega</small></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #ffc107;">(+) Cuentas por Cobrar</td>
                            <td style="text-align: right; font-weight: bold;">${{ number_format($totalAccountsReceivable, 0) }}</td>
                            <td class="text-muted"><small>Dinero pendiente de clientes</small></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #dc3545;">(-) Cuentas por pagar</td>
                            <td style="text-align: right; font-weight: bold; color: #dc3545;">-${{ number_format($totalAccountsPayable, 0) }}</td>
                            <td class="text-muted"><small>Dinero pendiente de proveedores</small></td>
                        </tr>   
                        <tr>
                            <td style="font-weight: bold; color: #28a745;">(+) Total Efectivo</td>
                            <td style="text-align: right; font-weight: bold;">${{ number_format($totalCash, 0) }}</td>
                            <td class="text-muted"><small>Dinero disponible en Caja</small></td>
                        </tr>
                        <!-- Row: Total Consolidated -->
                        <tr style="background-color: #f8f9fa; border-top: 3px solid #333;">
                            <td style="font-weight: bold; font-size: 1.1rem; color: #212529;">TOTAL CONSOLIDADO</td>
                            <td style="text-align: right; font-weight: bold; font-size: 1.1rem; color: #28a745;">
                                ${{ number_format($operationalProfit + $inventoryValuation + $totalAccountsReceivable + $totalCash - $totalAccountsPayable, 0) }}
                            </td>
                            <td class="text-muted"><small>Utilidad Operacional + Inventario + Cuentas por Cobrar + Efectivo - Cuentas por pagar</small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
           <div style="margin-top: 20px; font-size: 0.85rem; color: #666; font-style: italic; text-align: center;">
                Notas: 
                <br>
                - Lo marcado con (+) Activos (-) Pasivos
                <br>
                - Esta es una estimación basada en los datos disponibles.
            </div>

    </div>
</div>

    </div>
</div>
@endsection
