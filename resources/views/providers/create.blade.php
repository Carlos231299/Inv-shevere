@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <div class="card-header">
            <h3>Nuevo Proveedor</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('providers.store') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label">Nombre Empresa / Proveedor *</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}" autocomplete="off">
                    @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIT / Identificación</label>
                        <input type="text" name="nit" class="form-control" value="{{ old('nit') }}" autocomplete="off">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre Contacto</label>
                        <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name') }}" autocomplete="off">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" autocomplete="off">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" autocomplete="off">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address') }}" autocomplete="off">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('providers.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
