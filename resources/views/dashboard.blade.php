@extends('layouts.app')

@section('content')
    <div class="container-fluid" style="padding: 10px;">
        <div class="mb-4" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:32px; font-weight:700; color:#333; line-height:1;">
                Panel de control
            </span>
            <div>
                @if($activeRegister)
                    <span class="badge bg-success me-2" style="font-size: 1rem; padding: 10px;">🟢 Caja Abierta</span>
                    <button onclick="showAdjustmentModal('entry')" class="btn btn-success me-2" style="border:none; padding:10px 20px; font-weight:600; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                        ➕ Entrada
                    </button>
                    <button onclick="showAdjustmentModal('exit')" class="btn btn-warning me-2" style="border:none; padding:10px 20px; font-weight:600; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1); color: #fff;">
                        ➖ Salida
                    </button>
                    <button onclick="showCloseBoxModal()"
                        class="btn btn-danger me-2"
                        style="border:none; padding:10px 20px; font-weight:600; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                        🔒 Cerrar Caja (Cuadre)
                    </button>
                @else
                    <span class="badge bg-danger me-2" style="font-size: 1rem; padding: 10px;">🔴 Caja Cerrada</span>
                    <button onclick="showOpenBoxModal()"
                        class="btn btn-success me-2"
                        style="border:none; padding:10px 20px; font-weight:600; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                        🔓 Apertura de Caja
                    </button>
                @endif
                <a href="{{ route('cash-registers.index') }}" class="btn btn-secondary me-2" style="border:none; padding:10px 20px; font-weight:600; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    📜 Historial
                </a>
                <button onclick="showExportModal()"
                    class="btn btn-primary"
                    style="background:#1976d2; border:none; padding:10px 20px; font-weight:600; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    📅 Resumen del Día (Exportar)
                </button>
            </div>
    </div>

    <br>
    {{-- Cards Grid (Robust Responsive Grid) --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 30px;">
        <!-- Consolidated Financial Summary -->
        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header border-0 shadow-sm" style="background: linear-gradient(135deg, #8B0000 0%, #e53935 100%); padding: 25px;">
                <h4 class="mb-0 fw-bold text-white" style="letter-spacing: 0.5px;" align="center">Resumen Financiero del Día</h4>
                <p class="mb-0" style="color: rgba(255,255,255,0.8); font-size: 0.85rem;">Visión global de movimientos y utilidad</p>
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-white shadow-sm d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; font-size: 1.5rem;">
                                📊
                            </div>
                            <div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="row g-0">
                    <!-- Column 1: INGRESOS (Green) -->
                    <div class="col-md-4 border-end">
                        <div class="p-4" style="background-color: #f8fff9; height: 100%;">
                            <h6 class="text-uppercase fw-bold mb-3" style="color: #2e7d32; letter-spacing: 0.5px;">
                                🟢 Ingresos  por ventas y recaudo de cartera
                            </h6>
                            
                            <!-- Total Sales -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-end mb-1">
                                    <span class="text-secondary fw-medium">Ventas Totales</span>
                                    <span class="fs-5 fw-bold" style="color: #2e7d32; font-family: 'Roboto', sans-serif;">
                                        $ {{ number_format($salesToday, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>

                            <!-- Breakdown -->
                            <div class="d-flex flex-column gap-2 mb-4">
                                <div class="d-flex justify-content-between small text-muted border-bottom border-success border-opacity-25 pb-1">
                                    <span>💵 Pagos en Efectivo (Caja)</span>
                                    <span class="fw-bold text-dark">$ {{ number_format($salesTodayCash, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted border-bottom border-success border-opacity-25 pb-1">
                                    <span>📱 Pagos por Nequi</span>
                                    <span class="fw-bold text-dark">$ {{ number_format($salesTodayNequi, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted border-bottom border-success border-opacity-25 pb-1">
                                    <span>🏦 Pagos por Bancolombia</span>
                                    <span class="fw-bold text-dark">$ {{ number_format($salesTodayBancolombia, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted pb-1">
                                    <span>📝 Créditos Nuevos (Por Cobrar)</span>
                                    <span class="fw-bold text-warning">$ {{ number_format($salesTodayCredit, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <!-- Collections -->
                             <div class="p-3 rounded-3" style="background: rgba(46, 125, 50, 0.1);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-success fw-bold small">+ Recuado Cartera (Abonos Total)</span>
                                    <span class="fw-bold text-success">$ {{ number_format($collectedReceivablesToday, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <!-- Estimated Profit -->
                            <div class="p-3 rounded-3 mt-2" style="background: rgba(255, 152, 0, 0.1); border-left: 4px solid #ff9800;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold small" style="color: #e65100;">✨ Estado de resultados del dia</span>
                                    <span class="fw-bold" style="color: #e65100;">$ {{ number_format($profitToday, 0, ',', '.') }}</span>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">(Ventas - Costos de ventas - Gastos)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: EGRESOS (Red) -->
                    <div class="col-md-4 border-end">
                        <div class="p-4" style="background-color: #fffafa; height: 100%;">
                            <h6 class="text-uppercase fw-bold mb-3" style="color: #c62828; letter-spacing: 0.5px;">
                                🔴 Gastos y Salidas
                            </h6>

                            <!-- Total Outgo -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-end mb-1">
                                    <span class="text-secondary fw-medium">Total Salidas de Dinero</span>
                                    <span class="fs-5 fw-bold" style="color: #c62828; font-family: 'Roboto', sans-serif;">
                                        - $ {{ number_format($expensesDay + $cashPurchases + $paidPayablesToday, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>

                             <!-- Breakdown -->
                             <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between align-items-center p-2 rounded small" style="background: white; border: 1px solid #ffebee;">
                                    <span class="text-danger">🧾 Gastos (Efectivo)</span>
                                    <span class="fw-bold text-danger">- $ {{ number_format($expensesTodayCash, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center p-2 rounded small" style="background: white; border: 1px solid #ffebee;">
                                    <span class="text-danger">🧾 Gastos (Bancolombia)</span>
                                    <span class="fw-bold text-danger">- $ {{ number_format($expensesTodayBancolombia, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center p-2 rounded small" style="background: white; border: 1px solid #ffebee;">
                                    <span class="text-danger">🧾 Gastos (Nequi)</span>
                                    <span class="fw-bold text-danger">- $ {{ number_format($expensesTodayNequi, 0, ',', '.') }}</span>
                                </div>
                                
                                <!-- Costo de Ventas (Purchases) Breakdown -->
                                <div class="p-2 rounded border border-danger border-opacity-25" style="background: #fff8f8;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-danger fw-bold small">🛒 Costo de Ventas (Compras)</span>
                                        <span class="fw-bold text-danger">- $ {{ number_format($cashPurchases + $nequiPurchases + $bancolombiaPurchases + $creditPurchases, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted ps-2">
                                        <span>Efectivo:</span>
                                        <span>- $ {{ number_format($cashPurchases, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted ps-2">
                                        <span>Bancolombia:</span>
                                        <span>- $ {{ number_format($bancolombiaPurchases, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted ps-2">
                                        <span>Nequi:</span>
                                        <span>- $ {{ number_format($nequiPurchases, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted ps-2">
                                        <span>Crédito (Por Pagar):</span>
                                        <span>- $ {{ number_format($creditPurchases, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                <!-- Payables (Cuentas por Pagar) Breakdown -->
                                <div class="p-2 rounded border border-danger border-opacity-25" style="background: #fff8f8;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-danger fw-bold small">💳 Pagos a Proveedores</span>
                                        <span class="fw-bold text-danger">- $ {{ number_format($paidPayablesToday, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted ps-2">
                                        <span>Efectivo:</span>
                                        <span>- $ {{ number_format($paidPayablesCash, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted ps-2">
                                        <span>Bancolombia:</span>
                                        <span>- $ {{ number_format($paidPayablesBancolombia, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted ps-2">
                                        <span>Nequi:</span>
                                        <span>- $ {{ number_format($paidPayablesNequi, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                   <!-- Column 3: ARQUEO CAJA (Blue) -->
                    <div class="col-md-4">
                         <div class="p-4" style="background-color: #f0f7ff; height: 100%;">
                            <h6 class="text-uppercase fw-bold mb-3" style="color: #0d47a1; letter-spacing: 0.5px;">
                                🔵 Arqueo de Caja (Efectivo)
                            </h6>

                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between border-bottom border-primary border-opacity-25 pb-2">
                                    <span class="text-muted small">Saldo Inicial (Ayer)</span>
                                    <span class="fw-bold text-secondary">$ {{ number_format($previousDayBalance, 0, ',', '.') }}</span>
                                </div>
                                
                                <div class="d-flex justify-content-between text-success">
                                    <span class="small">(+) Total Entradas Efectivo</span>
                                    <!-- Sales Cash + Collections Cash -->
                                    <span class="fw-bold">$ {{ number_format($salesTodayCash, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between text-danger border-bottom border-primary border-opacity-25 pb-2">
                                    <span class="small">(-) Total Salidas Efectivo</span>
                                    <!-- Expenses + Purchases Cash + Payables Cash -->
                                    <span class="fw-bold">- $ {{ number_format($expensesTodayCash + $cashPurchases + $paidPayablesCash, 0, ',', '.') }}</span>
                                </div>

                                <div class="alert alert-primary mb-0 mt-2 text-center shadow-sm" style="border: none; background: #e3f2fd; color: #0d47a1;">
                                    <div class="small text-uppercase fw-bold opacity-75">Dinero en Caja</div>
                                    <div class="display-6 fw-bold my-1">
                                        $ {{ number_format($previousDayBalance + $salesTodayCash - ($expensesTodayCash + $cashPurchases + $paidPayablesCash), 0, ',', '.') }}
                                    </div>
                                    <small class="d-block mt-1 opacity-75">Debe coincidir con tu dinero físico</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts & Tables Row (Robust Flexbox) --}}
    <div class="row mb-4">
        <!-- Nequi Balance Card -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; background: linear-gradient(135deg, #fdf2f9 0%, #ffffff 100%);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-white shadow-sm p-3 me-3" style="font-size: 1.5rem;">📱</div>
                        <div>
                            <h6 class="text-uppercase fw-bold mb-0" style="color: #d81b60; letter-spacing: 1px;">Saldo Nequi</h6>
                            <small class="text-muted text-uppercase" style="font-size: 0.7rem;">Conciliación Digital</small>
                        </div>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-7">
                            <div class="display-6 fw-bold mb-0" style="color: #333;"><span>$ {{ number_format($nequiBalance, 0) }}</span></div>
                        </div>
                        <div class="col-5 text-end small text-muted">
                            <div class="mb-1">Base: <span class="fw-bold">$ {{ number_format($baseNequi, 0) }}</span></div>
                            <div class="mb-1 text-success">Entradas Hoy: +$ {{ number_format($incomeNequiToday, 0) }}</div>
                            <div class="text-danger">Salidas Hoy: -$ {{ number_format($paidPayablesNequi + $expensesTodayNequi + $nequiPurchases, 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bancolombia Balance Card -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-white shadow-sm p-3 me-3" style="font-size: 1.5rem;">🏦</div>
                        <div>
                            <h6 class="text-uppercase fw-bold mb-0" style="color: #0d47a1; letter-spacing: 1px;">Saldo Bancolombia</h6>
                            <small class="text-muted text-uppercase" style="font-size: 0.7rem;">Conciliación Digital</small>
                        </div>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-7">
                            <div class="display-6 fw-bold mb-0" style="color: #333;"><span>$ {{ number_format($bancolombiaBalance, 0) }}</span></div>
                        </div>
                        <div class="col-5 text-end small text-muted">
                            <div class="mb-1">Base: <span class="fw-bold">$ {{ number_format($baseBancolombia, 0) }}</span></div>
                            <div class="mb-1 text-success">Entradas Hoy: +$ {{ number_format($incomeBancolombiaToday, 0) }}</div>
                            <div class="text-danger">Salidas Hoy: -$ {{ number_format($paidPayablesBancolombia + $expensesTodayBancolombia + $bancolombiaPurchases, 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 25px;">
        <!-- Chart Column (Flex 2) -->
        <div style="flex: 2; min-width: 350px; margin-bottom: 20px;">
            <div class="card h-100" style="border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <div class="card-header bg-white" style="border-bottom: 1px solid #f0f0f0; padding: 20px; border-radius: 15px 15px 0 0;">
                    <h5 style="margin:0; color: #ffffff; font-weight: 600;">Ventas vs Compras (Últimos 7 días)</h5>
                </div>
                <div class="card-body" style="padding: 20px;">
                    <div style="position: relative; height: 350px; width: 100%;">
                        <canvas id="dashboardChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showExportModal() {
        Swal.fire({
            title: 'Resumen del Día',
            html: `
                <div class="mb-3 text-start">
                    <label class="form-label"><b>Seleccione la fecha:</b></label>
                    <input type="date" id="swal-date" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <p>¿En qué formato deseas descargar el resumen de esa fecha?</p>
            `,
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '📊 Excel',
            denyButtonText: '📄 PDF',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#217346',
            denyButtonColor: '#d32f2f'
        }).then((result) => {
            const date = document.getElementById('swal-date').value;
            if (!date && (result.isConfirmed || result.isDenied)) {
                Swal.fire('Error', 'Debe seleccionar una fecha', 'error');
                return;
            }

            if (result.isConfirmed) {
                window.location.href = `{{ route('reports.daily.export') }}?date=${date}`;
            } else if (result.isDenied) {
                window.open(`{{ route('reports.daily.pdf') }}?date=${date}`, '_blank');
            }
        });
    }

    function showOpenBoxModal() {
        Swal.fire({
            title: '🔓 Apertura de Caja',
            width: '500px',
            html: `
                <div class="alert alert-info small text-start">Ingrese los saldos iniciales (bases) para el día de hoy.</div>
                <div class="text-start mb-3">
                    <label class="form-label fw-bold">Efectivo en Caja (Base):</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">$</span>
                        <input type="number" id="open-cash" class="form-control" placeholder="0" min="0">
                    </div>
                </div>
                <div class="text-start mb-3">
                    <label class="form-label fw-bold">Saldo Inicial Nequi:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">$</span>
                        <input type="number" id="open-nequi" class="form-control" placeholder="0" min="0">
                    </div>
                </div>
                <div class="text-start mb-3">
                    <label class="form-label fw-bold">Saldo Inicial Bancolombia:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">$</span>
                        <input type="number" id="open-bancolombia" class="form-control" placeholder="0" min="0">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Abrir Caja',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745',
            preConfirm: () => {
                const cash = document.getElementById('open-cash').value;
                const nequi = document.getElementById('open-nequi').value;
                const bancolombia = document.getElementById('open-bancolombia').value;
                
                if (cash === '' || nequi === '' || bancolombia === '') {
                    Swal.showValidationMessage('Todos los saldos iniciales son requeridos (pueden ser 0)');
                }
                return { 
                    initial_cash: cash || 0,
                    initial_nequi: nequi || 0,
                    initial_bancolombia: bancolombia || 0
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ route("cash-registers.open") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('¡Caja Abierta!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                }).catch(err => Swal.fire('Error', 'Hubo un problema al procesar la solicitud.', 'error'));
            }
        });
    }

    function showAdjustmentModal(type) {
        const title = type === 'entry' ? '➕ Registrar Entrada de Dinero' : '➖ Registrar Salida / Retiro';
        const color = type === 'entry' ? '#28a745' : '#ffc107';
        
        Swal.fire({
            title: title,
            html: `
                <div class="text-start mb-3">
                    <label class="form-label fw-bold">Monto:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">$</span>
                        <input type="number" id="adj-amount" class="form-control" placeholder="0" min="0.01">
                    </div>
                </div>
                <div class="text-start mb-3">
                    <label class="form-label fw-bold">Método de Pago:</label>
                    <select id="adj-method" class="form-select">
                        <option value="cash">Efectivo</option>
                        <option value="nequi">Nequi</option>
                        <option value="bancolombia">Bancolombia</option>
                    </select>
                </div>
                <div class="text-start">
                    <label class="form-label fw-bold">Descripción / Motivo:</label>
                    <input type="text" id="adj-description" class="form-control" placeholder="Ej: Pago factura luz, Inyección de capital...">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Registrar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: color,
            preConfirm: () => {
                const amount = document.getElementById('adj-amount').value;
                const method = document.getElementById('adj-method').value;
                const description = document.getElementById('adj-description').value;
                
                if (!amount || amount <= 0) {
                    Swal.showValidationMessage('Debe ingresar un monto válido');
                }
                return { type, amount, payment_method: method, description };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ route("cash-registers.adjustment") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡Éxito!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    function showCloseBoxModal() {
        // Fetch System Totals First
        Swal.fire({
            title: 'Cargando totales...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('{{ route("cash-registers.totals") }}')
            .then(res => res.json())
            .then(data => {
                if(!data.success) {
                    Swal.fire('Error', data.message, 'error');
                    return;
                }

                Swal.fire({
                    title: '🔒 Cuadre y Cierre de Caja',
                    width: '600px',
                    html: `
                        <div class="alert alert-info text-start mb-3" style="font-size:0.9rem;">
                            Ingrese el dinero físico que tiene actualmente en su poder.
                        </div>
                        <div class="row text-start align-items-center mb-3">
                            <div class="col-4 fw-bold">Efectivo</div>
                            <div class="col-4 text-muted small">Sistema: $ ${new Intl.NumberFormat('es-CO').format(data.system_cash)}</div>
                            <div class="col-4"><input type="number" id="close-cash" class="form-control form-control-sm" placeholder="Físico"></div>
                        </div>
                        <div class="row text-start align-items-center mb-3">
                            <div class="col-4 fw-bold">Nequi</div>
                            <div class="col-4 text-muted small">Sistema: $ ${new Intl.NumberFormat('es-CO').format(data.system_nequi)}</div>
                            <div class="col-4"><input type="number" id="close-nequi" class="form-control form-control-sm" placeholder="Físico"></div>
                        </div>
                        <div class="row text-start align-items-center mb-3">
                            <div class="col-4 fw-bold">Bancolombia</div>
                            <div class="col-4 text-muted small">Sistema: $ ${new Intl.NumberFormat('es-CO').format(data.system_bancolombia)}</div>
                            <div class="col-4"><input type="number" id="close-bancolombia" class="form-control form-control-sm" placeholder="Físico"></div>
                        </div>
                        <div class="text-start mt-3">
                            <label class="form-label">Notas / Novedades:</label>
                            <textarea id="close-notes" class="form-control" rows="2" placeholder="Opcional..."></textarea>
                        </div>
                        <div class="form-check text-start mt-3">
                            <input class="form-check-input" type="checkbox" id="close-withdraw" checked>
                            <label class="form-check-input-label fw-bold text-danger" for="close-withdraw">
                                🏦 Retirar efectivo a Caja Fuerte (El panel quedará en $0)
                            </label>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Cerrar Caja e Imprimir',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    preConfirm: () => {
                        return {
                            physical_cash: document.getElementById('close-cash').value || 0,
                            physical_nequi: document.getElementById('close-nequi').value || 0,
                            physical_bancolombia: document.getElementById('close-bancolombia').value || 0,
                            notes: document.getElementById('close-notes').value,
                            withdraw_to_safe: document.getElementById('close-withdraw').checked
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('{{ route("cash-registers.close") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(result.value)
                        })
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                Swal.fire('¡Caja Cerrada!', data.message, 'success').then(() => {
                                    window.open('/cash-registers/' + data.id + '/ticket', '_blank');
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        }).catch(err => Swal.fire('Error', 'Hubo un problema al procesar la solicitud.', 'error'));
                    }
                });
            })
            .catch(err => Swal.fire('Error', 'No se pudieron obtener los saldos.', 'error'));
    }

    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('dashboardChart').getContext('2d');
        const data = @json($chartData);
        
        // Low Stock Alert Logic
        const lowStockProducts = @json($lowStockProducts);
        const lowStockCount = lowStockProducts.length;
        const todayStr = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
        const hideAlert = localStorage.getItem('hideLowStockAlert_' + todayStr);

        if (lowStockCount > 0 && !hideAlert) {
            // Build simple HTML table
            let tableHtml = `
                <div style="max-height: 250px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px;">
                    <table style="width:100%; text-align: left; border-collapse: collapse; font-size: 0.9rem;">
                        <thead style="position: sticky; top: 0; background: #f8f9fa;">
                            <tr>
                                <th style="padding: 8px; border-bottom: 1px solid #ddd;">Producto</th>
                                <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">Stock</th>
                                <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">Mín</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            lowStockProducts.forEach(p => {
                tableHtml += `
                    <tr>
                        <td style="padding: 6px 8px; border-bottom: 1px solid #f0f0f0;">${p.name}</td>
                        <td style="padding: 6px 8px; border-bottom: 1px solid #f0f0f0; text-align: center; color: #d32f2f; font-weight: bold;">${p.stock}</td>
                        <td style="padding: 6px 8px; border-bottom: 1px solid #f0f0f0; text-align: center; color: #777;">${p.min_stock}</td>
                    </tr>
                `;
            });

            tableHtml += `</tbody></table></div>`;

            Swal.fire({
                title: '⚠️ Alerta de Stock Bajo',
                html: `<p style="margin-bottom: 15px;">Tienes <b>${lowStockCount} productos</b> por debajo del stock mínimo:</p>${tableHtml}`,
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '📦 Ir a Inventario',
                denyButtonText: '🔕 No mostrar más hoy',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#1976d2',
                denyButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('products.index') }}";
                } else if (result.isDenied) {
                    localStorage.setItem('hideLowStockAlert_' + todayStr, 'true');
                    Swal.fire('Entendido', 'No te recordaremos esto por hoy.', 'success');
                }
            });
        }

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Ventas',
                        data: data.sales,
                        backgroundColor: 'rgba(46, 125, 50, 0.7)',
                        borderColor: 'rgba(46, 125, 50, 1)',
                        borderWidth: 1,
                        borderRadius: 5
                    },
                    {
                        label: 'Compras',
                        data: data.purchases,
                        backgroundColor: 'rgba(25, 118, 210, 0.7)',
                        borderColor: 'rgba(25, 118, 210, 1)',
                        borderWidth: 1,
                        borderRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection


