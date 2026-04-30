@extends('layouts.app')

@section('content')
    <div class="container-fluid" style="padding: 20px;">
        <h1 style="margin-bottom: 25px; color: var(--color-black); font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            Configuración del Sistema
        </h1>

        <div class="row">
            <!-- Business Details Action Card -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 action-card" onclick="openBusinessModal()"
                    style="border-radius: 20px; cursor: pointer; transition: all 0.3s ease; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                    <div class="card-body p-5 text-center">
                        <div class="icon-circle mb-4">🏢</div>
                        <h4 class="font-weight-bold mb-3" style="color: #2c3e50;">Datos del Negocio</h4>
                        <p class="text-muted mb-4">Actualiza el nombre, NIT, dirección y medios de contacto que
                            aparecerán en tus facturas.</p>
                        <button class="btn btn-primary rounded-pill px-5 py-2 font-weight-bold">Configurar Ahora</button>
                    </div>
                </div>
            </div>

            <!-- Initial Balances Action Card -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 action-card" onclick="openBalancesModal()"
                    style="border-radius: 20px; cursor: pointer; transition: all 0.3s ease; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                    <div class="card-body p-5 text-center">
                        <div class="icon-circle mb-4">💰</div>
                        <h4 class="font-weight-bold mb-3" style="color: #2c3e50;">Gestión de Saldos</h4>
                        <p class="text-muted mb-4">Define las bases iniciales de dinero para cuadrar tus cuentas
                            y arqueos diarios.</p>
                        <button class="btn btn-primary rounded-pill px-5 py-2 font-weight-bold">Gestionar Bases</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <!-- Danger Zone Card -->
                <div class="card"
                    style="border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 20px; overflow: hidden; background: white; box-shadow: 0 10px 30px rgba(239, 68, 68, 0.05);">
                    <div class="card-header border-0" style="background: rgba(239, 68, 68, 0.1); padding: 20px;">
                        <h4 style="margin: 0; color: #ff4d4d; font-weight: bold;">⚠️ Zona de Peligro</h4>
                    </div>
                    <div class="card-body p-4">
                        <h5 style="color: #2c3e50; font-weight: 600;">Reiniciar Transacciones</h5>
                        <p class="text-muted" style="margin-bottom: 20px;">
                            Esta acción eliminará permanentemente todas las <strong>ventas, compras, movimientos de
                                inventario, créditos y cuentas por pagar</strong>.
                        </p>

                        <form action="{{ route('settings.reset_transactions') }}" method="POST" id="reset-form">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label font-weight-bold" style="color: #ff4d4d;">Para continuar, escribe
                                    <code
                                        style="background: rgba(255,0,0,0.1); padding: 2px 6px; border-radius: 4px; color: #ff4d4d;">CONFIRMAR ELIMINACION</code>:</label>
                                <input type="text" id="confirm-text"
                                    class="form-control" placeholder="Escribe aquí..."
                                    autocomplete="off" style="border-radius: 12px; padding: 12px; border: 1px solid #ccc;">
                            </div>
                            <button type="button" class="btn btn-danger w-100 py-3" id="reset-btn" onclick="confirmReset()"
                                disabled style="border-radius: 12px; font-weight: 700; letter-spacing: 1px; opacity: 0.5;">
                                💣 ELIMINAR TODAS LAS TRANSACCIONES
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Datos del Negocio -->
    <div class="modal fade" id="businessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
                <div class="modal-header border-0"
                    style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); padding: 20px; color: white; border-radius: 25px 25px 0 0;">
                    <h5 class="modal-title font-weight-bold">🏢 Datos del Negocio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background: #f8f9fa; border-radius: 0 0 25px 25px;">
                    <form id="businessForm" action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label font-weight-bold text-dark">Nombre del Negocio</label>
                            <input type="text" name="business_name" class="form-control"
                                value="{{ \App\Models\Setting::getBusinessName() }}" required style="border-radius: 12px;">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold text-dark">NIT / ID</label>
                                <input type="text" name="business_nit"
                                    class="form-control"
                                    value="{{ \App\Models\Setting::getBusinessNit() }}" style="border-radius: 12px;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold text-dark">Teléfono</label>
                                <input type="text" name="business_phone"
                                    class="form-control"
                                    value="{{ \App\Models\Setting::getBusinessPhone() }}" style="border-radius: 12px;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold text-dark">Dirección</label>
                            <input type="text" name="business_address"
                                class="form-control"
                                value="{{ \App\Models\Setting::getBusinessAddress() }}" style="border-radius: 12px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold text-dark">Correo Electrónico</label>
                            <input type="email" name="business_email"
                                class="form-control"
                                value="{{ \App\Models\Setting::getBusinessEmail() }}" style="border-radius: 12px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold text-dark">Información de Pagos (Bancos/Nequi)</label>
                            <textarea name="business_payment_info" class="form-control"
                                rows="2"
                                style="border-radius: 12px;">{{ \App\Models\Setting::getBusinessPaymentInfo() }}</textarea>
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary px-5 rounded-pill font-weight-bold py-2 shadow-sm">💾 Guardar
                                Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Saldos Iniciales -->
    <div class="modal fade" id="balancesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
                <div class="modal-header border-0"
                    style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); padding: 20px; color: white; border-radius: 25px 25px 0 0;">
                    <h5 class="modal-title font-weight-bold">💰 Gestión de Saldos</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background: #f8f9fa; border-radius: 0 0 25px 25px;">
                    <form id="balancesForm" action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label class="form-label font-weight-bold text-dark">💵 Base de Efectivo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-secondary text-dark">$</span>
                                <input type="number" name="initial_cash_balance"
                                    class="form-control border-secondary"
                                    value="{{ \App\Models\Setting::getInitialCash() }}" step="0.01">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label font-weight-bold text-dark">📱 Base de Nequi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-secondary text-dark">$</span>
                                <input type="number" name="initial_nequi_balance"
                                    class="form-control border-secondary"
                                    value="{{ \App\Models\Setting::getInitialNequi() }}" step="0.01">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label font-weight-bold text-dark">🏦 Base Bancolombia</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-secondary text-dark">$</span>
                                <input type="number" name="initial_bancolombia_balance"
                                    class="form-control border-secondary"
                                    value="{{ \App\Models\Setting::getInitialBancolombia() }}" step="0.01">
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary px-5 rounded-pill font-weight-bold py-2 shadow-sm">💾 Guardar
                                Bases</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
            border-color: var(--color-primary) !important;
        }

        .icon-circle {
            display: inline-block;
            width: 100px;
            height: 100px;
            line-height: 100px;
            background: rgba(39, 174, 96, 0.1);
            border-radius: 50%;
            font-size: 3rem;
        }
    </style>
