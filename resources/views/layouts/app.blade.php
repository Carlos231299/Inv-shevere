<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Carnicería Salomé - Inventario</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom CSS & Scripts -->
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/css/print.css', 'resources/js/app.jsx'])

    <!-- PWA Manifest & SW -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#D32F2F">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW registered!', reg))
                    .catch(err => console.log('SW failed', err));
            });
        }
    </script>
    
    <style>
        /* Pagination Fixes */
        .custom-pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .custom-pagination nav {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .custom-pagination svg {
            width: 20px !important;
            height: 20px !important;
            max-width: 20px;
            max-height: 20px;
        }
        /* Hide the "Showing X to Y results" text if it appears and messes up layout, 
           or style it nicely. Default tailwind view is verbose. 
           Let's ensure flex wrapping is handled. */
        .custom-pagination div {
             display: flex;
             flex-wrap: wrap;
             justify-content: center;
             gap: 5px;
        }

        /* Global Button Styles */
        .btn, a.btn {
            text-decoration: none !important;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(0px);
            transition: background 0.3s ease, backdrop-filter 0.3s ease;
            pointer-events: none; /* Pass through clicks when hidden/transparent */
            visibility: hidden;
        }

        .modal-overlay.active {
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(2px);
            pointer-events: auto;
            visibility: visible;
        }

        .modal-container {
            background: white;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            overflow: hidden;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            
            /* Animation States */
            opacity: 0;
            transform: scale(0.85) translateY(30px);
            transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-overlay.active .modal-container {
            opacity: 1;
            transform: scale(1) translateY(0);
        }

        .modal-header {
            background: #8B0000;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 500;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.8rem;
            line-height: 1;
            cursor: pointer;
            padding: 0 5px;
            transition: transform 0.2s ease;
        }

        .close-modal:hover {
            transform: scale(1.1);
        }

        .modal-body {
            padding: 25px;
            overflow-y: auto;
            flex-grow: 1; /* Ensure it takes available space */
        }


        .modal-overlay.active .modal-container {
            opacity: 1;
            transform: scale(1) translateY(0);
        }

        /* Ensure SweetAlert is always on top of our custom modals (z-index 9999) */
        div.swal2-container {
            z-index: 20000 !important;
        }

        /* HIDE INPUT NUMBER SPINNERS */
        /* Chrome, Safari, Edge, Opera */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body>
    <div class="app-container" id="app">
        <div class="sidebar-overlay"></div>
        <!-- Sidebar -->
        <aside class="sidebar collapsed" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <img src="{{ asset('images/logo.png') }}" alt="Carnicería Salomé" class="logo-img">
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" title="Productos">
                    <span class="nav-icon">🥩</span>
                    <span class="nav-text">Productos</span>
                </a>
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('reports.products') }}" class="nav-link {{ request()->routeIs('reports.products') ? 'active' : '' }}" title="Reporte Productos">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">Ventas x Producto</span>
                </a>
                @endif
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('providers.index') }}" class="nav-link {{ request()->routeIs('providers.*') ? 'active' : '' }}" title="Proveedores">
                    <span class="nav-icon">🚛</span>
                    <span class="nav-text">Proveedores</span>
                </a>
                @endif
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" title="Compras">
                    <span class="nav-icon">🛒</span>
                    <span class="nav-text">Compras</span>
                </a>
                @endif
                <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" title="Ventas">
                    <span class="nav-icon">💰</span>
                    <span class="nav-text">Ventas</span>
                </a>
                <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" title="Gastos">
                    <span class="nav-icon">📉</span>
                    <span class="nav-text">Gastos</span>
                </a>
                <a href="{{ route('workers.index') }}" class="nav-link {{ request()->routeIs('workers.*') ? 'active' : '' }}" title="Trabajadores">
                    <span class="nav-icon">👷</span>
                    <span class="nav-text">Trabajadores</span>
                </a>
                <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" title="Clientes">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Clientes</span>
                </a>
                {{-- Cuentas Submenu --}}
                <div class="nav-item">
                    <a href="#" class="nav-link submenu-toggle {{ request()->routeIs('credits.*') || request()->routeIs('cuentas-por-pagar.*') ? 'active' : '' }}" title="Cuentas">
                        <span class="nav-icon">💳</span>
                        <span class="nav-text">Cuentas</span>
                        <span class="submenu-arrow" style="margin-left: auto;">▼</span>
                    </a>
                    <div class="submenu {{ request()->routeIs('credits.*') || request()->routeIs('cuentas-por-pagar.*') ? 'open' : '' }}" style="display: {{ request()->routeIs('credits.*') || request()->routeIs('cuentas-por-pagar.*') ? 'block' : 'none' }}; padding-left: 20px; background: rgba(0,0,0,0.1);">
                        <a href="{{ route('credits.index') }}" class="nav-link {{ request()->routeIs('credits.*') ? 'active' : '' }}" style="font-size: 0.9em;">
                            <span class="nav-text">Por Cobrar</span>
                        </a>
                        @if(auth()->user()->role === 'admin')
                        <a href="{{ route('cuentas-por-pagar.index') }}" class="nav-link {{ request()->routeIs('cuentas-por-pagar.*') ? 'active' : '' }}" style="font-size: 0.9em;">
                            <span class="nav-text">Por Pagar</span>
                        </a>
                        @endif
                    </div>
                </div>
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}" title="Reportes">
                    <span class="nav-icon">📄</span>
                    <span class="nav-text">Reportes</span>
                </a>
                @endif
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" title="Usuarios">
                    <span class="nav-icon">👤</span>
                    <span class="nav-text">Usuarios</span>
                </a>
                @endif
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('initial-setup.index') }}" class="nav-link {{ request()->routeIs('initial-setup.*') ? 'active' : '' }}" title="Modo Inicial">
                    <span class="nav-icon">🚀</span>
                    <span class="nav-text">Modo Inicial</span>
                </a>
                @endif
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" title="Configuración">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Configuración</span>
                </a>
                @endif
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content expanded" id="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <button id="sidebar-toggle" class="btn-toggle">
                    ☰
                </button>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <a href="{{ route('reports.manual') }}" title="Manual de Uso" style="text-decoration: none; color: #555; display: flex; align-items: center; transition: color 0.2s;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                        </svg>
                    </a>
                    <div class="user-menu" style="position: relative;">
                        <a href="#" id="user-menu-toggle" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <span>{{ Auth::user()->name }}</span>
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%;">
                            <span style="font-size: 0.8rem;">▼</span>
                        </a>
                    <div id="user-dropdown" style="display: none; position: absolute; right: 0; top: 100%; background: white; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); min-width: 180px; z-index: 1000; overflow: hidden; margin-top: 5px;">
                        <a href="{{ route('profile.show') }}" style="display: block; padding: 12px 15px; text-decoration: none; color: #333; border-bottom: 1px solid #f0f0f0; transition: background 0.2s;">
                            <span style="margin-right: 8px;">👤</span> Mi Perfil
                        </a>
                        <a href="{{ route('settings.index') }}" style="display: block; padding: 12px 15px; text-decoration: none; color: #333; border-bottom: 1px solid #f0f0f0; transition: background 0.2s;">
                            <span style="margin-right: 8px;">⚙️</span> Configuración
                        </a>
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); confirmLogout();" style="display: block; padding: 12px 15px; text-decoration: none; color: #d33; transition: background 0.2s;">
                            <span style="margin-right: 8px;">🚪</span> Cerrar Sesión
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content-wrapper">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function confirmFormSubmit(formId, title = '¿Estás seguro?', text = 'No podrás revertir esto', icon = 'warning', confirmButtonText = 'Sí, eliminar') {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            })
        }

        function confirmLogout() {
            Swal.fire({
                title: '¿Cerrar Sesión?',
                text: "Estás a punto de salir del sistema.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            });
        }

        // Global function for generating SKU (Product Form)
        function generateSku() {
            const skuInput = document.getElementById('sku');
            if(!skuInput) return;

            // Generate EAN-13 (12 digits + 1 checksum)
            // Prefix 200 is often used for in-store usage (Instore EAN)
            let ean = "200"; 
            for (let i = 0; i < 9; i++) {
                ean += Math.floor(Math.random() * 10);
            }

            // Calculate Checksum
            let sum = 0;
            for (let i = 0; i < 12; i++) {
                sum += parseInt(ean[i]) * (i % 2 === 0 ? 1 : 3);
            }
            const checksum = (10 - (sum % 10)) % 10;
            ean += checksum;
            
            skuInput.value = ean;
        }

        // Modal Handling Logic
        function closeCrudModal() {
            const overlay = document.getElementById('crud-modal-overlay');
            overlay.classList.remove('active');
            
            // Wait for transition to finish before cleaning content
            setTimeout(() => {
                document.getElementById('crud-modal-body').innerHTML = '<div style="text-align: center; padding: 20px;">Cargando...</div>';
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Open Modal Listener
            document.body.addEventListener('click', function(e) {
                // Close modal if clicking outside (on overlay)
                if (e.target.id === 'crud-modal-overlay') {
                    closeCrudModal();
                    return;
                }

                // Traverse up to find the anchor if clicked on icon
                const link = e.target.closest('.open-modal');
                if (link) {
                    e.preventDefault();
                    const url = link.getAttribute('href');
                    const title = link.getAttribute('data-title') || 'Formulario';
                    
                    // Show modal
                    const overlay = document.getElementById('crud-modal-overlay');
                    overlay.classList.add('active'); // Triggers CSS transition
                    document.getElementById('modal-title').innerText = title;
                    
                    // Fetch content
                    fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const modalBody = document.getElementById('crud-modal-body');
                        modalBody.innerHTML = html;
                        
                        // EXECUTE SCRIPTS in the response
                        modalBody.querySelectorAll('script').forEach(oldScript => {
                            const newScript = document.createElement('script');
                            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                            oldScript.parentNode.replaceChild(newScript, oldScript);
                        });
                        
                        // Hijack Form Submission
                        const newForm = modalBody.querySelector('form');
                        if(newForm) {
                             // Correctly handle the submit button to avoid double clicks or confusion
                             // newForm.querySelector('button[type="submit"]').textContent = 'Guardando...';

                            newForm.addEventListener('submit', function(ev) {
                                ev.preventDefault();
                                const formData = new FormData(newForm);
                                
                                // Clear previous errors
                                document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
                                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                                
                                fetch(newForm.action, {
                                    method: newForm.method,
                                    body: formData,
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json'
                                    }
                                })
                                .then(res => res.json().then(data => ({status: res.status, body: data})))
                                .then(result => {
                                    if (result.status === 200 || result.status === 201) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '¡Éxito!',
                                            text: result.body.message || 'Operación realizada correctamente',
                                            timer: 1500,
                                            showConfirmButton: false
                                        }).then(() => {
                                            closeCrudModal();
                                            window.location.reload(); 
                                        });
                                    } else if (result.status === 422) {
                                        // Validation Errors
                                        const errors = result.body.errors;
                                        for (const [field, messages] of Object.entries(errors)) {
                                            const input = newForm.querySelector(`[name="${field}"]`);
                                            if(input) {
                                                input.classList.add('is-invalid');
                                                const errorDiv = document.createElement('div');
                                                errorDiv.className = 'invalid-feedback';
                                                errorDiv.style.color = 'red';
                                                errorDiv.style.fontSize = '0.85em';
                                                errorDiv.innerText = messages[0];
                                                input.parentNode.appendChild(errorDiv);
                                            }
                                        }
                                    } else {
                                        Swal.fire('Error', 'Ocurrió un error inesperado', 'error');
                                    }
                                })
                                .catch(err => {
                                    console.error(err);
                                    Swal.fire('Error', 'Error de conexión', 'error');
                                });
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        document.getElementById('crud-modal-body').innerHTML = '<p style="color:red; text-align:center;">Error al cargar el formulario.</p>';
                    });
                }
            });
        });

        // Sidebar toggle logic with Mobile support
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const overlay = document.querySelector('.sidebar-overlay');
            
            // Function to handle toggle
            function toggleSidebar() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('mobile-open');
                    overlay.classList.toggle('active');
                } else {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                }
            }

            // Click Event
            toggleBtn.addEventListener('click', toggleSidebar);

            // Overlay click (close sidebar on mobile)
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                });
            }

            // Hover Logic (Desktop only)
            sidebar.addEventListener('mouseenter', function() {
                if (window.innerWidth > 768 && sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded'); // Optional: push content depends on preference
                }
            });

            sidebar.addEventListener('mouseleave', function() {
                if (window.innerWidth > 768 && !sidebar.classList.contains('collapsed')) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                }
            });

            // Submenu Logic
            const submenuToggles = document.querySelectorAll('.submenu-toggle');
            submenuToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Auto-expand sidebar if collapsed (Desktop)
                    if (window.innerWidth > 768 && sidebar.classList.contains('collapsed')) {
                         sidebar.classList.remove('collapsed');
                         mainContent.classList.remove('expanded');
                    }

                    const submenu = this.nextElementSibling;
                    if (submenu) {
                        if (submenu.style.display === 'block') {
                            submenu.style.display = 'none';
                            submenu.classList.remove('open');
                        } else {
                            submenu.style.display = 'block';
                            submenu.classList.add('open');
                        }
                    }
                });
            });

            // User Dropdown Logic
            const userToggle = document.getElementById('user-menu-toggle');
            const userDropdown = document.getElementById('user-dropdown');
            
            if (userToggle && userDropdown) {
                userToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (userDropdown.style.display === 'block') {
                        userDropdown.style.display = 'none';
                    } else {
                        userDropdown.style.display = 'block';
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!userToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.style.display = 'none';
                    }
                });
            }
        });
    </script>
    
    <style>
        /* Sidebar Hover Transitions */
        .sidebar {
            transition: all 0.3s ease;
        }
        .sidebar.collapsed {
            width: 80px; /* Adjust based on icon width */
        }
        .sidebar.collapsed .nav-text, 
        .sidebar.collapsed .sidebar-brand,
        .sidebar.collapsed .submenu-arrow {
            display: none;
        }
        .sidebar.collapsed .nav-link {
            justify-content: center;
        }
        .sidebar.collapsed .submenu {
            display: none !important;
        }
        
        /* Ensure overlay on main content if preferred, or push content */
        /* If we want to save space, sidebar might need to be absolute or existing CSS handles flex */
    </style>
    @stack('scripts')
    
    <!-- Generic CRUD Modal -->
    <div id="crud-modal-overlay" class="modal-overlay"> <!-- Removed style="display:none" to rely on class and CSS visibility -->
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modal-title">Título</h3>
                <button type="button" class="close-modal" onclick="closeCrudModal()">×</button>
            </div>
            <div class="modal-body" id="crud-modal-body">
                <div style="text-align: center; padding: 20px;">Cargando...</div>
            </div>
        </div>
    </div>
    <script>
        // GLOBAL FIX: Disable mouse wheel scroll on number inputs to prevent accidental value changes
        document.addEventListener('wheel', function(e) {
            if (document.activeElement.type === 'number') {
                document.activeElement.blur();
            }
        });
    </script>
    
    @yield('modals')
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @yield('scripts')

    @if($needsDigitalSettings ?? false)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: '🐮 ¡Apertura de Día Requerida!',
                html: `
                    <p class="text-muted small mb-3">Ingresa las Bases para iniciar a registrar los movimientos de hoy.</p>
                    <div style="text-align: left;">
                        <div class="mb-3">
                            <label class="form-label fw-bold">💵 Saldo Efectivo (Caja):</label>
                            <input type="number" id="swal-cash" class="form-control" value="{{ $globalBaseCash ?? 0 }}" step="1" required autoFocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">📱 Saldo Nequi:</label>
                            <input type="number" id="swal-nequi" class="form-control" value="{{ $globalBaseNequi ?? 0 }}" step="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">🏦 Saldo Bancolombia:</label>
                            <input type="number" id="swal-bancolombia" class="form-control" value="{{ $globalBaseBancolombia ?? 0 }}" step="1" required>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: '✅ Confirmar Apertura de Hoy',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showCancelButton: false,
                confirmButtonColor: '#1976d2',
                preConfirm: () => {
                    const cash = document.getElementById('swal-cash').value;
                    const nequi = document.getElementById('swal-nequi').value;
                    const bancolombia = document.getElementById('swal-bancolombia').value;
                    
                    if (!cash || !nequi || !bancolombia) {
                        Swal.showValidationMessage('Debes ingresar los tres saldos');
                        return false;
                    }

                    // Return a promise that shows another confirmation
                    return Swal.fire({
                        title: '¿Estás seguro?',
                        text: `El sistema guardará estas bases para el turno de hoy. De esto dependerá el cierre de caja.`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, iniciar turno',
                        cancelButtonText: 'Volver a revisar',
                        confirmButtonColor: '#1976d2',
                        cancelButtonColor: '#d33',
                    }).then((confirmResult) => {
                        if (confirmResult.isConfirmed) {
                            return fetch("{{ route('settings.update') }}", {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    initial_cash_balance: cash,
                                    initial_nequi_balance: nequi,
                                    initial_bancolombia_balance: bancolombia
                                })
                            })
                            .then(response => {
                                if (!response.ok) throw new Error(response.statusText);
                                return response.json();
                            })
                            .catch(error => {
                                Swal.showValidationMessage(`Error: ${error}`);
                            });
                        } else {
                            // If user cancels the second alert, we return false to stay in the first one
                            // But wait, the first one closed when the second one opened.
                            // To keep it open, we would need to reload it. 
                            // Actually, let's just trigger the whole flow again if they cancel.
                            location.reload(); 
                            return false;
                        }
                    });
                }
            }).then((result) => {
                if (result && result.isConfirmed && result.value && result.value.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Día abierto correctamente.',
                        icon: 'success',
                        timer: 1000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        });
    </script>
    @endif
</body>
</html>
