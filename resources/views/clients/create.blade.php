@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h2>Nuevo Cliente</h2>
    </div>
    <div class="card-body">
        <form action="{{ route('clients.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Nombre Completo *</label>
                <input type="text" name="name" class="form-control" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Documento / CC</label>
                <input type="text" name="document" class="form-control" autocomplete="off">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="phone" class="form-control" autocomplete="off">
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="address" class="form-control" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar Cliente</button>
        </form>
    </div>
</div>
@endsection
