@extends('layouts.app')

@section('content')
<div class="container-fluid" style="padding: 20px;">
    <div class="mb-4">
        <h2 style="font-weight: 700; color: #333;">⚙️ Configuración Inicial del Sistema</h2>
        <p style="color: #666;">Gestiona el inventario inicial, efectivo base y cuentas iniciales de tu negocio.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Status Card -->
    <div class="card mb-4" style="border-left: 5px solid {{ $isInitialMode ? '#ff9800' : '#4caf50' }};">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="margin: 0; color: {{ $isInitialMode ? '#ff9800' : '#4caf50' }};">
                        {{ $isInitialMode ? '🟠 Modo Inventario Inicial ACTIVO' : '✅ Modo Normal (Operativo)' }}
                    </h4>
                    <p style="margin: 5px 0 0 0; color: #666;">
                        @if($isInitialMode)
                            Todos los productos y movimientos se marcarán como inventario inicial.
                        @else
                            El sistema está operando normalmente. Al guardar saldos iniciales, este modo se cierra automáticamente.
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('initial-setup.toggle-mode') }}" id="toggleModeForm" style="margin: 0;">
                    @csrf
                    <button type="button" class="btn {{ $isInitialMode ? 'btn-danger' : 'btn-warning' }}" 
                            onclick="confirmToggleMode({{ $isInitialMode ? 'true' : 'false' }})">
                        {{ $isInitialMode ? '🔒 Cerrar Modo Inicial' : '🔓 Activar Modo Inicial' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <!-- Initial Cash -->
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <h6 style="color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 0.8rem;">💵 Efectivo Inicial</h6>
                <h2 style="font-weight: bold; color: #2e7d32; margin: 10px 0;">${{ number_format($initialCash, 0, ',', '.') }}</h2>
                @if($isInitialMode)
                    <button type="button" class="btn btn-sm btn-primary" onclick="openCashModal()">Modificar</button>
                @endif
            </div>
        </div>

        <!-- Initial Digital Accounts -->
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <h6 style="color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 0.8rem;">📱 Cuentas Digitales</h6>
                <div class="d-flex justify-content-around mt-2">
                    <div>
                        <small class="text-muted d-block">Nequi</small>
                        <span class="fw-bold" style="color: #6a1b9a;">${{ number_format(App\Models\Setting::getInitialNequi(), 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Bancolombia</small>
                        <span class="fw-bold" style="color: #0d47a1;">${{ number_format(App\Models\Setting::getInitialBancolombia(), 0, ',', '.') }}</span>
                    </div>
                </div>
                @if($isInitialMode)
                    <button type="button" class="btn btn-sm btn-primary mt-2" onclick="openBanksModal()">Modificar</button>
                @endif
            </div>
        </div>

        <!-- Initial Inventory -->
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <h6 style="color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 0.8rem;">📦 Productos Iniciales</h6>
                <h2 style="font-weight: bold; color: #1976d2; margin: 10px 0;">{{ $initialInventoryCount }}</h2>
                @if($isInitialMode)
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openProductImportModal()">📦 Importar Excel (Prod, Prov y Compras)</button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Oculto temporalmente Cuentas por Cobrar y Pagar 
        <!-- Initial Credits -->
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <h6 style="color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 0.8rem;">📋 Cuentas por Cobrar</h6>
                <h2 style="font-weight: bold; color: #f57c00; margin: 10px 0;">${{ number_format($initialCreditsSum, 0, ',', '.') }}</h2>
                @if($isInitialMode)
                    <a href="{{ route('credits.index') }}" class="btn btn-sm btn-primary">Registrar Cuenta</a>
                @else
                     <a href="{{ route('credits.index') }}" style="color: #f57c00; font-size: 0.8rem;">Ver Detalle</a>
                @endif
            </div>
        </div>

        <!-- Initial Payables -->
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <h6 style="color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 0.8rem;">💳 Cuentas por Pagar</h6>
                <h2 style="font-weight: bold; color: #d32f2f; margin: 10px 0;">${{ number_format($initialPayablesSum, 0, ',', '.') }}</h2>
                 @if($isInitialMode)
                    <a href="{{ route('cuentas-por-pagar.index') }}" class="btn btn-sm btn-primary">Registrar Deuda</a>
                @else
                     <a href="{{ route('cuentas-por-pagar.index') }}" style="color: #d32f2f; font-size: 0.8rem;">Ver Detalle</a>
                @endif
            </div>
        </div>
        --}}

        <!-- Initial Providers -->
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <h6 style="color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 0.8rem;">👤 Proveedores</h6>
                <h2 style="font-weight: bold; color: #6a1b9a; margin: 10px 0;">{{ \App\Models\Provider::count() }}</h2>
                @if($isInitialMode)
                    <small style="color: #666;">Se importan junto con los productos.</small>
                @endif
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="card">
        <div class="card-header" style="background: #b04b4bff;">
            <h5 style="margin: 0;">📖 Instrucciones</h5>
        </div>
        <div class="card-body">
            <ol style="line-height: 2;">
                <li><strong>Activar Modo Inicial:</strong> Haz clic en "Activar Modo Inicial" para comenzar.</li>
                <li><strong>Ingresar Efectivo y Bancos:</strong> Registra el dinero que tienes en caja, Nequi y Bancolombia.</li>
                <li><strong>Registrar Inventario:</strong> Ve a "Compras" y registra todos tus productos iniciales.</li>
                <li><strong>Auto-Cierre:</strong> Al guardar tus saldos finales de caja y bancos, el sistema **cerrará automáticamente** el modo inicial para proteger tus registros operativos.</li>
            </ol>
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
                <strong>⚠️ Importante:</strong> Una vez cerrado el modo inicial, solo podrás reabrirlo con confirmación especial. Los movimientos iniciales no afectarán tus reportes de utilidad.
            </div>
        </div>
    </div>

    <!-- DANGER ZONE -->
    <div class="card" style="border: 2px solid #d32f2f; margin-top: 30px;">
        <div class="card-header" style="background: #d32f2f; color: white;">
            <h5 style="margin: 0;">⚠️ Zona de Peligro</h5>
        </div>
        <div class="card-body">
            <h6 style="font-weight: bold; color: #d32f2f;">Resetear Base de Datos</h6>
            <p style="color: #666; margin-bottom: 15px;">
                Esta acción <strong>eliminará permanentemente</strong> todos los movimientos, ventas, compras, créditos, gastos y configuraciones. 
                Úsala solo si quieres empezar completamente desde cero.
            </p>
            <form method="POST" action="{{ route('initial-setup.reset') }}" id="resetDatabaseForm">
                @csrf
                <button type="button" class="btn btn-danger" onclick="confirmResetDatabase()">
                    🗑️ Resetear Todo y Empezar de Cero
                </button>
            </form>
        </div>
    </div>

    <!-- Modals -->
    <!-- Modal Efectivo -->
    <div class="modal fade" id="cashModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('initial-setup.store-cash') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">💵 Definir Efectivo Inicial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Este valor reemplazará el saldo actual y se tomará como el punto de partida real.</p>
                    <div class="mb-3">
                        <label>Monto en Efectivo</label>
                        <input type="number" name="initial_cash" class="form-control" value="{{ $initialCash }}" required min="0">
                    </div>
                    <div class="alert alert-warning small">
                        <strong>Nota:</strong> Al guardar, el Modo Inicial se cerrará automáticamente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar y Cerrar Modo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Bancos -->
    <div class="modal fade" id="banksModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('initial-setup.store-banks') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">📱 Definir Saldos Digitales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Estos valores reemplazarán los saldos bancarios actuales.</p>
                    <div class="mb-3">
                        <label>Saldo Nequi</label>
                        <input type="number" name="initial_nequi" class="form-control" value="{{ App\Models\Setting::getInitialNequi() }}" required min="0">
                    </div>
                    <div class="mb-3">
                        <label>Saldo Bancolombia</label>
                        <input type="number" name="initial_bancolombia" class="form-control" value="{{ App\Models\Setting::getInitialBancolombia() }}" required min="0">
                    </div>
                    <div class="alert alert-warning small">
                        <strong>Nota:</strong> Al guardar, el Modo Inicial se cerrará automáticamente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar y Cerrar Modo</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Importar Productos -->
    <div class="modal fade" id="productImportModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('initial-setup.import-products') }}" method="POST" enctype="multipart/form-data" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">📦 Importación Masiva (Productos, Proveedores y Compras)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">1. Descarga la plantilla unificada</label>
                        <a href="{{ route('initial-setup.template-products') }}" class="btn btn-sm btn-info text-white d-block">
                            📥 Plantilla_Unificada.xlsx
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">2. Sube tu archivo</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                        <div class="form-text">Solo archivos .xlsx o .xls</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Iniciar Importación</button>
                </div>
            </form>
        </div>
    </div>


</div>
@endsection

@section('scripts')
<script>
function openCashModal() {
    new bootstrap.Modal(document.getElementById('cashModal')).show();
}

function openBanksModal() {
    new bootstrap.Modal(document.getElementById('banksModal')).show();
}

function openProductImportModal() {
    new bootstrap.Modal(document.getElementById('productImportModal')).show();
}

function openProviderImportModal() {
    new bootstrap.Modal(document.getElementById('providerImportModal')).show();
}

function confirmToggleMode(isClosing) {
    const title = isClosing ? '¿Cerrar Modo Inicial?' : '¿Activar Modo Inicial?';
    const text = isClosing 
        ? 'Esta acción marcará el fin de la configuración base. Los movimientos iniciales quedarán registrados permanentemente.'
        : 'Solo puedes activar este modo si no hay movimientos operativos. Todos los productos y compras se marcarán como inventario inicial.';
    const icon = isClosing ? 'warning' : 'question';
    const confirmButtonText = isClosing ? 'Sí, cerrar modo' : 'Sí, activar modo';
    
    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: isClosing ? '#d33' : '#ff9800',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('toggleModeForm').submit();
        }
    });
}

