<form action="{{ isset($expense) ? route('expenses.update', $expense->id) : route('expenses.store') }}" method="POST" onsubmit="return submitFormAjax(this)">
    @csrf
    @if(isset($expense))
        @method('PUT')
    @endif
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label for="expense_date" class="fw-bold text-muted small text-uppercase mb-2">Fecha *</label>
                <input type="date" name="expense_date" id="expense_date" class="form-control" value="{{ old('expense_date', isset($expense) ? $expense->expense_date->format('Y-m-d') : date('Y-m-d')) }}" required style="border-radius: 12px; padding: 12px; border: 1px solid #ced4da;">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label for="category_id" class="fw-bold text-muted small text-uppercase mb-2">Categoría *</label>
                <select name="category_id" id="modalCategorySelect" class="form-select" required style="border-radius: 12px; padding: 12px; border: 1px solid #ced4da;">
                    <option value="">Seleccione...</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $expense->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="form-group mb-3">
        <label for="description" class="fw-bold text-muted small text-uppercase mb-2">Descripción / Concepto *</label>
        <input type="text" name="description" id="description" class="form-control" value="{{ old('description', $expense->description ?? '') }}" placeholder="Ej: Pago de Luz, Compra de Bolsas..." required style="border-radius: 12px; padding: 12px; border: 1px solid #ced4da;">
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label for="amount" class="fw-bold text-muted small text-uppercase mb-2">Monto ($) *</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;">$</span>
                    <input type="number" step="1" name="amount" id="amount" class="form-control border-start-0" value="{{ old('amount', $expense->amount ?? '') }}" required style="border-radius: 0 12px 12px 0; padding: 12px; border: 1px solid #ced4da;">
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label for="payment_method" class="fw-bold text-muted small text-uppercase mb-2">Método de Pago *</label>
                <select name="payment_method" id="payment_method" class="form-select" required style="border-radius: 12px; padding: 12px; border: 1px solid #ced4da;">
                    <option value="cash" {{ old('payment_method', $expense->payment_method ?? 'cash') == 'cash' ? 'selected' : '' }}>💵 Efectivo</option>
                    <option value="nequi" {{ old('payment_method', $expense->payment_method ?? '') == 'nequi' ? 'selected' : '' }}>📱 Nequi</option>
                    <option value="bancolombia" {{ old('payment_method', $expense->payment_method ?? '') == 'bancolombia' ? 'selected' : '' }}>🏦 Bancolombia</option>
                </select>
            </div>
        </div>
    </div>



    <div class="d-grid">
        <button type="submit" class="btn btn-primary" style="padding: 14px; border-radius: 12px; font-weight: bold; font-size: 1.1rem; background-color: #8B0000; border-color: #8B0000;">
            <i class="fas fa-save me-2"></i> {{ isset($expense) ? 'Actualizar Gasto' : 'Registrar Gasto' }}
        </button>
    </div>
</form>
