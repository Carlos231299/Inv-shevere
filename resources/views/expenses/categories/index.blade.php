@extends('layouts.app')

@section('content')
<div class="card" style="border-radius: 20px; border: 1px solid #ebf0f5; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px;">
    <div class="card-header" style="background: white; border-bottom: 1px solid #f0f0f0; border-radius: 20px 20px 0 0; padding: 25px; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="{{ route('expenses.index') }}" class="btn" style="background: #f0f0f0; color: #555; border-radius: 12px; font-weight: 600;">⬅️ Gastos</a>
            <h2 style="margin: 0; color: #333; font-weight: 700;">Categorías de Gastos</h2>
        </div>
        <a href="{{ route('expense-categories.create') }}" class="btn btn-primary open-modal" data-title="Nueva Categoría" style="padding: 10px 20px; border-radius: 12px; font-weight: bold; font-size: 1rem;">
            + Nueva Categoría
        </a>
    </div>

    <div class="card-body" style="padding: 25px;">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px;">Nombre</th>
                        <th style="padding: 15px;">Gastos Asociados</th>
                        <th style="padding: 15px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td style="padding: 15px;">{{ $category->name }}</td>
                        <td style="padding: 15px;">{{ $category->expenses->count() }}</td>
                        <td style="padding: 15px;">
                            <div style="display: flex; gap: 8px;">
                                <a href="{{ route('expense-categories.edit', $category->id) }}" class="btn open-modal" data-title="Editar Categoría" style="background: #fff9c4; color: #856404; padding: 6px 14px; border-radius: 8px; border: 1px solid #ffeeba; font-size: 0.85rem; font-weight: 600;">Editar</a>
                                
                                <form action="{{ route('expense-categories.destroy', $category->id) }}" method="POST" id="delete-cat-{{ $category->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn" style="background: #ffebee; color: #c62828; padding: 6px 14px; border-radius: 8px; border: 1px solid #ffcdd2; font-size: 0.85rem; font-weight: 600;" onclick="confirmFormSubmit('delete-cat-{{ $category->id }}')">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center" style="padding: 30px;">No hay categorías registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
