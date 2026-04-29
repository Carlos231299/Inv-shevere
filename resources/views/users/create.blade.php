@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <div class="card-header">
            <h3>Nuevo Usuario</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}" autocomplete="off">
                    @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required value="{{ old('email') }}" autocomplete="off">
                    @error('email')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Rol *</label>
                    <select name="role" class="form-control" required>
                        <option value="cashier" {{ old('role') == 'cashier' ? 'selected' : '' }}>Cajero</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrador</option>
                    </select>
                    @error('role')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña *</label>
                    <input type="password" name="password" class="form-control" required minlength="8" autocomplete="off">
                    @error('password')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirmar Contraseña *</label>
                    <input type="password" name="password_confirmation" class="form-control" required autocomplete="off">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
