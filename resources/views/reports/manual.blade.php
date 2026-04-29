@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manual de Usuario</h1>
        <a href="{{ route('reports.manual.pdf') }}" class="btn btn-danger">
            <span class="nav-icon">📄</span> Descargar PDF
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="section mb-5">
                <h2 class="h4 text-primary border-bottom pb-2">1. Bienvenido al Sistema</h2>
                <p>Este manual le guiará a través de las funciones principales de la plataforma diseñada para optimizar la gestión de <b>Carnicería Salomé</b>. El sistema está optimizado para el control preciso de inventarios, ventas rápidas y reportes financieros automáticos.</p>
                
                <div class="alert alert-info">
                    <strong>Conceptos Clave:</strong>
                    <ul class="mb-0">
                        <li><b>Lotes (Batches):</b> Cada vez que compra un producto, se crea un "lote". El sistema vende primero los lotes más antiguos (PEPS).</li>
                        <li><b>SKU/Código:</b> Identificador único (código de barras) de cada producto.</li>
                        <li><b>Movimientos:</b> Cada venta o compra genera un registro histórico inalterable.</li>
                    </ul>
                </div>
            </div>

            <div class="section mb-5">
                <h2 class="h4 text-primary border-bottom pb-2">2. Realizar Ventas (POS)</h2>
                <p>El módulo de ventas (Ventas) es el corazón de la operación diaria. Está diseñado para ser rápido y eficiente.</p>
                <h3 class="h5">Pasos para vender:</h3>
                <ul>
                    <li><b>Búsqueda:</b> Use el lector de códigos de barras en el campo de búsqueda o escriba el nombre/SKU del producto.</li>
                    <li><b>Cantidades y Precios:</b> Ajuste la cantidad. <span class="badge bg-warning text-dark">¡NUEVO!</span> Ahora puede modificar el <b>Precio de Venta</b> directamente en el recuadro de precio junto a la cantidad. Este cambio solo afecta a la venta actual y no altera el precio original del producto en el inventario.</li>
                    <li>El sistema soporta formato de moneda con separadores (ej: $1.200,50).</li>
                    <li><b>Cliente:</b> Seleccione un cliente existente o cree uno nuevo rápidamente desde el buscador (opcional).</li>
                    <li><b>Cobro:</b> Elija el método de pago. Si selecciona <b>Transferencia (Bancos)</b>, el sistema lo sumará en el reporte de "Bancos" del día.</li>
                    <li><b>Finalizar:</b> El sistema imprimirá automáticamente la factura térmica si está configurada.</li>
                </ul>
                <div class="alert alert-light border-start border-warning border-4">
                    <b>Tip:</b> Si el cliente paga en efectivo, el sistema le ayudará a calcular el cambio automáticamente.
                </div>
            </div>

            <div class="section mb-5">
                <h2 class="h4 text-primary border-bottom pb-2">3. Gestión de Compras e Inventario</h2>
                <p>El registro correcto de compras garantiza que su stock siempre esté actualizado y sus márgenes de ganancia sean reales.</p>
                <h3 class="h5">Entradas de Mercancía:</h3>
                <ul>
                    <li>Vaya al módulo <b>"Compras"</b>.</li>
                    <li>Seleccione el proveedor.</li>
                    <li>Agregue los productos. Para cada uno podrá definir el <span class="badge bg-secondary">Precio de Costo</span> y el <span class="badge bg-info text-dark">Precio de Venta</span>.</li>
                    <li>El sistema utiliza el formato estándar colombiano para precios (puntos para miles, coma para decimales). Ej: 2.500,00.</li>
                    <li>Al guardar, el stock del producto subirá automáticamente.</li>
                </ul>
                <h3 class="h5">Inventario:</h3>
                <ul>
                    <li>Consulte su stock actual en tiempo real en la pestaña <b>"Inventario"</b>.</li>
                    <li>Desde allí podrá descargar el <b>Catálogo de Códigos de Barras</b> para etiquetar sus productos.</li>
                </ul>
            </div>

            <div class="section mb-5">
                <h2 class="h4 text-primary border-bottom pb-2">4. Reportes Avanzados</h2>
                <p>El sistema genera datos automáticos para la toma de decisiones financieras.</p>
                <ul>
                    <li><b>Arqueo de Caja (Resumen Diario):</b>
                        <ul>
                            <li>Descargue el reporte (PDF o Excel) desde el botón azul del Panel de Control.</li>
                            <li><span class="badge bg-warning text-dark">¡NUEVO!</span> Se añadió una sección de <b>"Bancos / Transferencias"</b> que le dice exactamente cuánto dinero debe tener en sus cuentas bancarias por ventas del día.</li>
                            <li>El "Total Efectivo en Caja" le dice cuánto dinero físico debe tener en el cajón.</li>
                        </ul>
                    </li>
                    <li><b>Estado de Resultados (Financiero):</b>
                        <ul>
                            <li>Genere reportes de rentabilidad por fechas.</li>
                            <li><span class="badge bg-warning text-dark">¡NUEVO!</span> <b>Comparativa Real vs Esperada:</b>
                                <ul>
                                    <li><b>Columna Real:</b> Muestra la ganancia basada en el precio al que <i>realmente</i> vendió (útil si hizo descuentos o cambios de precio manuales).</li>
                                    <li><b>Columna Esperada:</b> Muestra cuánto <i>debería</i> haber ganado si hubiera vendido todo al precio de lista oficial.</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li><b>Historial de Movimientos:</b> Consulte y reimprima facturas antiguas.</li>
                </ul>
            </div>

            <div class="section">
                <h2 class="h4 text-primary border-bottom pb-2">5. Roles y Seguridad</h2>
                <ul>
                    <li><b>Administrador:</b> Acceso total a todos los módulos y configuración financiera.</li>
                    <li><b>Cajero:</b> Acceso restringido solo al POS de Ventas y consulta de productos. No puede ver costos ni reportes financieros.</li>
                </ul>
            </div>
        </div>
        <div class="card-footer text-muted text-center py-3">
            © {{ date('Y') }} Carnicería Salomé - Plataforma de Gestión de Inventarios.
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .section h2 { margin-top: 1.5rem; }
    .alert { font-size: 0.95rem; }
    .badge { font-weight: 500; }
</style>
@endpush
