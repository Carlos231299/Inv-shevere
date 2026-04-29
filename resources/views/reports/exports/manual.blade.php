<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manual de Usuario - Carnicería Salomé</title>
    <style>
        @page { margin: 2cm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 3px solid #8B0000; padding-bottom: 10px; margin-bottom: 30px; }
        .logo { color: #8B0000; font-size: 28px; font-weight: bold; }
        .subtitle { color: #555; font-size: 16px; margin-top: 5px; }
        
        h1 { color: #8B0000; font-size: 24px; border-left: 5px solid #8B0000; padding-left: 10px; margin-top: 30px; }
        h2 { color: #444; font-size: 18px; margin-top: 20px; }
        
        p { margin-bottom: 10px; text-align: justify; }
        .highlight { background-color: #fcf8e3; padding: 2px 5px; border-radius: 3px; font-weight: bold; }
        
        .section { margin-bottom: 40px; }
        .icon { font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif; }
        
        ul { margin-bottom: 15px; }
        li { margin-bottom: 8px; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
        
        .card { background: #f9f9f9; border: 1px solid #eee; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .tip { border-left: 4px solid #f0ad4e; background: #fcf8f2; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">CARNICERÍA SALOMÉ</div>
        <div class="subtitle">Manual de Usuario del Sistema de Inventario y Ventas</div>
    </div>

    <div class="section">
        <h1>1. Bienvenido al Sistema</h1>
        <p>Este manual le guiará a través de las funciones principales de la plataforma diseñada para optimizar la gestión de <b>Carnicería Salomé</b>. El sistema está optimizado para el control preciso de inventarios, ventas rápidas y reportes financieros automáticos.</p>
        
        <div class="card">
            <strong>Conceptos Clave:</strong>
            <ul>
                <li><b>Lotes (Batches):</b> Cada vez que compra un producto, se crea un "lote". El sistema vende primero los lotes más antiguos (PEPS).</li>
                <li><b>SKU/Código:</b> Identificador único (código de barras) de cada producto.</li>
                <li><b>Movimientos:</b> Cada venta o compra genera un registro histórico inalterable.</li>
            </ul>
        </div>
    </div>

    <div class="section">
        <h1>2. Realizar Ventas (POS)</h1>
        <p>El módulo de ventas (Ventas) es el corazón de la operación diaria. Está diseñado para ser rápido y eficiente.</p>
        <h2>Pasos para vender:</h2>
        <ul>
            <li><b>Búsqueda:</b> Use el lector de códigos de barras en el campo de búsqueda o escriba el nombre/SKU del producto.</li>
            <li><b>Cantidades y Precios:</b> Ajuste la cantidad. <span class="highlight">¡NUEVO!</span> Ahora puede modificar el <b>Precio de Venta</b> directamente en el recuadro de precio junto a la cantidad. Este cambio solo afecta a la venta actual y no altera el precio original del producto en el inventario.</li>
            <li>El sistema soporta formato de moneda con separadores (ej: $1.200,50).</li>
            <li><b>Cliente:</b> Seleccione un cliente existente o cree uno nuevo rápidamente desde el buscador (opcional).</li>
            <li><b>Cobro:</b> Elija el método de pago. Si selecciona <b>Transferencia (Bancos)</b>, el sistema lo sumará en el reporte de "Bancos" del día.</li>
            <li><b>Finalizar:</b> El sistema imprimirá automáticamente la factura térmica si está configurada.</li>
        </ul>
        <div class="tip">
            <b>Tip:</b> Si el cliente paga en efectivo, el sistema le ayudará a calcular el cambio automáticamente.
        </div>
    </div>

    <div class="section">
        <h1>3. Gestión de Compras e Inventario</h1>
        <p>El registro correcto de compras garantiza que su stock siempre esté actualizado y sus márgenes de ganancia sean reales.</p>
        <h2>Entradas de Mercancía:</h2>
        <ul>
            <li>Vaya al módulo <b>"Compras"</b>.</li>
            <li>Seleccione el proveedor.</li>
            <li>Agregue los productos. Para cada uno podrá definir el <span class="highlight">Precio de Costo</span> y el <span class="highlight">Precio de Venta</span>.
            <li>El sistema utiliza el formato estándar colombiano para precios (puntos para miles, coma para decilames). Ej: 2.500,00.</li>
            <li>Al guardar, el stock del producto subirá automáticamente.</li>
        </ul>
        <h2>Inventario:</h2>
        <ul>
            <li>Consulte su stock actual en tiempo real en la pestaña <b>"Inventario"</b>.</li>
            <li>Desde allí podrá descargar el <b>Catálogo de Códigos de Barras</b> para etiquetar sus productos.</li>
        </ul>
    </div>

    <div class="section">
        <h1>4. Reportes Avanzados</h1>
        <p>El sistema genera datos automáticos para la toma de decisiones financieras.</p>
        <ul>
            <li><b>Arqueo de Caja (Resumen Diario):</b>
                <ul>
                    <li>Descargue el reporte (PDF o Excel) desde el botón azul del Panel de Control.</li>
                    <li><span class="highlight">¡NUEVO!</span> Se añadió una sección de <b>"Bancos / Transferencias"</b> que le dice exactamente cuánto dinero debe tener en sus cuentas bancarias por ventas del día.</li>
                    <li>El "Total Efectivo en Caja" le dice cuánto dinero físico debe tener en el cajón.</li>
                </ul>
            </li>
            <li><b>Estado de Resultados (Financiero):</b>
                <ul>
                    <li>Genere reportes de rentabilidad por fechas.</li>
                    <li><span class="highlight">¡NUEVO!</span> <b>Comparativa Real vs Esperada:</b>
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
        <h1>5. Roles y Seguridad</h1>
        <ul>
            <li><b>Administrador:</b> Acceso total a todos los módulos y configuración financiera.</li>
            <li><b>Cajero:</b> Acceso restringido solo al POS de Ventas y consulta de productos. No puede ver costos ni reportes financieros.</li>
        </ul>
    </div>

    <div class="footer">
        © {{ date('Y') }} Carnicería Salomé - Plataforma de Gestión de Inventarios.
    </div>
</body>
</html>
