@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 600px; padding: 40px 20px;">
    <div class="card">
        <div class="card-header" style="background: #1976d2; color: white;">
            <h4 style="margin: 0;">💵 Ingresar Efectivo Inicial</h4>
        </div>
        <div class="card-body">
            <p style="color: #666; margin-bottom: 20px;">
                Registra el dinero en efectivo que tienes en caja al momento de iniciar el sistema. 
                Este valor será la base para todos los cálculos de arqueo.
            </p>

            <form method="POST" action="{{ route('initial-setup.cash-balance.store') }}">
                @csrf
                <div class="form-group">
                    <label for="initial_cash">Efectivo Inicial ($)</label>
                    <input type="number" 
                           step="0.01" 
                           name="initial_cash" 
                           id="initial_cash" 
                           class="form-control" 
                           value="{{ old('initial_cash', $initialCash) }}" 
                           required 
                           autocomplete="off"
                           style="font-size: 1.5rem; font-weight: bold; text-align: right;">
                    @error('initial_cash')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <strong>📌 Nota:</strong> Este valor solo puede modificarse mientras el Modo Inventario Inicial esté activo.
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="{{ route('initial-setup.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Efectivo Inicial</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
