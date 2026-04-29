@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Gestión de Productos</h2>
        @if(auth()->user()->role === 'admin')
        <a href="{{ route('products.create') }}" class="btn btn-primary open-modal" data-title="Nuevo Producto">
            + Nuevo Producto
        </a>
        @endif
    </div>

    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '{{ session('error') }}'
            });
        </script>
    @endif

    <div class="card-body">
        <div class="row mb-3" style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">
            <div style="flex: 1;">
                <input type="text" id="search-input" class="form-control" placeholder="Buscar por nombre o código..." value="{{ request('search') }}" autocomplete="off">
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="white-space: nowrap;">Mostrar</span>
                <select id="per-page-select" class="form-control" style="width: auto; display: inline-block;">
                    <option value="5" {{ request('per_page') == 5 ? 'selected' : '' }}>5</option>
                    <option value="10" {{ request('per_page') == 10 || !request('per_page') ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span style="white-space: nowrap;">productos</span>
            </div>
        </div>

        <div id="loading-spinner" style="text-align: center; display: none; margin: 20px;">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Medida</th>
                    <th>Compra</th>
                    <th>Prc Prom de compra</th>
                    <th>Venta</th>
                    <th>Stock Actual</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="products-table-body">
                @include('products.partials.table_rows')
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const perPageSelect = document.getElementById('per-page-select');
        const tableBody = document.getElementById('products-table-body');
        const loadingSpinner = document.getElementById('loading-spinner');
        let debounceTimer;

        function fetchProducts(url = null) {
            const search = searchInput.value;
            const perPage = perPageSelect.value;
            const currentUrl = url || "{{ route('products.index') }}";

            // Show loading
            // loadingSpinner.style.display = 'block';
            // tableBody.style.opacity = '0.5';

            const separator = currentUrl.includes('?') ? '&' : '?';
            const fetchUrl = `${currentUrl}${separator}search=${encodeURIComponent(search)}&per_page=${perPage}`;

            fetch(fetchUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                tableBody.innerHTML = html;
                // Re-bind pagination links to work via AJAX
                bindPaginationLinks();
            })
            .catch(error => console.error('Error:', error))
            .finally(() => {
                // loadingSpinner.style.display = 'none';
                // tableBody.style.opacity = '1';
            });
        }

        function bindPaginationLinks() {
            const links = tableBody.querySelectorAll('.pagination a');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetchProducts(this.href);
                });
            });
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchProducts();
            }, 300); // Debounce for 300ms
        });

        perPageSelect.addEventListener('change', function() {
            fetchProducts();
        });

        // Initial binding
        bindPaginationLinks();
        bindAdjustButtons(); // Bind initial buttons

        function bindAdjustButtons() {
            document.querySelectorAll('.adjust-stock-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const sku = this.dataset.sku;
                    const name = this.dataset.name;
                    const currentStock = this.dataset.stock;

                    // 1. Initial Warning
                    const result = await Swal.fire({
                        title: '¿Realizar Ajuste de Inventario?',
                        text: "Esta acción modificará directamente el stock del producto. Úselo con precaución.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, continuar',
                        cancelButtonText: 'Cancelar'
                    });

                    if (!result.isConfirmed) return;

                    // 2. Input Modal
                    const { value: formValues } = await Swal.fire({
                        title: `Ajuste de Stock: ${name}`,
                        html: `
                            <div style="text-align: left; margin-bottom: 10px;">
                                <label>Stock en Sistema:</label>
                                <input class="swal2-input" value="${currentStock}" readonly disabled style="background: #f0f0f0;">
                                <label style="margin-top: 10px; display: block; font-weight: bold;">Stock Físico (Real):</label>
                                <input id="swal-real-stock" type="number" step="0.001" class="swal2-input" placeholder="Ingrese cantidad real">
                                <label style="margin-top: 10px; display: block;">Nota (Opcional):</label>
                                <input id="swal-notes" class="swal2-input" placeholder="Razón del ajuste...">
                            </div>
                        `,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Siguiente',
                        cancelButtonText: 'Cancelar',
                        preConfirm: () => {
                            const realStock = document.getElementById('swal-real-stock').value;
                            const notes = document.getElementById('swal-notes').value;
                            if (!realStock) {
                                Swal.showValidationMessage('Debe ingresar el stock real');
                            }
                            return { sku: sku, real_stock: realStock, notes: notes };
                        }
                    });

                    if (formValues) {
                        // 3. Final Confirmation
                        const confirmFinal = await Swal.fire({
                            title: '¿Confirmar Ajuste?',
                            html: `El stock de <b>${name}</b> pasará de <b>${currentStock}</b> a <b style="color:#d33; font-size:1.2em">${formValues.real_stock}</b>.<br><br>¿Está seguro de aplicar este cambio?`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, Cambiar Stock',
                            cancelButtonText: 'Cancelar'
                        });

                        if (!confirmFinal.isConfirmed) return;

                        // Send AJAX request
                        fetch('{{ route("inventory.adjust") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(formValues)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.message) {
                                Swal.fire('Ajustado', data.message, 'success');
                                fetchProducts(); // Reload table
                            } else {
                                Swal.fire('Error', 'Hubo un problema al ajustar', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Error', 'Error de conexión', 'error');
                        });
                    }
                });
            });
        }

        // Override fetchProducts to re-bind buttons after AJAX reload
        const originalFetchProducts = fetchProducts;
        fetchProducts = function(url) {
            const currentUrl = url || "{{ route('products.index') }}";
             const search = searchInput.value;
            const perPage = perPageSelect.value;
            const separator = currentUrl.includes('?') ? '&' : '?';
            const fetchUrl = `${currentUrl}${separator}search=${encodeURIComponent(search)}&per_page=${perPage}`;

            fetch(fetchUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                tableBody.innerHTML = html;
                bindPaginationLinks();
                bindAdjustButtons(); // Re-bind new elements
            });
        };
    });
</script>
@endsection