function confirmResetDatabase() {
    Swal.fire({
        title: '⛔ PELIGRO: ¿BORRAR TODO?',
        html: '<p style="color:red; font-weight:bold;">ESTA ACCIÓN ES DESTRUCTIVA E IRREVERSIBLE.</p>' +
              '<p>Al confirmar, se eliminará permanentemente:</p>' +
              '<ul style="text-align: left; margin: 10px 0; color: #555;">' +
              '<li>❌ Todo el historial de Ventas y Compras</li>' +
              '<li>❌ Todos los Movimientos de Inventario</li>' +
              '<li>❌ Todas las Cuentas por Cobrar y Pagar</li>' +
              '<li>❌ Todos los Gastos registrados</li>' +
              '</ul>' +
              '<p>El sistema quedará <strong>VACÍO</strong> como el primer día.</p>' +
              '<p style="margin-top:15px;">Para confirmar, escribe: <strong>BORRAR DATOS</strong></p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '⚠️ SÍ, ELIMINAR TODO',
        cancelButtonText: 'Cancelar, llévame a salvo',
        input: 'text',
        inputPlaceholder: 'Escribe BORRAR DATOS aquí',
        inputAttributes: {
            autocapitalize: 'off'
        },
        inputValidator: (value) => {
            if (value !== 'BORRAR DATOS') {
                return 'Debes escribir exactamente: BORRAR DATOS'
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: '¿Última oportunidad?',
                text: "No podrás deshacer esto. ¿Proceder?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: '¡HAZLO!'
            }).then((lastResult) => {
                 if (lastResult.isConfirmed) {
                     document.getElementById('resetDatabaseForm').submit();
                 }
            });
        }
    });
}
</script>
@endsection
