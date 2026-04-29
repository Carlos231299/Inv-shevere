@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Registrar Nuevo Gasto</h2>
        <a href="{{ route('expenses.index') }}" class="btn" style="background: #f0f0f0; color: #555; border-radius: 12px; font-weight: 600;">⬅️ Volver</a>
    </div>

    @if ($errors->any())
        <div style="background: #ffe6e6; border: 1px solid red; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
            <ul style="margin-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('expenses.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="expense_date">Fecha *</label>
            <input type="date" name="expense_date" id="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="description">Descripción / Concepto *</label>
            <input type="text" name="description" id="description" class="form-control" value="{{ old('description') }}" placeholder="Ej: Pago de Luz, Compra de Bolsas..." required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="amount">Monto ($) *</label>
            <input type="number" step="1" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required autocomplete="off">
        </div>

        <div class="form-group mb-3">
            <label for="category_id">Categoría</label>
            <select name="category_id" id="createCategorySelect" class="form-control">
                <option value="">-- Sin Categoría --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group mb-3" id="createWorkerContainer" style="display: none;">
            <label for="worker" class="text-primary fw-bold">👷 Seleccionar Trabajador</label>
            <select name="worker" id="createWorkerSelect" class="form-control border-primary">
                <option value="">-- Seleccione --</option>
                <option value="BREINER">BREINER</option>
                <option value="ANDRES">ANDRES</option>
                <option value="JAIR">JAIR</option>
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="payment_method">Método de Pago *</label>
            <select name="payment_method" id="payment_method" class="form-control" required>
                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>💵 Efectivo</option>
                <option value="nequi" {{ old('payment_method') == 'nequi' ? 'selected' : '' }}>📱 Nequi</option>
                <option value="bancolombia" {{ old('payment_method') == 'bancolombia' ? 'selected' : '' }}>🏦 Bancolombia</option>
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="type">Tipo de Gasto *</label>
            <select name="type" id="type" class="form-control" required>
                <option value="business" {{ old('type') == 'business' ? 'selected' : '' }}>Gastos del Negocio (Afecta Utilidad)</option>
            </select>
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn btn-primary">Registrar Gasto</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const catSelect = document.getElementById('createCategorySelect');
        const workerContainer = document.getElementById('createWorkerContainer');
        const workerSelect = document.getElementById('createWorkerSelect');
        
        function toggleWorker() {
            if (!catSelect.value) {
                workerContainer.style.display = 'none';
                return;
            }
            const selectedText = catSelect.options[catSelect.selectedIndex].text.trim().toUpperCase();
            
            // Check if TRABAJADORES is in the text (e.g. "PAGO TRABAJADORES", "TRABAJADORES ")
            if (selectedText.includes('TRABAJADORES')) {
                workerContainer.style.display = 'block';
                workerSelect.required = true;
            } else {
                workerContainer.style.display = 'none';
                workerSelect.value = '';
                workerSelect.required = false;
            }
        }

        catSelect.addEventListener('change', toggleWorker);
        toggleWorker();
    });
</script>
@endsection
