@extends('layouts.app')

@section('content')
<div class="container-fluid" style="padding: 20px;">
    <h1 style="margin-bottom: 25px; color: #333; font-weight: 700;">Configuración del Sistema</h1>

    @if(session('success'))
        <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            {{ session('error') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-8 offset-md-2 mb-4">
            <!-- Initial Balances Card -->
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-primary text-white" style="border-radius: 15px 15px 0 0; padding: 15px 20px;">
                    <h5 class="mb-0 font-weight-bold">💰 Gestión de Saldos Iniciales</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">Define con cuánto dinero iniciaste en cada cuenta para que el sistema pueda cuadrar tus totales diarios.</p>
                    
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">💵 Base de Efectivo</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">$</span>
                                    <input type="number" name="initial_cash_balance" class="form-control border-0 bg-light" value="{{ \App\Models\Setting::getInitialCash() }}" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">📱 Base de Nequi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">$</span>
                                    <input type="number" name="initial_nequi_balance" class="form-control border-0 bg-light" value="{{ \App\Models\Setting::getInitialNequi() }}" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">🏦 Base Bancolombia</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">$</span>
                                    <input type="number" name="initial_bancolombia_balance" class="form-control border-0 bg-light" value="{{ \App\Models\Setting::getInitialBancolombia() }}" step="0.01">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-success px-4 rounded-pill font-weight-bold">
                                💾 Guardar Saldos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
            <!-- Danger Zone Card -->
            <div class="card" style="border: 1px solid #f5c6cb; border-radius: 15px; overflow: hidden;">
                <div class="card-header" style="background: #f8d7da; border-bottom: 1px solid #f5c6cb; padding: 20px;">
                    <h4 style="margin: 0; color: #721c24; font-weight: bold;">⚠️ Zona de Peligro</h4>
                </div>
                <div class="card-body" style="padding: 30px;">
                    <h5 style="color: #333; font-weight: 600;">Reiniciar Transacciones</h5>
                    <p style="color: #666; margin-bottom: 20px;">
                        Esta acción eliminará permanentemente todas las <strong>ventas, compras, movimientos de inventario, créditos y cuentas por pagar</strong>.
                        <br>
                        El stock de todos los productos se establecerá en 0.
                        <br>
                        <span style="color: #d33; font-weight: bold;">Tus Productos y Clientes NO serán eliminados.</span>
                    </p>

                    <div class="alert alert-warning border-0 shadow-sm mb-4" style="background: #fff3cd; color: #856404; border-radius: 10px;">
                        <strong>⚠️ Atención:</strong> Esta acción es irreversible. Una vez borrados, no podremos recuperar tus ventas ni compras.
                    </div>

                    <form action="{{ route('settings.reset_transactions') }}" method="POST" id="reset-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label font-weight-bold" style="color: #721c24;">Para continuar, escribe <code style="background: #f8d7da; padding: 2px 6px; border-radius: 4px; color: #721c24;">CONFIRMAR ELIMINACION</code> abajo:</label>
                            <input type="text" id="confirm-text" class="form-control" placeholder="Escribe aquí..." autocomplete="off" style="border: 2px solid #f8d7da; border-radius: 10px; padding: 12px;">
                        </div>
                        <button type="button" class="btn btn-danger w-100" id="reset-btn" onclick="confirmReset()" disabled style="background-color: #dc3545; border-color: #dc3545; padding: 12px 24px; font-weight: 600; border-radius: 10px; opacity: 0.5;">
                            💣 Eliminar Todas las Transacciones
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('confirm-text').addEventListener('input', function(e) {
        const btn = document.getElementById('reset-btn');
        if (e.target.value === 'CONFIRMAR ELIMINACION') {
            btn.disabled = false;
            btn.style.opacity = '1';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        }
    });

    function confirmReset() {
        Swal.fire({
            title: '¿Estás ABSOLUTAMENTE seguro?',
            text: "¡No podrás revertir esto! Se borrarán todas las ventas y compras.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, borrar todo',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('reset-form').submit();
            }
        })
    }
</script>
@endsection
