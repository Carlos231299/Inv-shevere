import React, { useState, useEffect, useRef } from 'react';
import Swal from 'sweetalert2';

export default function Purchase() {
    const [products, setProducts] = useState([]);
    const [search, setSearch] = useState('');
    const [filteredProducts, setFilteredProducts] = useState([]);
    const [showDropdown, setShowDropdown] = useState(false);

    const [cart, setCart] = useState([]);
    const [loading, setLoading] = useState(false);
    const [paymentMethod, setPaymentMethod] = useState('cash'); // cash, credit, transfer

    // Payment Status State
    const [paymentStatus, setPaymentStatus] = useState('paid'); // paid, credit, partial
    const [depositAmount, setDepositAmount] = useState('');
    const [customDate, setCustomDate] = useState(new Date().toISOString().split('T')[0]); // Added customDate
    const [editMode, setEditMode] = useState(false);
    const [editId, setEditId] = useState(null);

    // Inline Add State
    const [quantityToAdd, setQuantityToAdd] = useState(1);
    const [costPriceToAdd, setCostPriceToAdd] = useState('');
    const [salePriceToAdd, setSalePriceToAdd] = useState('');
    const [batchNumberToAdd, setBatchNumberToAdd] = useState('');
    const [selectedProduct, setSelectedProduct] = useState(null);

    // Provider State
    const [providers, setProviders] = useState([]);
    const [selectedProvider, setSelectedProvider] = useState('');

    const inputRef = useRef(null);

    // CSRF Token Helper
    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Fetch all products and providers on mount
    useEffect(() => {
        fetchProducts();
        fetchProviders();

        const params = new URLSearchParams(window.location.search);
        const editIdStr = params.get('edit');
        if (editIdStr) {
            setEditMode(true);
            setEditId(editIdStr);
            fetchPurchaseForEdit(editIdStr);
        }
    }, []);

    const fetchPurchaseForEdit = async (id) => {
        try {
            const res = await fetch(`/api/purchases/${id}`, {
                headers: { 'Accept': 'application/json' }
            });
            const purchase = await res.json();

            // Populate Cart
            const newCart = purchase.movements.map(m => ({
                id: m.id,
                sku: m.product_sku,
                name: m.product.name,
                quantity: parseFloat(m.quantity) || 0,
                cost_price: parseFloat(m.price_at_moment) || 0,
                sale_price: parseFloat(m.product.sale_price) || 0,
                subtotal: parseFloat(m.total) || 0
            }));

            setCart(newCart);
            setCustomDate(purchase.created_at.split('T')[0]);
            setSelectedProvider(purchase.provider_id || '');

            // Payment Logic
            if (purchase.account_payable) {
                // If there's an AP record, status is credit or partial
                // Check if it has payments (initial deposit)
                const totalDebt = parseFloat(purchase.account_payable.amount) || 0;
                const paidAmt = parseFloat(purchase.account_payable.paid_amount) || 0;

                if (paidAmt > 0) {
                    setPaymentStatus('partial');
                    setDepositAmount(paidAmt);
                    // Try to get payment method from the first payment
                    if (purchase.account_payable.payments && purchase.account_payable.payments.length > 0) {
                        setPaymentMethod(purchase.account_payable.payments[0].payment_method);
                    } else {
                        setPaymentMethod('cash'); // fallback
                    }
                } else {
                    setPaymentStatus('credit');
                    setDepositAmount('');
                }
            } else {
                setPaymentStatus('paid');
                // Try to infer payment method from first movement
                if (purchase.movements.length > 0) {
                    const mMethod = purchase.movements[0].payment_method;
                    if (mMethod === 'credit') {
                        setPaymentMethod('cash');
                    } else if (['bank', 'transfer', 'bancolombia'].includes(mMethod)) {
                        setPaymentMethod('bancolombia');
                    } else {
                        setPaymentMethod(mMethod);
                    }
                }
            }

            Swal.fire({
                title: 'Modo Edición',
                text: `Editando Compra #${String(id).padStart(6, '0')}`,
                icon: 'info',
                timer: 1500,
                showConfirmButton: false
            });
        } catch (error) {
            console.error('Error fetching purchase for edit:', error);
            Swal.fire('Error', 'No se pudo cargar la compra para editar.', 'error');
        }
    };

    const fetchProviders = async () => {
        try {
            const res = await fetch('/api/providers', {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            const data = await res.json();
            setProviders(data);
        } catch (error) {
            console.error('Error fetching providers:', error);
        }
    };

    const fetchProducts = async () => {
        try {
            const res = await fetch(`/api/products-list?t=${Date.now()}`, {
                headers: { 
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            const data = await res.json();
            setProducts(data);
        } catch (error) {
            console.error('Error fetching products:', error);
        }
    };

    // Filter products when typing
    useEffect(() => {
        if (search.trim() === '') {
            setFilteredProducts([]);
            return;
        }

        const lowerSearch = search.toLowerCase();
        const filtered = products.filter(p =>
            p.name.toLowerCase().includes(lowerSearch) ||
            p.sku.toLowerCase().includes(lowerSearch)
        );
        setFilteredProducts(filtered);
    }, [search, products]);

    // --- INLINE ADD TO CART LOGIC ---
    // --- INLINE ADD TO CART LOGIC ---
    const handleAddItem = () => {
        // 1. Enforce Provider Selection First
        if (!selectedProvider) {
            Swal.fire('Seleccione un Proveedor', 'Debe seleccionar un proveedor (o "Sin Proveedor") antes de agregar productos.', 'warning');
            return;
        }

        if (!selectedProduct) {
            Swal.fire('Seleccione un producto', 'Busque y seleccione un producto de la lista.', 'info');
            return;
        }

        const qty = parseFloat(quantityToAdd);

        // Parse Currency String to Float for Calculation
        // Remove dots (thousands) and replace comma with dot (decimal)
        const parseCurrency = (str) => {
            if (!str) return 0;
            if (typeof str === 'number') return str;
            return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
        };

        const cost = parseCurrency(costPriceToAdd);
        const sale = parseCurrency(salePriceToAdd);

        if (isNaN(qty) || qty <= 0) {
            Swal.fire('Cantidad inválida', 'Ingrese cantidad mayor a 0', 'warning');
            return;
        }
        if (isNaN(cost) || cost < 0) {
            Swal.fire('Costo inválido', 'Ingrese costo válido', 'warning');
            return;
        }

        // Get Provider Name for display
        let providerName = 'Sin Proveedor';
        if (selectedProvider !== 'no_provider') {
            const prov = providers.find(p => p.id == selectedProvider);
            if (prov) providerName = prov.name;
        }

        // Just use raw qty for purchase
        const newItem = {
            ...selectedProduct,
            quantity: qty,
            cost_price: costPriceToAdd, // Send as string/formatted to backend
            sale_price: salePriceToAdd || selectedProduct.sale_price, // Send as string
            batch_number: batchNumberToAdd.trim(),
            subtotal: qty * cost, // Local calc with float
            provider: providerName // Add provider name for the table
        };

        setCart(prev => [...prev, newItem]);

        // Reset inputs
        setSearch('');
        setSelectedProduct(null);
        setQuantityToAdd(1);
        setCostPriceToAdd('');
        setSalePriceToAdd('');
        setBatchNumberToAdd('');
        inputRef.current?.focus();
    };

    const handleSelectProduct = (product) => {
        setSearch(product.name);
        setSelectedProduct(product);
        setShowDropdown(false);
        setQuantityToAdd(1);
        setCostPriceToAdd(product.cost_price || '');
        setSalePriceToAdd(product.sale_price || '');
        setBatchNumberToAdd('');
        // Focus quantity next
        setTimeout(() => document.getElementById('input-qty')?.focus(), 100);
    };

    // --- DEPRECATED MODAL LOGIC REMOVED ---

    // Still used for creation prompt
    const promptCreateProduct = (term) => {
        Swal.fire({
            title: 'Producto no encontrado',
            text: `"${term}" no existe. ¿Crearlo ahora?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, crear',
            confirmButtonColor: '#D32F2F'
        }).then((result) => {
            if (result.isConfirmed) {
                openCreateModal(term);
            } else {
                inputRef.current?.focus();
            }
        });
    };

    const openCreateModal = (suggestedName) => {
        // Simple random default SKU or ask for it
        // Asking for SKU to keep it clean
        Swal.fire({
            title: 'Nuevo Producto Rápido',
            html: `
                <input id="swal-sku" class="swal2-input" placeholder="Código de Barras (SKU)" value="${Date.now().toString().slice(-6)}" autocomplete="off">
                <input id="swal-name" class="swal2-input" placeholder="Nombre" value="${suggestedName}" autocomplete="off">
                <select id="swal-type" class="swal2-input">
                    <option value="kg">Kilogramo</option>
                    <option value="unit">Unidad</option>
                </select>
                <input id="swal-cost" type="number" step="0.01" class="swal2-input" placeholder="Precio Compra" autocomplete="off">
                <input id="swal-sale" type="number" step="0.01" class="swal2-input" placeholder="Precio Venta" autocomplete="off">
            `,
            focusConfirm: false,
            preConfirm: () => {
                return {
                    sku: document.getElementById('swal-sku').value,
                    name: document.getElementById('swal-name').value,
                    measure_type: document.getElementById('swal-type').value,
                    cost_price: document.getElementById('swal-cost').value,
                    sale_price: document.getElementById('swal-sale').value,
                    min_stock: 1
                }
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                await createProduct(result.value);
            }
        });
    };

    // --- PROVIDER CREATION ---
    const promptCreateProvider = async () => {
        const { value: formValues } = await Swal.fire({
            title: '<span style="color:#8B0000">Nuevo Proveedor</span>',
            html: `
                <input id="swal-prov-name" class="swal2-input" placeholder="Nombre Empresa / Proveedor" autocomplete="off">
                <input id="swal-prov-nit" class="swal2-input" placeholder="NIT / Cédula (Opcional)" autocomplete="off">
                <input id="swal-prov-phone" class="swal2-input" placeholder="Teléfono (Opcional)" autocomplete="off">
            `,
            confirmButtonText: 'Guardar Proveedor',
            confirmButtonColor: '#8B0000',
            showCancelButton: true,
            focusConfirm: false,
            preConfirm: () => {
                const name = document.getElementById('swal-prov-name').value;
                if (!name) Swal.showValidationMessage('El nombre es obligatorio');
                return {
                    name: name,
                    nit: document.getElementById('swal-prov-nit').value,
                    phone: document.getElementById('swal-prov-phone').value
                }
            }
        });

        if (formValues) {
            try {
                const res = await fetch('/api/providers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify(formValues)
                });
                if (res.ok) {
                    const newProv = await res.json();
                    setProviders(prev => [...prev, newProv].sort((a, b) => a.name.localeCompare(b.name)));
                    setSelectedProvider(newProv.id); // Auto-select
                    Swal.fire({ icon: 'success', title: 'Proveedor Creado', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Error', 'No se pudo crear.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Fallo de conexión', 'error');
            }
        }
    };

    const createProduct = async (productData) => {
        try {
            const response = await fetch('/api/products', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify(productData)
            });

            if (response.ok) {
                const newProduct = await response.json();
                Swal.fire({
                    icon: 'success',
                    title: 'Producto Creado',
                    text: 'Se ha añadido al inventario.',
                    confirmButtonColor: '#8B0000'
                });
                // Refresh list and select the new product to add quantity
                await fetchProducts();
                // Find and select the new product
                handleSelectProduct(newProduct);
            } else {
                const data = await response.json();
                Swal.fire('Error', data.message || 'Error al guardar.', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Fallo de conexión.', 'error');
        } finally {
            setLoading(false);
        }
    };

    // Confirmation Modal State
    const [showConfirmation, setShowConfirmation] = useState(false);

    // Handle Confirm Purchase (Opens Modal)
    const handleFinishPurchase = () => {
        if (cart.length === 0) return;

        // Custom validation for payment method if not credit
        if (paymentStatus !== 'credit' && !paymentMethod) {
            Swal.fire('Error', 'Selecciona un método de pago', 'warning');
            return;
        }

        setShowConfirmation(true);
    };

    const executePurchase = async () => {
        setLoading(true);
        try {
            // Determine actual method to send
            const finalMethod = (paymentStatus === 'credit' || paymentStatus === 'partial') && (paymentMethod === 'cash' || !paymentMethod) ? 'credit' : paymentMethod;

            const url = editMode ? `/api/purchases/${editId}` : '/api/purchases';
            const method = editMode ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    items: cart,
                    payment_method: finalMethod,
                    payment_status: paymentStatus,
                    deposit_amount: depositAmount || 0,
                    provider_id: selectedProvider === 'no_provider' ? null : selectedProvider,
                    custom_date: customDate // Send custom date
                })
            });
            if (response.ok) {
                Swal.fire({
                    icon: 'success',
                    title: editMode ? 'Compra Actualizada' : 'Compra Registrada',
                    showConfirmButton: false,
                    timer: 1500
                });
                setCart([]);
                setShowConfirmation(false);
                await fetchProducts(); // Refresh stock/prices in local list
                if (editMode) {
                    // Optionally redirect after update
                    window.location.href = '/purchases'; // Or to a specific purchase detail page
                }
            } else {
                const data = await response.json();
                Swal.fire('Error', data.message || 'Error al guardar.', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Fallo de conexión.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const removeItem = (index) => {
        setCart(prev => prev.filter((_, i) => i !== index));
    };

    const formatMoney = (amount) => {
        return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);
    };

    const totalTotal = cart.reduce((sum, item) => sum + (parseFloat(item.subtotal) || 0), 0);
    const totalPrice = totalTotal;

    // Helper to get selected provider name
    const getProviderName = () => {
        if (selectedProvider === 'no_provider') return 'Sin Proveedor (General)';
        if (!selectedProvider) return 'No Seleccionado';
        const p = providers.find(prov => prov.id == selectedProvider);
        return p ? p.name : 'Desconocido';
    };

    return (
        <div className="container-fluid p-4 desktop-grid" style={{ minHeight: '100vh', display: 'grid', gridTemplateColumns: 'minmax(320px, 1fr) 420px', gap: '25px', alignItems: 'start' }}>

            {/* EDIT MODE BANNER */}
            {editMode && (
                <div style={{
                    position: 'fixed', top: 0, left: 0, right: 0, zIndex: 9999,
                    background: '#ff9800', color: '#fff', textAlign: 'center',
                    padding: '10px', fontWeight: 'bold', fontSize: '1.2rem',
                    boxShadow: '0 2px 10px rgba(0,0,0,0.2)'
                }}>
                    ⚠️ MODO EDICIÓN ACTIVO: Estás modificando la Compra #{String(editId).padStart(6, '0')}
                    <button onClick={() => window.location.href = '/purchases'} style={{ marginLeft: '20px', border: '1px solid white', background: 'transparent', color: 'white', borderRadius: '5px', padding: '2px 10px', fontSize: '0.9rem', cursor: 'pointer' }}>
                        Cancelar Edición
                    </button>
                </div>
            )}

            {/* --- LEFT PANEL: PRODUCTS LIST --- */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: '20px', height: '100%' }}>

                {/* Search & Inline Add Controls */}
                <div style={{ background: 'white', padding: '20px', borderRadius: '15px', boxShadow: '0 2px 10px rgba(0,0,0,0.05)' }}>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>

                        {/* Search Bar */}
                        <div style={{ position: 'relative' }}>
                            <input
                                ref={inputRef}
                                type="text"
                                className="form-control form-control-lg"
                                placeholder="🔍 Buscar Producto..."
                                value={search}
                                onFocus={() => fetchProducts()}
                                onChange={(e) => {
                                    setSearch(e.target.value);
                                    setShowDropdown(true);
                                    setSelectedProduct(null);
                                }}
                                onKeyDown={(e) => { if (e.key === 'Enter') handleAddItem(); }}
                                autoFocus
                                autoComplete="off"
                                style={{ borderRadius: '12px', padding: '15px', fontSize: '1.2rem', background: '#f8f9fa', border: 'none' }}
                            />
                        </div>

                        {/* Inline Add Controls */}
                        {/* Inline Add Controls - GRID LAYOUT */}
                        <div style={{
                            display: 'grid',
                            gridTemplateColumns: 'minmax(100px, 1fr) 1fr 1fr 0.8fr 1.2fr auto',
                            gap: '10px',
                            alignItems: 'end',
                            background: '#e3f2fd',
                            borderRadius: '12px',
                            padding: '15px'
                        }}>
                            {/* Quantity Input */}
                            <div>
                                <label className="form-label text-muted mb-1" style={{ fontSize: '0.75rem', fontWeight: 'bold' }}>Cant.</label>
                                <input
                                    id="input-qty"
                                    type="number"
                                    className="form-control border-0 text-center"
                                    value={quantityToAdd}
                                    onChange={(e) => setQuantityToAdd(e.target.value)}
                                    onKeyDown={(e) => { if (e.key === 'Enter') document.getElementById('input-cost')?.focus(); }}
                                    placeholder="1"
                                    style={{ fontSize: '1.1rem', fontWeight: 'bold', height: '40px', background: 'white' }}
                                />
                            </div>

                            {/* Cost Price Input */}
                            <div>
                                <label className="form-label text-muted mb-1" style={{ fontSize: '0.75rem', fontWeight: 'bold' }}>P. Compra ($)</label>
                                <input
                                    id="input-cost"
                                    type="text"
                                    className="form-control border-0"
                                    value={costPriceToAdd}
                                    onChange={(e) => {
                                        const val = e.target.value.replace(/[^0-9.,]/g, '');
                                        setCostPriceToAdd(val);
                                    }}
                                    onBlur={(e) => {
                                        if (!e.target.value) return;
                                        let val = e.target.value.replace(/\./g, '').replace(',', '.');
                                        if (!isNaN(val)) {
                                            const formatted = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(val);
                                            setCostPriceToAdd(formatted);
                                        }
                                    }}
                                    onKeyDown={(e) => { if (e.key === 'Enter') document.getElementById('input-sale')?.focus(); }}
                                    placeholder="0"
                                    style={{ fontSize: '1.1rem', fontWeight: 'bold', height: '40px', background: 'white' }}
                                />
                            </div>

                            {/* Sale Price Input */}
                            <div>
                                <label className="form-label text-muted mb-1" style={{ fontSize: '0.75rem', fontWeight: 'bold' }}>P. Venta ($)</label>
                                <input
                                    id="input-sale"
                                    type="text"
                                    className="form-control border-0"
                                    value={salePriceToAdd}
                                    onChange={(e) => {
                                        const val = e.target.value.replace(/[^0-9.,]/g, '');
                                        setSalePriceToAdd(val);
                                    }}
                                    onBlur={(e) => {
                                        if (!e.target.value) return;
                                        let val = e.target.value.replace(/\./g, '').replace(',', '.');
                                        if (!isNaN(val)) {
                                            const formatted = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(val);
                                            setSalePriceToAdd(formatted);
                                        }
                                    }}
                                    onKeyDown={(e) => { if (e.key === 'Enter') document.getElementById('input-batch')?.focus(); }}
                                    placeholder="0"
                                    style={{ fontSize: '1.1rem', fontWeight: 'bold', color: '#D32F2F', height: '40px', background: 'white' }}
                                />
                            </div>

                            {/* Batch Input */}
                            <div>
                                <label className="form-label text-muted mb-1" style={{ fontSize: '0.75rem', fontWeight: 'bold' }}>Lote</label>
                                <input
                                    id="input-batch"
                                    type="text"
                                    className="form-control border-0"
                                    value={batchNumberToAdd}
                                    onChange={(e) => setBatchNumberToAdd(e.target.value)}
                                    onKeyDown={(e) => { if (e.key === 'Enter') handleAddItem(); }}
                                    placeholder="Auto"
                                    style={{ fontSize: '1rem', fontWeight: '500', height: '40px', background: 'white' }}
                                />
                            </div>

                            {/* Real-time Total Display */}
                            <div>
                                <label className="form-label text-muted mb-1" style={{ fontSize: '0.75rem', fontWeight: 'bold' }}>Total</label>
                                <input
                                    type="text"
                                    className="form-control border-0"
                                    value={formatMoney((parseFloat(quantityToAdd) || 0) * (parseFloat(costPriceToAdd.toString().replace(/\./g, '').replace(',', '.')) || 0))}
                                    readOnly
                                    disabled
                                    style={{ fontSize: '1.1rem', fontWeight: 'bold', color: '#1976d2', height: '40px', background: 'white' }}
                                />
                            </div>

                            <button className="btn btn-primary h-100" onClick={handleAddItem} style={{ borderRadius: '8px', padding: '0 20px', fontWeight: 'bold', height: '40px', minWidth: '120px' }}>
                                + AGREGAR
                            </button>
                        </div>
                    </div>
                </div>

                {/* Product Results Grid */}
                <div style={{ flex: 1, overflowY: 'auto', background: 'white', borderRadius: '15px', padding: '20px', boxShadow: '0 2px 10px rgba(0,0,0,0.05)' }}>
                    {search.trim() ? (
                        filteredProducts.length > 0 ? (
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))', gap: '15px' }}>
                                {filteredProducts.map(p => (
                                    <div
                                        key={p.sku}
                                        onClick={() => handleSelectProduct(p)}
                                        style={{
                                            border: selectedProduct?.sku === p.sku ? '2px solid #2196f3' : '1px solid #eee',
                                            borderRadius: '12px', padding: '15px', cursor: 'pointer',
                                            background: selectedProduct?.sku === p.sku ? '#e3f2fd' : 'white',
                                            transition: 'all 0.2s', position: 'relative'
                                        }}
                                        className="hover-shadow"
                                    >
                                        <div style={{ fontSize: '1.5rem', marginBottom: '10px' }}>📦</div>
                                        <div style={{ fontWeight: 'bold', fontSize: '1rem', lineHeight: '1.2', marginBottom: '5px', height: '40px', overflow: 'hidden' }}>{p.name}</div>
                                        <div style={{ fontSize: '0.9rem', color: '#666' }}>{p.sku}</div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '10px' }}>
                                            <div style={{ fontSize: '1.1rem', fontWeight: 'bold', color: '#2e7d32' }}>${p.sale_price.toLocaleString()}</div>
                                            <div style={{ fontSize: '0.8rem', color: '#555' }}>Costo: ${p.cost_price}</div>
                                        </div>
                                        <div style={{ position: 'absolute', top: '10px', right: '10px', fontSize: '0.8rem', padding: '2px 8px', borderRadius: '10px', background: p.stock > 0 ? '#e8f5e9' : '#ffebee', color: p.stock > 0 ? '#2e7d32' : '#c62828' }}>
                                            {p.stock}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            // Helper for creating new product if not found
                            <div style={{ textAlign: 'center', marginTop: '50px' }}>
                                <div style={{ fontSize: '3rem', marginBottom: '10px' }}>✨</div>
                                <h4>No encontrado: "{search}"</h4>
                                <button
                                    className="btn btn-outline-primary mt-3"
                                    onClick={() => promptCreateProduct(search)}
                                >
                                    + Crear Nuevo Producto
                                </button>
                            </div>
                        )
                    ) : (
                        <div style={{ textAlign: 'center', color: '#ccc', marginTop: '100px' }}>
                            <div style={{ fontSize: '4rem', marginBottom: '20px' }}>🔍</div>
                            <h3>Busca para agregar al inventario</h3>
                            <p>Ingresa nombre o código de barras</p>
                        </div>
                    )}
                </div>
            </div>

            {/* --- RIGHT PANEL: PURCHASE TICKET --- */}
            <div style={{ display: 'flex', flexDirection: 'column', height: '100%', background: 'white', borderRadius: '15px', boxShadow: '0 4px 20px rgba(0,0,0,0.1)', overflow: 'hidden' }}>

                {/* Header: Provider */}
                <div style={{ padding: '20px', background: '#8B0000', color: 'white' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                        <div style={{ fontWeight: 'bold', fontSize: '0.9rem', opacity: 0.9 }}>👤 PROVEEDOR</div>
                        <button className="btn btn-sm btn-light" style={{ fontSize: '0.75rem', padding: '4px 12px', borderRadius: '20px', fontWeight: 'bold', color: '#8B0000', border: 'none' }} onClick={promptCreateProvider}>+ NUEVO</button>
                    </div>

                    <select
                        className="form-select border-0"
                        value={selectedProvider}
                        onChange={e => setSelectedProvider(e.target.value)}
                        style={{
                            background: 'white',
                            color: '#333',
                            borderRadius: '10px',
                            fontWeight: '600',
                            padding: '12px',
                            boxShadow: '0 4px 10px rgba(0,0,0,0.2)',
                            fontSize: '1rem',
                            cursor: 'pointer'
                        }}
                    >
                        <option value="">-- Seleccionar Proveedor --</option>
                        <option value="no_provider">🏢 Sin Proveedor (General)</option>
                        {providers.map(p => (
                            <option key={p.id} value={p.id}>{p.name}</option>
                        ))}
                    </select>
                </div>

                {/* Body: Cart - EDITABLE QUANTITY */}
                <div style={{ flex: 1, overflowY: 'auto', background: '#f8f9fa' }}>
                    {cart.length === 0 ? (
                        <div style={{ textAlign: 'center', padding: '40px', color: '#adb5bd' }}>
                            <div style={{ fontSize: '2rem' }}>🛒</div>
                            <p>Lista de compra vacía</p>
                        </div>
                    ) : (
                        <table className="table mb-0 w-100" style={{ tableLayout: 'fixed' }}>
                            <thead className="bg-light sticky-top">
                                <tr>
                                    <th style={{ width: '35%', padding: '10px 15px' }}>Prod</th>
                                    <th className="text-center" style={{ width: '25%', padding: '10px 5px' }}>Cant</th>
                                    <th className="text-end" style={{ width: '20%', padding: '10px 5px' }}>Costo</th>
                                    <th className="text-end" style={{ width: '20%', padding: '10px 5px' }}>Venta</th>
                                    <th className="text-end" style={{ width: '20%', padding: '10px 5px' }}>Sub</th>
                                    <th style={{ width: '10%' }}></th>
                                </tr>
                            </thead>
                            <tbody>
                                {cart.map((item, index) => (
                                    <tr key={index} style={{ background: 'white', borderBottom: '1px solid #f1f1f1' }}>
                                        <td style={{ padding: '10px 15px', verticalAlign: 'middle', wordWrap: 'break-word' }}>
                                            <div style={{ fontWeight: '600', fontSize: '1rem', lineHeight: '1.2' }}>{item.name}</div>
                                            <small className="text-muted" style={{ fontSize: '0.8rem' }}>{item.sku}</small>
                                        </td>
                                        <td className="text-center align-middle" style={{ padding: '10px 5px' }}>
                                            <input
                                                type="number"
                                                className="form-control form-control-sm text-center"
                                                value={item.quantity}
                                                step="0.01"
                                                onChange={(e) => {
                                                    const newQty = parseFloat(e.target.value);
                                                    if (isNaN(newQty) || newQty <= 0) return;
                                                    const updatedCart = [...cart];
                                                    const cost = parseFloat(updatedCart[index].cost_price.toString().replace(/\./g, '').replace(',', '.')) || 0;
                                                    updatedCart[index] = {
                                                        ...updatedCart[index],
                                                        quantity: newQty,
                                                        subtotal: newQty * cost
                                                    };
                                                    setCart(updatedCart);
                                                }}
                                                style={{ width: '100%', padding: '2px', fontWeight: 'bold' }}
                                                min="0.1"
                                            />
                                        </td>
                                        <td className="text-end align-middle" style={{ padding: '10px 5px' }}>
                                            <input
                                                type="text"
                                                className="form-control form-control-sm text-end"
                                                value={item.cost_price}
                                                onChange={(e) => {
                                                    const val = e.target.value.replace(/[^0-9.,]/g, '');
                                                    const updatedCart = [...cart];
                                                    updatedCart[index] = { ...updatedCart[index], cost_price: val };
                                                    const cost = parseFloat(val.replace(/\./g, '').replace(',', '.')) || 0;
                                                    updatedCart[index].subtotal = updatedCart[index].quantity * cost;
                                                    setCart(updatedCart);
                                                }}
                                                onBlur={(e) => {
                                                    if (!e.target.value) return;
                                                    let val = e.target.value.replace(/\./g, '').replace(',', '.');
                                                    if (!isNaN(val)) {
                                                        const formatted = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(val);
                                                        const updatedCart = [...cart];
                                                        updatedCart[index] = { ...updatedCart[index], cost_price: formatted };
                                                        setCart(updatedCart);
                                                    }
                                                }}
                                                style={{ width: '100%', fontWeight: 'normal', fontSize: '0.9rem' }}
                                            />
                                        </td>
                                        <td className="text-end align-middle" style={{ padding: '10px 5px', color: '#D32F2F' }}>{formatMoney(item.sale_price)}</td>
                                        <td className="text-end align-middle fw-bold" style={{ padding: '10px 5px', fontSize: '1.1rem' }}>{formatMoney(item.subtotal)}</td>
                                        <td className="align-middle text-center" style={{ padding: '10px 10px 10px 0' }}>
                                            <button className="btn btn-sm btn-outline-danger border-0 p-0" onClick={() => removeItem(index)} style={{ fontSize: '1.2rem', lineHeight: 1, width: '24px', height: '24px' }}>×</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* Footer: Payment & Actions */}
                <div style={{ padding: '15px', background: 'white', borderTop: '1px solid #eee' }}>

                    {/* Totals */}
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px', borderBottom: '1px dashed #eee', paddingBottom: '15px' }}>
                        <span style={{ fontWeight: 'bold' }}>TOTAL COMPRA</span>
                        <span style={{ fontWeight: 'bold', fontSize: '1.6rem', color: '#D32F2F' }}>{formatMoney(totalPrice)}</span>
                    </div>

                    {/* Payment Controls Grid */}
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px', marginBottom: '15px' }}>
                        <div>
                            <label style={{ fontSize: '0.75rem', fontWeight: 'bold', color: '#666', marginBottom: '5px', display: 'block' }}>FECHA</label>
                            <input
                                type="date"
                                className="form-control"
                                value={customDate}
                                onChange={e => setCustomDate(e.target.value)}
                                style={{ height: '45px', borderRadius: '10px', border: '1px solid #ced4da', fontWeight: '500' }}
                            />
                        </div>
                        <div>
                            <label style={{ fontSize: '0.75rem', fontWeight: 'bold', color: '#666', marginBottom: '5px', display: 'block' }}>ESTADO</label>
                            <select
                                className="form-control"
                                value={paymentStatus}
                                onChange={e => setPaymentStatus(e.target.value)}
                                style={{ height: '45px', borderRadius: '10px', border: '1px solid #ced4da', fontWeight: '500' }}
                            >
                                <option value="paid">✅ Pagado</option>
                                <option value="credit">⏳ Crédito</option>
                                <option value="partial">⚖️ Abono</option>
                            </select>
                        </div>
                    </div>

                    {paymentStatus !== 'credit' && (
                        <div style={{ marginBottom: '15px' }}>
                            <label style={{ fontSize: '0.75rem', fontWeight: 'bold', color: '#666', marginBottom: '5px', display: 'block' }}>MÉTODO</label>
                            <select
                                className="form-control"
                                value={paymentMethod}
                                onChange={e => setPaymentMethod(e.target.value)}
                                style={{ height: '45px', borderRadius: '10px', border: '1px solid #ced4da', fontWeight: '500' }}
                            >
                                <option value="">-- Método de Pago --</option>
                                <option value="cash">💵 Efectivo</option>
                                <option value="nequi">📱 Nequi</option>
                                <option value="bancolombia">🏦 Bancolombia</option>
                            </select>
                        </div>
                    )}

                    {paymentStatus === 'partial' && (
                        <div style={{ marginBottom: '15px' }}>
                            <label style={{ fontSize: '0.75rem', fontWeight: 'bold', color: '#666', marginBottom: '5px', display: 'block' }}>MONTO ABONO</label>
                            <input
                                type="number"
                                className="form-control form-control-lg"
                                placeholder="$ 0"
                                value={depositAmount}
                                onChange={e => setDepositAmount(e.target.value)}
                                style={{ borderRadius: '10px', fontWeight: 'bold', fontSize: '1.2rem' }}
                            />
                        </div>
                    )}

                    <button
                        className="btn btn-success w-100 py-3 fw-bold"
                        style={{ borderRadius: '12px', fontSize: '1.2rem', boxShadow: '0 4px 6px rgba(46, 125, 50, 0.2)' }}
                        onClick={handleFinishPurchase}
                        disabled={cart.length === 0 || loading}
                    >
                        {loading ? 'Guardando...' : '✓ REGISTRAR COMPRA'}
                    </button>
                </div>
            </div>

            {/* Mobile CSS override */}
            <style>{`
                @media (max-width: 991px) {
                    .desktop-grid {
                        display: flex !important;
                        flex-direction: column;
                    }
                }
                .hover-shadow:hover {
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                    transform: translateY(-2px);
                }
            `}</style>

            {/* CONFIRMATION MODAL */}
            {showConfirmation && (
                <div style={{
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                    backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 9999,
                    display: 'flex', justifyContent: 'center', alignItems: 'center'
                }}>
                    <div style={{
                        background: 'white', borderRadius: '15px', width: '90%', maxWidth: '600px',
                        boxShadow: '0 5px 30px rgba(0,0,0,0.3)', overflow: 'hidden', display: 'flex', flexDirection: 'column',
                        maxHeight: '90vh'
                    }}>
                        {/* Modal Header */}
                        <div className="card-header" style={{ background: '#2c3e50', color: '#ecf0f1', display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '10px 20px' }}>
                            <h4 style={{ margin: 0, fontWeight: 'bold' }}>
                                {editMode ? `✏️ Editando Compra #${String(editId).padStart(6, '0')}` : '🛒 Nueva Compra'}
                            </h4>
                            <div style={{ display: 'flex', gap: '15px', alignItems: 'center' }}>
                                <button onClick={() => setShowConfirmation(false)} style={{ border: 'none', background: 'none', fontSize: '1.5rem', cursor: 'pointer', color: '#ecf0f1' }}>×</button>
                            </div>
                        </div>

                        {/* Modal Body */}
                        <div style={{ padding: '25px', overflowY: 'auto' }}>

                            {/* Summary Cards */}
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px', marginBottom: '20px' }}>
                                <div style={{ background: '#e3f2fd', padding: '15px', borderRadius: '10px' }}>
                                    <small className="text-muted d-block uppercase">PROVEEDOR</small>
                                    <div style={{ fontWeight: 'bold', fontSize: '1.1rem' }}>
                                        {getProviderName()}
                                    </div>
                                </div>
                                <div style={{ background: '#fff3e0', padding: '15px', borderRadius: '10px' }}>
                                    <small className="text-muted d-block uppercase">FECHA</small>
                                    <div style={{ fontWeight: 'bold', fontSize: '1.1rem' }}>{customDate}</div>
                                </div>
                                <div style={{ background: '#e8f5e9', padding: '15px', borderRadius: '10px' }}>
                                    <small className="text-muted d-block uppercase">MÉTODO PAGO</small>
                                    <div style={{ fontWeight: 'bold', fontSize: '1.1rem' }}>
                                        {paymentStatus === 'paid' ? 'Contado' : (paymentStatus === 'credit' ? 'Crédito' : 'Parcial')}
                                        <span className="text-muted small"> ({paymentMethod === 'cash' ? 'Efectivo' : (paymentMethod === 'nequi' ? 'Nequi' : (paymentMethod === 'bancolombia' ? 'Bancolombia' : '-'))})</span>
                                    </div>
                                </div>
                                {paymentStatus === 'partial' && (
                                    <div style={{ background: '#ffebee', padding: '15px', borderRadius: '10px' }}>
                                        <small className="text-muted d-block uppercase">ABONO</small>
                                        <div style={{ fontWeight: 'bold', fontSize: '1.1rem', color: '#d32f2f' }}>
                                            {formatMoney(depositAmount)}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Items Table */}
                            <h6 style={{ margin: '0 0 10px 0', borderBottom: '2px solid #eee', paddingBottom: '10px' }}>Detalle de Productos</h6>
                            <table className="table table-sm">
                                <thead>
                                    <tr className="text-muted">
                                        <th>Producto</th>
                                        <th className="text-center">Cant</th>
                                        <th className="text-end">Costo</th>
                                        <th className="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {cart.map((item, idx) => (
                                        <tr key={idx}>
                                            <td>
                                                <div>{item.name}</div>
                                                <small className="text-muted">{item.sku}</small>
                                            </td>
                                            <td className="text-center">{item.quantity}</td>
                                            <td className="text-end">{formatMoney(item.cost_price)}</td>
                                            <td className="text-end fw-bold">{formatMoney(item.subtotal)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr style={{ fontSize: '1.2rem', borderTop: '2px solid #333' }}>
                                        <td colSpan="3" className="text-end fw-bold">TOTAL:</td>
                                        <td className="text-end fw-bold text-success">{formatMoney(totalTotal)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {/* Modal Footer */}
                        <div style={{ padding: '20px', background: '#f8f9fa', borderTop: '1px solid #e9ecef', display: 'flex', justifyContent: 'flex-end', gap: '15px' }}>
                            <button
                                className="btn btn-secondary btn-lg"
                                onClick={() => setShowConfirmation(false)}
                                style={{ borderRadius: '10px', padding: '10px 25px' }}
                            >
                                Cancelar
                            </button>
                            <button
                                className="btn btn-success btn-lg"
                                onClick={executePurchase}
                                disabled={loading}
                                style={{ borderRadius: '10px', padding: '10px 30px', fontWeight: 'bold', display: 'flex', alignItems: 'center', gap: '10px' }}
                            >
                                {loading ? 'Procesando...' : (
                                    <><span>✓</span> Registrar Compra</>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}


