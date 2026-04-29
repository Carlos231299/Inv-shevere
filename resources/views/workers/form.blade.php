<form action="{{ isset($expense) ? route('workers.update', $expense->id) : route('workers.store') }}" 
      method="POST" id="workerExpenseForm" onsubmit="event.preventDefault(); submitWorkerForm(this);">
    @csrf
    @if(isset($expense))
        @method('PUT')
    @endif

    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">👷 Trabajador</label>
            <select name="worker" id="worker_field" class="form-select border-primary" style="border-radius: 10px; padding: 12px;" required>
                <option value="">-- Seleccionar --</option>
                <option value="BREINER" {{ (isset($worker) && $worker == 'BREINER') ? 'selected' : '' }}>BREINER</option>
                <option value="ANDRES" {{ (isset($worker) && $worker == 'ANDRES') ? 'selected' : '' }}>ANDRES</option>
                <option value="JAIR" {{ (isset($worker) && $worker == 'JAIR') ? 'selected' : '' }}>JAIR</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">📝 Concepto</label>
            <select name="concept" id="concept_field" class="form-select border-primary" style="border-radius: 10px; padding: 12px;" required>
                <option value="PAGO TRABAJADOR" {{ (!isset($currentConcept) || $currentConcept == 'PAGO TRABAJADOR') ? 'selected' : '' }}>PAGO SEMANA</option>
                <option value="ADELANTO" {{ (isset($currentConcept) && $currentConcept == 'ADELANTO') ? 'selected' : '' }}>ADELANTO</option>
                <option value="PRESTAMO" {{ (isset($currentConcept) && $currentConcept == 'PRESTAMO') ? 'selected' : '' }}>PRÉSTAMO</option>
                <option value="OTROS" {{ (isset($currentConcept) && $currentConcept == 'OTROS') ? 'selected' : '' }}>OTROS</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold">💰 Monto del Pago</label>
        <div class="input-group">
            <span class="input-group-text bg-light">$</span>
            <input type="text" name="amount" id="amount_field" class="form-control currency-input" 
                   value="{{ isset($expense) ? number_format($expense->amount, 0, ',', '.') : '' }}" 
                   placeholder="0" required style="font-size: 1.2rem; font-weight: bold; padding: 12px;">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold">📅 Fecha del Pago</label>
        <input type="date" name="expense_date" class="form-control" 
               value="{{ isset($expense) ? $expense->expense_date : date('Y-m-d') }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold">💳 Método de Pago</label>
        <select name="payment_method" class="form-select" required>
            <option value="cash" {{ (isset($expense) && $expense->payment_method == 'cash') ? 'selected' : '' }}>💵 Efectivo</option>
            <option value="nequi" {{ (isset($expense) && $expense->payment_method == 'nequi') ? 'selected' : '' }}>📱 Nequi</option>
            <option value="bancolombia" {{ (isset($expense) && $expense->payment_method == 'bancolombia') ? 'selected' : '' }}>🏦 Bancolombia</option>
        </select>
    </div>
    <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 12px; font-weight: bold; padding: 15px;">
            {{ isset($expense) ? '💾 Actualizar Pago' : '✅ Registrar Pago' }}
        </button>
    </div>
</form>

<script>
    // Simple currency formatter during typing
    document.getElementById('amount_field').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, "");
        if (value === "") {
            e.target.value = "";
            return;
        }
        e.target.value = new Intl.NumberFormat('es-CO').format(value);
    });
</script>
