@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between;">
        <h2>👥 Clientes</h2>
        <a href="{{ route('clients.create') }}" class="btn btn-primary open-modal" data-title="Nuevo Cliente">+ Nuevo Cliente</a>
    </div>
    <div class="card-body">
        <form action="{{ route('clients.index') }}" method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o documento..." value="{{ request('search') }}" autocomplete="off">
            <button type="submit" class="btn btn-secondary">Buscar</button>
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->document }}</td>
                    <td>{{ $client->phone }}</td>
                    <td>{{ $client->address }}</td>
                    <td>
                        <div style="display: flex; gap: 5px; align-items: center;">
                            <a href="{{ route('credits.show', $client->id) }}" class="btn btn-sm btn-secondary" style="text-decoration: none; color: black;" title="Historial de crédito">
                                📄 <span class="d-none d-md-inline">Historial de crédito</span>
                            </a>
                            <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-sm btn-info open-modal" data-title="Editar Cliente: {{ $client->name }}" style="text-decoration: none; color: black;" title="Editar">
                                ✏️
                            </a>
                            <form id="delete-client-{{ $client->id }}" action="{{ route('clients.destroy', $client->id) }}" method="POST" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger" title="Eliminar" style="color: black;" onclick="confirmFormSubmit('delete-client-{{ $client->id }}')">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center;">No hay clientes registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $clients->links() }}
    </div>
</div>
@endsection
