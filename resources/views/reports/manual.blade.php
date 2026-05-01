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
                <h2 class="h4 text-primary border-bottom pb-2">📘 1. Bienvenido al Sistema</h2>
                <p>Este manual le guiará a través del funcionamiento paso a paso del sistema de punto de venta (POS) y gestión de inventario de <b>{{ \App\Models\Setting::getBusinessName() ?? 'SHEVERE' }}</b>. Está diseñado para reflejar el flujo de trabajo actual, incluyendo la importación unificada, facturación e impresión térmica en modo Kiosk.</p>
            </div>

            <div class="section mb-5">
                <h2 class="h4 text-primary border-bottom pb-2">📦 2. Importación Unificada de Inventario (Excel)</h2>
                <p>El sistema utiliza una <b>Plantilla Unificada</b> (<code>Plantilla_Unificada.xlsx</code>) que hace todo el trabajo pesado por ti. Con un solo archivo, alimentas productos, compras y proveedores.</p>
                <h3 class="h5 mt-3">¿Cómo funciona la importación?</h3>
                <ul>
                    <li>Dirígete a <b>Gestión de Importación</b> en el menú principal.</li>
                    <li>Haz clic en <b>Descargar Plantilla</b> para obtener el archivo Excel con las columnas correctas.</li>
                    <li><b>Llenado del Excel:</b>
                        <ul>
                            <li><b>Productos:</b> Ingresa SKU, Nombre, Costo, Precio de Venta, Stock Inicial y Stock Mínimo.</li>
                            <li><b>Proveedores:</b> En las mismas filas del producto, llena los datos del proveedor (Nombre, NIT, Teléfono, Dirección).</li>
                        </ul>
                    </li>
                    <li><b>Sube la plantilla</b> usando el botón <b>Importar Plantilla Unificada</b>.</li>
                </ul>
                <div class="alert alert-info border-start border-info border-4 mt-3">
                    <strong>💡 Magia Automática:</strong> El sistema es inteligente. Si pones el mismo proveedor (Ej. "PROVISIONES EL FUTURO") en 50 filas distintas, el sistema <b>no</b> creará 50 proveedores. Creará <b>solo uno</b>, y meterá todos esos 50 productos dentro de <b>una sola gran compra</b> sumando el costo total de forma automática.
                </div>
            </div>

            <div class="section mb-5">
                <h2 class="h4 text-primary border-bottom pb-2">🏷️ 3. Generación de Códigos de Barras</h2>
                <p>Si necesitas etiquetar tus productos o tener un catálogo físico en caja para escanear rápido:</p>
                <ul>
                    <li>Ve al módulo de <b>Reportes</b> (o Inventario).</li>
                    <li>Haz clic en el botón para exportar <b>Códigos de Barras</b>.</li>
                    <li>El sistema generará automáticamente un PDF optimizado para tu <b>impresora térmica (80mm / 76mm)</b>.</li>
                    <li><b>Diseño de Rollo:</b> Cada producto saldrá en formato de tirilla con su SKU y barras escaneables, listo para que lo imprimas en tu ticketera como una tira continua sin cortes incómodos a los lados.</li>
                </ul>
            </div>

            <div class="section mb-5">
                <h2 class="h4 text-primary border-bottom pb-2">🛒 4. Módulo de Ventas (El Punto de Venta)</h2>
                <p>El cajero utiliza este módulo para registrar la salida de mercancía.</p>
                <h3 class="h5 mt-3">Registro de Productos</h3>
                <ul>
                    <li><b>Por Escáner:</b> Pasa el código de barras por el lector. El producto se agregará inmediatamente a la lista.</li>
                    <li><b>Búsqueda Manual:</b> Escribe el nombre o el SKU en la barra superior.</li>
                </ul>
                <h3 class="h5 mt-3">Métodos de Pago</h3>
                <p>Al darle a <b>Procesar Venta</b>, el sistema te permite elegir cómo está pagando el cliente:</p>
                <ul>
                    <li><b>Efectivo</b> (Calcula la devuelta/cambio automáticamente y registra el efectivo en caja).</li>
                    <li><b>Nequi / Bancolombia / Tarjeta / Transferencia</b> (Se registra directamente en los balances digitales).</li>
                    <li><b>Crédito / Fiado:</b> Te pedirá seleccionar o registrar el cliente al que se le fía.</li>
                    <li><b>Mixto / Parcial:</b> Por ejemplo, pagan una parte en efectivo y el resto por Nequi; o dejan un abono inicial a una compra de crédito.</li>
                </ul>
            </div>

            <div class="section mb-5">
                <h2 class="h4 text-primary border-bottom pb-2">🖨️ 5. Impresión de Tickets y Modo Kiosk</h2>
                <p>El ticket de venta se ha optimizado visualmente para no saturar de tinta térmica tu papel, usando un negro nítido de alta visibilidad y separadores en líneas punteadas adaptados para rollos de 80mm.</p>
                
                <h3 class="h5 mt-3">Impresión Automática (Kiosk Mode)</h3>
                <p>Si utilizas <b>Google Chrome</b> con el acceso directo configurado con <code>--kiosk-printing</code>:</p>
                <ul>
                    <li>Al terminar una venta, la pestaña del ticket se abre sola.</li>
                    <li>Tras 1 segundo exacto (tiempo de seguridad para procesar la información de fondo), <b>Chrome manda la impresión automáticamente sin mostrar ventanas de confirmación</b>.</li>
                    <li>El ticket sale directo de tu impresora sin interrupciones.</li>
                </ul>
                <div class="alert alert-warning border-start border-warning border-4 mt-3">
                    <strong>⚠️ REGLA DE ORO DEL KIOSK PRINTING:</strong> El sistema envía la impresión a la impresora que tengas marcada como <b>"Predeterminada"</b> en Windows. Asegúrate siempre de que tu impresora térmica POS tenga el chulito verde en el panel de control de Windows, de lo contrario, el sistema enviará los tickets a otra impresora (como "Guardar como PDF") y parecerá que no hace nada.
                </div>
            </div>

            <div class="section mb-5">
                <h2 class="h4 text-primary border-bottom pb-2">📊 6. Cierres y Reportes Diarios</h2>
                <p>En el <b>Dashboard (Panel Principal)</b>, podrás ver un resumen en tiempo real de tu día:</p>
                <ul>
                    <li><b>Cierres de Caja:</b> Te separará exactamente cuánto dinero debe haber en físico (efectivo de ventas + base inicial), y cuánto entró por canales digitales (Nequi, Bancolombia, etc.).</li>
                    <li><b>Deudas (Fiados):</b> Te mostrará cuánto dinero tienes pendiente por cobrar en créditos.</li>
                    <li><b>Stock Bajo:</b> Te avisará en rojo si un producto bajó del "Stock Mínimo" que configuraste en tu plantilla de Excel, para que pidas a tus proveedores a tiempo.</li>
                </ul>
            </div>

        </div>
        <div class="card-footer text-muted text-center py-3">
            © {{ date('Y') }} {{ \App\Models\Setting::getBusinessName() ?? 'SHEVERE' }} - Plataforma de Gestión de Inventarios.
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
