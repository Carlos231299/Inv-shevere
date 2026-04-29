<form action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}" method="POST">
    @csrf
    @if(isset($user))
        @method('PUT')
    @endif
    
    <div class="mb-3">
        <label class="form-label">Nombre *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $user->name ?? '') }}" autocomplete="off">
        @error('name')<span class="text-danger">{{ $message }}</span>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" required value="{{ old('email', $user->email ?? '') }}" autocomplete="off">
        @error('email')<span class="text-danger">{{ $message }}</span>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Rol *</label>
        <select name="role" class="form-control" required>
            <option value="cashier" {{ old('role', $user->role ?? '') == 'cashier' ? 'selected' : '' }}>Cajero</option>
            <option value="admin" {{ old('role', $user->role ?? '') == 'admin' ? 'selected' : '' }}>Administrador</option>
        </select>
        @error('role')<span class="text-danger">{{ $message }}</span>@enderror
    </div>

    <hr>
    <div class="mb-3">
        <label class="form-label">{{ isset($user) ? 'Nueva Contraseña (Opcional)' : 'Contraseña *' }}</label>
        <input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }} minlength="8" placeholder="{{ isset($user) ? 'Dejar en blanco para mantener la actual' : '' }}" autocomplete="off">
        @error('password')<span class="text-danger">{{ $message }}</span>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Confirmar {{ isset($user) ? 'Nueva ' : '' }}Contraseña *</label>
        <input type="password" name="password_confirmation" class="form-control" {{ isset($user) ? '' : 'required' }} autocomplete="off">
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-primary">{{ isset($user) ? 'Actualizar Usuario' : 'Guardar Usuario' }}</button>
    </div>
</form>
