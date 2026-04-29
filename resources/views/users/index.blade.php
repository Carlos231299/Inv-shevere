@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Gestión de Usuarios</h2>
        <a href="{{ route('users.create') }}" class="btn btn-primary open-modal" data-title="Nuevo Usuario">
            + Nuevo Usuario
        </a>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('users.index') }}" method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o email..." value="{{ request('search') }}" autocomplete="off">
                <button class="btn btn-outline-secondary" type="submit">Buscar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td style="font-weight: 500;">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->role === 'admin')
                                <span class="badge bg-danger">Administrador</span>
                            @else
                                <span class="badge bg-success">Cajero</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-info open-modal" data-title="Editar Usuario: {{ $user->name }}" style="text-decoration: none; color: black;" title="Editar">
                                    ✏️
                                </a>
                                @if(auth()->id() !== $user->id)
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" id="delete-form-{{ $user->id }}" class="d-inline" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger" title="Eliminar" style="color: black;" onclick="confirmFormSubmit('delete-form-{{ $user->id }}', '¿Eliminar usuario?', 'Esta acción no se puede deshacer.')">
                                            🗑️
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No se encontraron usuarios.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
