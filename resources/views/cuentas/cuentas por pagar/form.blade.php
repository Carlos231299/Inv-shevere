<form action="{{ route('cuentas-por-pagar.update', $cuenta->id) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="form-group" style="margin-bottom: 20px;">
        <label style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Proveedor</label>
        <input type="text" class="form-control" value="{{ $cuenta->provider->name ?? 'Sin Proveedor' }}" disabled style="border-radius: 12px; background: #fafafa;">
        {{-- Hidden provider_id to pass validation or we can rely on existing if not changing --}}
         <input type="hidden" name="provider_id" value="{{ $cuenta->provider_id }}">
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
        <button type="submit" class="btn btn-primary" style="padding: 12px 25px; border-radius: 12px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">Actualizar Cuenta</button>
    </div>
</form>