@endsection

@section('scripts')
    <script>
        let businessModal, balancesModal;

        document.addEventListener('DOMContentLoaded', function() {
            businessModal = new bootstrap.Modal(document.getElementById('businessModal'));
            balancesModal = new bootstrap.Modal(document.getElementById('balancesModal'));

            document.getElementById('businessForm').addEventListener('submit', function (e) {
                e.preventDefault();
                confirmAction(this, '¿Guardar datos del negocio?', 'La información se actualizará en todo el sistema.');
            });

            document.getElementById('balancesForm').addEventListener('submit', function (e) {
                e.preventDefault();
                confirmAction(this, '¿Actualizar saldos iniciales?', 'Esto modificará el arqueo de caja de hoy.');
            });

            document.getElementById('confirm-text').addEventListener('input', function (e) {
                const btn = document.getElementById('reset-btn');
                if (this.value === 'CONFIRMAR ELIMINACION') {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                } else {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                }
            });
        });

        function openBusinessModal() {
            if(businessModal) businessModal.show();
        }
        function openBalancesModal() {
            if(balancesModal) balancesModal.show();
        }

        function confirmAction(form, title, text) {
            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: 'var(--color-primary)',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Procesando...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    const formData = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Completado!',
                                    text: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', 'No se pudo procesar la solicitud.', 'error');
                            }
                        })
                        .catch(() => {
                            Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                        });
                }
            });
        }

        function confirmReset() {
            Swal.fire({
                title: '¿ESTÁS TOTALMENTE SEGURO?',
                text: "Esta acción eliminará todos los registros de ventas y compras.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'SÍ, BORRAR TODO',
                cancelButtonText: 'CANCELAR'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('reset-form').submit();
                }
            });
        }
    </script>

@endsection