@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 600px; margin: 20px auto; border-radius: 20px; border: 1px solid #ebf0f5; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <div class="card-header" style="background: white; border-bottom: 1px solid #f0f0f0; border-radius: 20px 20px 0 0; padding: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; color: #333; font-weight: 700;">Editar Gasto</h2>
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
        <form action="{{ route('expenses.update', $expense->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="expense_date" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Fecha *</label>
                <input type="date" name="expense_date" id="expense_date" class="form-control" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required style="border-radius: 12px; padding: 10px 15px; font-size: 1rem;" autocomplete="off">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="description" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Descripción / Concepto *</label>
                <input type="text" name="description" id="description" class="form-control" value="{{ old('description', $expense->description) }}" required style="border-radius: 12px; padding: 10px 15px; font-size: 1rem;" autocomplete="off">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="amount" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Monto ($) *</label>
                <input type="number" step="1" name="amount" id="amount" class="form-control" value="{{ old('amount', $expense->amount) }}" required style="border-radius: 12px; padding: 10px 15px; font-size: 1rem;" autocomplete="off">
            </div>

            <div class="form-group mb-3">
                <label for="category_id" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Categoría</label>
                <select name="category_id" id="editCategorySelect" class="form-control" style="border-radius: 12px; padding: 10px 15px; font-size: 1rem;">
                    <option value="">-- Sin Categoría --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $expense->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mb-3" id="editWorkerContainer" style="display: none;">
                <label for="worker" class="text-primary fw-bold" style="margin-bottom: 8px; display: block;">👷 Seleccionar Trabajador</label>
                <select name="worker" id="editWorkerSelect" class="form-control border-primary" style="border-radius: 12px; padding: 10px 15px; font-size: 1rem; border-width: 2px;">
                    <option value="">-- Seleccione --</option>
                    <option value="BREINER">Breiner</option>
                    <option value="ANDRES">Andres</option>
                    <option value="JAIR">Jair</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="payment_method" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Método de Pago *</label>
                <select name="payment_method" id="payment_method" class="form-control" required style="border-radius: 12px; padding: 10px 15px; font-size: 1rem;">
                    <option value="cash" {{ old('payment_method', $expense->payment_method ?? 'cash') == 'cash' ? 'selected' : '' }}>💵 Efectivo</option>
                    <option value="nequi" {{ old('payment_method', $expense->payment_method ?? '') == 'nequi' ? 'selected' : '' }}>📱 Nequi</option>
                    <option value="bancolombia" {{ old('payment_method', $expense->payment_method ?? '') == 'bancolombia' ? 'selected' : '' }}>🏦 Bancolombia</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 30px;">
                <label for="type" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Tipo de Gasto *</label>
                <select name="type" id="type" class="form-control" required style="border-radius: 12px; padding: 10px 15px; font-size: 1rem; height: auto;">
                    <option value="business" {{ old('type', $expense->type) == 'business' ? 'selected' : '' }}>Gastos del Negocio</option>
                </select>
            </div>

            <div style="text-align: right;">
            <a href="{{ route('expenses.index') }}" class="btn btn-secondary" style="padding: 12px 25px; border-radius: 12px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">Cancelar</a>    
            <button type="submit" class="btn btn-primary" style="padding: 12px 25px; border-radius: 12px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">Actualizar Gasto</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const catSelect = document.getElementById('editCategorySelect');
        const workerContainer = document.getElementById('editWorkerContainer');
        const workerSelect = document.getElementById('editWorkerSelect');
        const descriptionInput = document.getElementById('description');
        
        function toggleWorker() {
            if (!catSelect.value) {
                workerContainer.style.display = 'none';
                return;
            }
            const selectedText = catSelect.options[catSelect.selectedIndex].text.trim().toUpperCase();
            
            if (selectedText.includes('TRABAJADORES')) {
                workerContainer.style.display = 'block';
                // Try to pre-select worker from description if editing and not already set
                if (!workerSelect.value) {
                    const currentDesc = descriptionInput.value.toUpperCase();
                    if (currentDesc.includes('BREINER')) workerSelect.value = 'BREINER';
                    else if (currentDesc.includes('ANDRES')) workerSelect.value = 'ANDRES';
                    else if (currentDesc.includes('JAIR')) workerSelect.value = 'JAIR';
                }
            } else {
                workerContainer.style.display = 'none';
            }
        }

        catSelect.addEventListener('change', toggleWorker);
        toggleWorker();
    });
</script>
@endpush
