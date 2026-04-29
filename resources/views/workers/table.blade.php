<div class="table-responsive">
    <table class="table table-hover" style="border-collapse: separate; border-spacing: 0 10px;">
        <thead>
            <tr style="background: #f8f9fa;">
                <th style="padding: 15px; border: none; border-radius: 10px 0 0 10px;">Fecha</th>
                <th style="padding: 15px; border: none;">Trabajador</th>
                <th style="padding: 15px; border: none;">Método</th>
                <th style="padding: 15px; border: none;">Tipo</th>
                <th style="padding: 15px; border: none;">Monto</th>
                <th style="padding: 15px; border: none; border-radius: 0 10px 10px 0;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
            @php 
                $parts = explode(" - ", $expense->description);
                $concept = $parts[0] ?? 'PAGO';
                $workerName = $parts[1] ?? $expense->description;
            @endphp
            <tr style="background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                <td style="padding: 15px; border: 1px solid #f0f0f0; border-right: none; border-radius: 10px 0 0 10px;">
                    {{ \Carbon\Carbon::parse($expense->expense_date)->format('d/m/Y') }}
                </td>
                <td style="padding: 15px; border: 1px solid #f0f0f0; border-right: none; border-left: none;">
                    <span class="badge bg-secondary mb-1 d-block" style="font-size: 0.65rem;">{{ $concept }}</span>
                    <span class="fw-bold text-primary">{{ $workerName }}</span>
                </td>
                <td style="padding: 15px; border: 1px solid #f0f0f0; border-right: none; border-left: none;">
                    @if($expense->payment_method == 'cash')
                        <span class="badge" style="background: #e8f5e9; color: #1b5e20; padding: 4px 8px; border-radius: 6px;">💵 Efectivo</span>
                    @elseif($expense->payment_method == 'nequi')
                        <span class="badge" style="background: #fce4ec; color: #880e4f; padding: 4px 8px; border-radius: 6px;">📱 Nequi</span>
                    @elseif($expense->payment_method == 'bancolombia')
                        <span class="badge" style="background: #e3f2fd; color: #0d47a1; padding: 4px 8px; border-radius: 6px;">🏦 Bancolombia</span>
                    @endif
                </td>
                <td style="padding: 15px; border: 1px solid #f0f0f0; border-right: none; border-left: none;">
                    @if($expense->type == 'business')
                        <span class="badge bg-light text-dark border">Negocio</span>
                    @endif
                </td>
                <td style="padding: 15px; border: 1px solid #f0f0f0; border-right: none; border-left: none; font-weight: bold; color: #333;">
                    $ {{ number_format($expense->amount, 0, ',', '.') }}
                </td>
                <td style="padding: 15px; border: 1px solid #f0f0f0; border-left: none; border-radius: 0 10px 10px 0;">
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route('workers.edit', $expense->id) }}" class="btn open-worker-modal" data-title="Editar Pago" style="background: #fff9c4; color: #856404; padding: 6px 14px; border-radius: 8px; border: 1px solid #ffeeba; font-size: 0.85rem; font-weight: 600;">Editar</a>
                        <button type="button" class="btn" style="background: #ffebee; color: #c62828; padding: 6px 14px; border-radius: 8px; border: 1px solid #ffcdd2; font-size: 0.85rem; font-weight: 600;" onclick="confirmDeleteWorker({{ $expense->id }})">Eliminar</button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 30px; color: #888;">
                    No hay registros de trabajadores en este periodo.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 20px; display: flex; justify-content: center;">
    {{ $expenses->links() }}
</div>
