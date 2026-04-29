<form action="{{ isset($provider) ? route('providers.update', $provider) : route('providers.store') }}" method="POST">
    @csrf
    @if(isset($provider))
        @method('PUT')
    @endif
    
    <div class="mb-3">
        <label class="form-label">Nombre Empresa / Proveedor *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $provider->name ?? '') }}" autocomplete="off">
        @error('name')<span class="text-danger">{{ $message }}</span>@enderror
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">NIT / Identificación</label>
            <input type="text" name="nit" class="form-control" value="{{ old('nit', $provider->nit ?? '') }}" autocomplete="off">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Nombre Contacto</label>
            <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name', $provider->contact_name ?? '') }}" autocomplete="off">
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $provider->phone ?? '') }}" autocomplete="off">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $provider->email ?? '') }}" autocomplete="off">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Dirección</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $provider->address ?? '') }}" autocomplete="off">
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-primary">{{ isset($provider) ? 'Actualizar Proveedor' : 'Guardar Proveedor' }}</button>
    </div>
</form>
