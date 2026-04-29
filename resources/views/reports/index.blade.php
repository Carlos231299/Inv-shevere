@extends('layouts.app')

@section('content')
<div class="container">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px;">
        <span style="font-size: 2.5rem;">📊</span>
        <div>
            <h1 style="margin: 0; font-weight: 800; color: #333;">Centro de Reportes</h1>
            <p style="margin: 0; color: #666;">Gestione y exporte la información financiera de su negocio</p>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 25px;">
        
        <!-- 1. CUADRE DE CAJA DIARIO (ANCHO COMPLETO) -->
        <div class="card" style="background: #fff; border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="background: #1976d2; padding: 10px 20px; color: white; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.2rem;">📉</span>
                <h5 style="margin: 0; font-weight: 700; font-size: 1rem;">Cuadre de Caja Diario <span style="font-weight: normal; opacity: 0.8; font-size: 0.8rem; margin-left: 10px;">| Resumen detallado de ingresos y egresos</span></h5>
            </div>
            <div class="card-body" style="padding: 20px 25px;">
                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <label style="font-weight: 600; color: #666; font-size: 0.85rem; display: block; margin-bottom: 5px;">Seleccione Fecha para Reporte:</label>
                        <input type="date" id="dailyReportDate" class="form-control" value="{{ date('Y-m-d') }}" style="border-radius: 12px; border: 1px solid #ddd; padding: 10px; font-weight: 600;">
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="exportDaily('pdf')" class="btn btn-danger" style="padding: 10px 25px; border-radius: 12px; font-weight: 700; background: #d32f2f; border: none;">VER DÍA PDF</button>
                        <button onclick="exportDaily('excel')" class="btn btn-success" style="padding: 10px 25px; border-radius: 12px; font-weight: 700; background: #2e7d32; border: none;">DESCARGAR EXCEL</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILA DE 3 COLUMNAS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            
            <!-- 2. ESTADO DE RESULTADOS -->
            <div class="card" style="background: #fff; border-radius: 20px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; padding: 30px 20px;">
                <div style="font-size: 3rem; margin-bottom: 15px;">💰</div>
                <h5 style="font-weight: 700; color: #333; margin-bottom: 10px;">Estado de Resultados</h5>
                <p style="color: #888; font-size: 0.9rem; margin-bottom: 25px;">Ingresos, Costos, Gastos y Utilidad Neta.</p>
                <div style="margin-bottom: 20px; display: flex; gap: 10px; justify-content: center;">
                    <input type="date" id="finStartDate" value="{{ date('Y-m-01') }}" class="form-control form-control-sm" style="width: 120px; font-size: 0.8rem;">
                    <input type="date" id="finEndDate" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" style="width: 120px; font-size: 0.8rem;">
                </div>
                <div style="display: flex; gap: 8px; justify-content: center;">
                    <button onclick="goToReport('financial')" class="btn btn-primary" style="background: #d32f2f; border: none; border-radius: 15px; padding: 8px 25px; font-weight: 600;">Ver Reporte</button>
                    <button onclick="exportFinancial('pdf')" class="btn btn-sm btn-outline-danger" style="border-radius: 10px;">PDF</button>
                </div>
            </div>

            <!-- 3. INVENTARIO VALORIZADO -->
            <div class="card" style="background: #fff; border-radius: 20px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; padding: 30px 20px;">
                <div style="font-size: 3rem; margin-bottom: 15px;">📦</div>
                <h5 style="font-weight: 700; color: #333; margin-bottom: 10px;">Inventario Valorizado</h5>
                <p style="color: #888; font-size: 0.9rem; margin-bottom: 25px;">Stock actual y costo total de mercancía.</p>
                <div style="display: flex; gap: 8px; justify-content: center; margin-top: 20px;">
                    <a href="{{ route('reports.inventory') }}" class="btn btn-dark" style="background: #263238; border: none; border-radius: 15px; padding: 8px 25px; font-weight: 600; color: white; text-decoration: none;">Ver Inventario</a>
                    <button onclick="exportInventory('pdf')" class="btn btn-sm btn-outline-danger" style="border-radius: 10px;">PDF</button>
                    <button onclick="exportInventory('excel')" class="btn btn-sm btn-outline-success" style="border-radius: 10px;">XLS</button>
                </div>
            </div>

            <!-- 4. HISTORIAL COMPRAS Y VENTAS -->
            <div class="card" style="background: #fff; border-radius: 20px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; padding: 30px 20px;">
                <div style="font-size: 3rem; margin-bottom: 15px;">🗓️</div>
                <h5 style="font-weight: 700; color: #333; margin-bottom: 10px;">Historial Compras y Ventas</h5>
                <p style="color: #888; font-size: 0.9rem; margin-bottom: 25px;">Detalle de cada compra y venta realizada.</p>
                <div style="margin-bottom: 20px; display: flex; flex-direction: column; gap: 5px; align-items: center;">
                    <div style="display: flex; gap: 5px;">
                        <input type="date" id="salesStartDate" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" style="width: 120px; font-size: 0.8rem;">
                        <input type="date" id="salesEndDate" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" style="width: 120px; font-size: 0.8rem;">
                    </div>
                    <select id="salesType" class="form-select form-select-sm" style="width: 245px; font-size: 0.8rem; border-radius: 8px;">
                        <option value="all">Filtro: Todos</option>
                        <option value="sale">Ventas</option>
                        <option value="purchase">Compras</option>
                    </select>
                </div>
                <div style="display: flex; gap: 8px; justify-content: center;">
                    <button onclick="goToReport('sales')" class="btn btn-secondary" style="background: #6c757d; border: none; border-radius: 15px; padding: 8px 25px; font-weight: 600;">Ver Historial</button>
                    <button onclick="exportSales('pdf')" class="btn btn-sm btn-outline-danger" style="border-radius: 10px;">PDF</button>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
