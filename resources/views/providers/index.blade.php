@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Gestión de Proveedores</h2>
        <a href="{{ route('providers.create') }}" class="btn btn-primary open-modal" data-title="Nuevo Proveedor">
            + Nuevo Proveedor
        </a>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('providers.index') }}" method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre, NIT o contacto..." value="{{ request('search') }}" autocomplete="off">
                <button class="btn btn-outline-secondary" type="submit">Buscar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>NIT</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $provider)
                    <tr>
                        <td style="font-weight: 500;">{{ $provider->name }}</td>
                        <td>{{ $provider->nit ?? '-' }}</td>
                        <td>{{ $provider->contact_name ?? '-' }}</td>
                        <td>{{ $provider->phone ?? '-' }}</td>
                        <td>{{ $provider->email ?? '-' }}</td>
                        <td>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <a href="{{ route('providers.edit', $provider) }}" class="btn btn-sm btn-info open-modal" data-title="Editar Proveedor: {{ $provider->name }}" style="text-decoration: none; color: black;" title="Editar">
                                    ✏️
                                </a>
                                <form action="{{ route('providers.destroy', $provider) }}" method="POST" id="delete-form-{{ $provider->id }}" class="d-inline" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-danger" title="Eliminar" style="color: black;" onclick="confirmFormSubmit('delete-form-{{ $provider->id }}', '¿Eliminar proveedor?', 'Esta acción no se puede deshacer.')">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No se encontraron proveedores.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $providers->links() }}
        </div>
    </div>
</div>
@endsection
