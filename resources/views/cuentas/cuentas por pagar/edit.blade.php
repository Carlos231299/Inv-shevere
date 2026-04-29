@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 600px; margin: 20px auto; border-radius: 20px; border: 1px solid #ebf0f5; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <div class="card-header" style="background: white; border-bottom: 1px solid #f0f0f0; border-radius: 20px 20px 0 0; padding: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; color: #333; font-weight: 700;">Editar Cuenta por Pagar</h2>
        </div>
    </div>

    @if ($errors->any())
        <div style="background: #fff5f5; border: 1px solid #fcbdc0; padding: 15px; border-radius: 12px; margin: 20px 25px;">
            <ul style="margin-left: 20px; margin-bottom: 0; color: #c53030;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card-body" style="padding: 30px;">
        <form action="{{ route('cuentas-por-pagar.update', $cuenta->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            {{-- Provider (Read Only mostly, but let's allow change if needed, or keeping it disabled for integrity) --}}
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Proveedor</label>
                <input type="text" class="form-control" value="{{ $cuenta->provider->name ?? 'Sin Proveedor' }}" disabled style="border-radius: 12px; background: #fafafa;">
            </div>

            <div class="row">
                <div class="col-md-6">
                     <div class="form-group" style="margin-bottom: 20px;">
                        <label for="amount" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Monto Total ($)</label>
                        <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount', $cuenta->amount) }}" required style="border-radius: 12px; padding: 10px 15px; font-size: 1rem;" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="paid_amount" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Pagado Hasta Ahora ($)</label>
                        <input type="number" name="paid_amount" id="paid_amount" class="form-control" value="{{ old('paid_amount', $cuenta->paid_amount) }}" required style="border-radius: 12px; padding: 10px 15px; font-size: 1rem;" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="due_date" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Fecha Vencimiento</label>
                <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date', $cuenta->due_date ? $cuenta->due_date->format('Y-m-d') : '') }}" style="border-radius: 12px; padding: 10px 15px; font-size: 1rem;" autocomplete="off">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="description" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Descripción / Notas</label>
                <textarea name="description" id="description" class="form-control" rows="3" style="border-radius: 12px; padding: 10px 15px;">{{ old('description', $cuenta->description) }}</textarea>
            </div>

             <div class="form-group" style="margin-bottom: 30px;">
                <label for="status" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Estado</label>
                <select name="status" id="status" class="form-control" required style="border-radius: 12px; padding: 10px 15px; height: auto;">
                    <option value="pending" {{ $cuenta->status == 'pending' ? 'selected' : '' }}>Pendiente</option>
                    <option value="paid" {{ $cuenta->status == 'paid' ? 'selected' : '' }}>Pagado</option>
                    <option value="overdue" {{ $cuenta->status == 'overdue' ? 'selected' : '' }}>Vencido</option>
                </select>
            </div>

            <div style="text-align: right;">
            <a href="{{ route('cuentas-por-pagar.index') }}" class="btn" style="background: #f0f0f0; color: #555; border-radius: 12px; font-weight: 600;">Cancelar</a>    
            <button type="submit" class="btn btn-primary" style="padding: 12px 25px; border-radius: 12px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">Actualizar Cuenta</button>
            </div>
        </form>
    </div>
</div>
@endsection
