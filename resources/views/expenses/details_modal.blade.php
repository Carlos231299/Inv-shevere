<div class="modal-header border-0 pb-0">
    <div class="d-flex align-items-center">
        <div class="rounded-circle bg-success bg-opacity-10 shadow-sm d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
            <span class="fs-3">📊</span>
        </div>
        <div>
            <h5 class="modal-title fw-bold text-dark text-uppercase mb-0">{{ $category->name }}</h5>
            <small class="text-muted">Historial de gastos (Rango Seleccionado)</small>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-4">
    <div class="row mb-3 bg-light p-2 rounded-3 mx-0">
        <div class="col text-center">
            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Total Acumulado</small>
            <span class="h4 fw-bold text-success mb-0">$ {{ number_format($expenses->sum('amount'), 0) }}</span>
        </div>
    </div>

    @if($expenses->count() > 0)
        <div class="list-group list-group-flush shadow-sm rounded-3">
            @foreach($expenses as $expense)
                <div class="list-group-item p-3 border-start-0 border-end-0 border-top-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-bold d-block text-dark">{{ $expense->description }}</span>
                            <small class="text-muted">
                                <span class="badge bg-secondary bg-opacity-10 text-muted border-0">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d/m/Y') }}</span>
                                <span class="badge bg-info bg-opacity-10 text-info border-0 text-uppercase">{{ $expense->payment_method }}</span>

                            </small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold d-block fs-5 text-dark">$ {{ number_format($expense->amount, 0) }}</span>
                            <div class="mt-1">
                                <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-sm btn-link text-primary p-0 open-expense-modal" data-title="Editar Gasto">✏️</a>
                                <span class="text-muted mx-1"></span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 delete-expense-btn" data-id="{{ $expense->id }}">🗑️</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-5">
            <div class="fs-1 opacity-25">📂</div>
            <p class="text-muted mt-2">No se encontraron gastos registrados para este periodo.</p>
        </div>
    @endif
</div>
