<form action="{{ isset($client) ? route('clients.update', $client->id) : route('clients.store') }}" method="POST">
    @csrf
    @if(isset($client))
        @method('PUT')
    @endif
    
    <div class="form-group mb-3">
        <label>Nombre Completo *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $client->name ?? '') }}" required autocomplete="off">
    </div>
    
    <div class="form-group mb-3">
        <label>Documento / CC</label>
        <input type="text" name="document" class="form-control" value="{{ old('document', $client->document ?? '') }}" autocomplete="off">
    </div>
    
    <div class="form-group mb-3">
        <label>Teléfono</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $client->phone ?? '') }}" autocomplete="off">
    </div>
    
    <div class="form-group mb-3">
        <label>Dirección</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $client->address ?? '') }}" autocomplete="off">
    </div>
    
    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">{{ isset($client) ? 'Actualizar Cliente' : 'Guardar Cliente' }}</button>
    </div>
</form>
