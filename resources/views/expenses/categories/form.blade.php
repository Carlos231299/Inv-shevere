<form action="{{ isset($category) ? route('expense-categories.update', $category->id) : route('expense-categories.store') }}" method="POST">
    @csrf
    @if(isset($category))
        @method('PUT')
    @endif

    <div class="form-group" style="margin-bottom: 25px;">
        <label for="name" style="font-weight: 600; color: #555; margin-bottom: 8px; display: block;">Nombre de la Categoría *</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" placeholder="Ej: SERVICIOS, PERSONAL, ARRIENDO..." required style="border-radius: 12px; padding: 12px 15px; font-size: 1rem;" autocomplete="off">
    </div>

    <div style="text-align: right;">
        <button type="submit" class="btn btn-primary" style="padding: 12px 25px; border-radius: 12px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            {{ isset($category) ? 'Actualizar Categoría' : 'Guardar Categoría' }}
        </button>
    </div>
</form>