function exportDaily(format) {
    const date = document.getElementById('dailyReportDate').value;
    if (!date) {
        alert('Seleccione una fecha');
        return;
    }
    let url = format === 'pdf' ? "{{ route('reports.daily.pdf') }}" : "{{ route('reports.daily.export') }}";
    url += `?date=${date}`;
    window.open(url, format === 'pdf' ? '_blank' : '_self');
}

function exportFinancial(format) {
    const start = document.getElementById('finStartDate').value;
    const end = document.getElementById('finEndDate').value;
    let url = format === 'pdf' ? "{{ route('reports.financial.pdf') }}" : "{{ route('reports.financial.export') }}";
    url += `?start_date=${start}&end_date=${end}`;
    window.open(url, format === 'pdf' ? '_blank' : '_self');
}

function exportInventory(format) {
    let url = format === 'pdf' ? "{{ route('reports.inventory.pdf') }}" : "{{ route('reports.inventory.export') }}";
    window.open(url, format === 'pdf' ? '_blank' : '_self');
}

function exportSales(format) {
    const start = document.getElementById('salesStartDate').value;
    const end = document.getElementById('salesEndDate').value;
    const type = document.getElementById('salesType').value;
    let url = format === 'pdf' ? "{{ route('reports.purchases&sales.pdf') }}" : "{{ route('reports.purchases&sales.export') }}";
    url += `?start_date=${start}&end_date=${end}&type=${type}`;
    window.open(url, format === 'pdf' ? '_blank' : '_self');
}

function goToReport(type) {
    let url = "";
    if (type === 'financial') {
        const start = document.getElementById('finStartDate').value;
        const end = document.getElementById('finEndDate').value;
        url = `{{ route('reports.financial') }}?start_date=${start}&end_date=${end}`;
    } else if (type === 'sales') {
        const start = document.getElementById('salesStartDate').value;
        const end = document.getElementById('salesEndDate').value;
        const sub = document.getElementById('salesType').value;
        url = `{{ route('reports.purchases&sales') }}?start_date=${start}&end_date=${end}&type=${sub}`;
    }
    window.location.href = url;
}
</script>
@endsection
