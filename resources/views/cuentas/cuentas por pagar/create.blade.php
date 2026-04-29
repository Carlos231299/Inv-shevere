@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 600px; margin: 20px auto; border-radius: 20px; border: 1px solid #ebf0f5; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <div class="card-header" style="background: white; border-bottom: 1px solid #f0f0f0; border-radius: 20px 20px 0 0; padding: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; color: #333; font-weight: 700;">Nueva Cuenta por Pagar</h2>
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
        <form action="{{ route('cuentas-por-pagar.store') }}" method="POST">
            @csrf
            
            {{-- Provider Selection --}}
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Proveedor</label>
                <select name="provider_id" id="provider_id" class="form-control" style="border-radius: 12px; height: auto; padding: 10px;" onchange="toggleNewProvider()">
                    <option value="">-- Seleccionar Proveedor --</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->id }}" {{ old('provider_id') == $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                    @endforeach
                    <option value="new_provider_option" style="font-weight: bold; color: #007bff;">+ Nuevo Proveedor</option>
                </select>
                
                <div id="new-provider-container" style="margin-top: 10px; display: none;">
                    <label style="font-size: 0.9rem; color: #777;">Nombre del Nuevo Proveedor:</label>
                    <input type="text" name="new_provider_name" id="new_provider_name" class="form-control" placeholder="Escribe el nombre aquí..." value="{{ old('new_provider_name') }}" style="border-radius: 12px;">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                     <div class="form-group" style="margin-bottom: 20px;">
                        <label for="amount" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Monto Total ($)</label>
                        <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required style="border-radius: 12px; padding: 10px 15px; font-size: 1rem;" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="due_date" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Fecha Vencimiento</label>
                        <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date') }}" required style="border-radius: 12px; padding: 10px 15px; font-size: 1rem;" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="description" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Descripción / Notas</label>
                <textarea name="description" id="description" class="form-control" rows="3" style="border-radius: 12px; padding: 10px 15px;">{{ old('description') }}</textarea>
            </div>

            {{-- Hidden Status: Always Pending for Accounts Payable --}}
            <input type="hidden" name="status" value="pending">

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const select = document.getElementById('provider_id');
                    const container = document.getElementById('new-provider-container');
                    const input = document.getElementById('new_provider_name');
                    const form = select.closest('form');

                    // 1. Toggle visibility on change
                    function toggleVisibility() {
                        if (select.value === 'new_provider_option') {
                            container.style.display = 'block';
                            input.focus();
                        } else {
                            container.style.display = 'none';
                        }
                    }

                    select.addEventListener('change', toggleVisibility);
                    
                    // Run once on load in case of old input
                    toggleVisibility();

                    // 2. Handle Form Submission
                    form.addEventListener('submit', function(e) {
                        if (select.value === 'new_provider_option') {
                            // If "New Provider" is selected, we want 'provider_id' to be null/empty.
                            // But the select has the name "provider_id" and value "new_provider_option".
                            // This would fail validation 'exists:providers,id'.
                            
                            // Remove name from select so it's not sent
                            select.removeAttribute('name');
                            
                            // Append a hidden input with empty value for provider_id
                            // This ensures $request->provider_id is present but null/empty
                            const hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'provider_id';
                            hidden.value = '';
                            form.appendChild(hidden);
                        }
                    });
                });
            </script>

            <div style="text-align: right;">
                <a href="{{ route('cuentas-por-pagar.index') }}" class="btn" style="background: #f0f0f0; color: #555; border-radius: 12px; font-weight: 600;">Cancelar</a>    
                <button type="submit" class="btn btn-primary" style="padding: 12px 25px; border-radius: 12px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">Guardar Cuenta</button>
            </div>
        </form>
    </div>
</div>
@endsection
