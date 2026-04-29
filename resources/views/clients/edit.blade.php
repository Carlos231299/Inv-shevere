@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h2>✏️ Editar Cliente</h2>
    </div>
    <div class="card-body">
        <form action="{{ route('clients.update', $client->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group mb-3">
                <label>Nombre Completo *</label>
                <input type="text" name="name" class="form-control" value="{{ $client->name }}" required autocomplete="off">
            </div>
            
            <div class="form-group mb-3">
                <label>Documento / CC</label>
                <input type="text" name="document" class="form-control" value="{{ $client->document }}" autocomplete="off">
            </div>
            
            <div class="form-group mb-3">
                <label>Teléfono</label>
                <input type="text" name="phone" class="form-control" value="{{ $client->phone }}" autocomplete="off">
            </div>
            
            <div class="form-group mb-3">
                <label>Dirección</label>
                <input type="text" name="address" class="form-control" value="{{ $client->address }}" autocomplete="off">
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="{{ route('clients.index') }}" class="btn btn-secondary" style="text-decoration: none; color: black;">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Cliente</button>
            </div>
        </form>
    </div>
</div>
@endsection
