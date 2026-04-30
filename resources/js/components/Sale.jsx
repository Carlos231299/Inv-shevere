import React, { useState, useEffect, useRef } from 'react';
import Swal from 'sweetalert2';

export default function Sale() {
    // Product Search State
    const [products, setProducts] = useState([]);
    const [search, setSearch] = useState('');
    const [filteredProducts, setFilteredProducts] = useState([]);
    const [showDropdown, setShowDropdown] = useState(false);

    // Selection state
    // Selection state
    const [selectedProduct, setSelectedProduct] = useState(null);

    // Helper to format currency for inputs
    const formatCurrencyValue = (val) => {
        if (!val) return '';
        return new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(val);
    };

    // Helper to parse currency string
    const parseCurrencyValue = (str) => {
        if (!str) return 0;
        if (typeof str === 'number') return str;
        return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
    };

    // Cart State
    const [cart, setCart] = useState([]);
    const [loading, setLoading] = useState(false);

    // Client & Payment
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const [clientQuery, setClientQuery] = useState('');
    const [clients, setClients] = useState([]);
    const [selectedClient, setSelectedClient] = useState(null);

    const [paymentStatus, setPaymentStatus] = useState('paid'); // paid, credit, partial
    const [depositAmount, setDepositAmount] = useState('');
    const [customDate, setCustomDate] = useState(new Date().toISOString().split('T')[0]); // Default Today
    const [nextId, setNextId] = useState('...');
    const [editMode, setEditMode] = useState(false);
    const [editId, setEditId] = useState(null);

    // Split Payment State
    const [isSplitPayment, setIsSplitPayment] = useState(false);
    const [payments, setPayments] = useState([]); // Array of { method: 'cash', amount: 0 }

    // CSRF Token Helper
    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const inputRef = useRef(null);

    // Fetch Products
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

    useEffect(() => {
        fetchProducts();

        const fetchNextId = async () => {
            try {
                const res = await fetch('/api/sales/next-id');
                const data = await res.json();
                setNextId(data.next_id);
            } catch (error) {
                console.error('Error fetching next ID:', error);
            }
        }

        const params = new URLSearchParams(window.location.search);
        const editIdStr = params.get('edit');
        if (editIdStr) {
            setEditMode(true);
            setEditId(editIdStr);
            fetchSaleForEdit(editIdStr);
        } else {
            fetchNextId();
        }
    }, []); // Run once on mount

    const fetchSaleForEdit = async (id) => {
        try {
            const res = await fetch(`/api/sales/${id}`, {
                headers: { 'Accept': 'application/json' }
            });
            const sale = await res.json();

            // Populate Cart
            const newCart = sale.movements.map(m => ({
                id: m.id,
                sku: m.product_sku,
                name: m.product.name,
                quantity: parseFloat(m.quantity) || 0,
                displayQuantity: parseFloat(m.quantity) || 0,
                unitPrice: parseFloat(m.price_at_moment) || 0,
                sale_price: m.price_at_moment, // Add this line because backend requires it
                subtotal: parseFloat(m.total) || 0,
                measure_type: m.product.measure_type
            }));

            setCart(newCart);
            setCustomDate(sale.created_at.split('T')[0]);
            setPaymentMethod(sale.payment_method);
            setDiscount(sale.discount > 0 ? sale.discount : '');
            setReceivedAmount(sale.received_amount > 0 ? sale.received_amount : '');

            if (sale.client) {
                setSelectedClient(sale.client);
                setClientQuery(sale.client.name);
            }

            // Determine payment status
            if (sale.credit) {
                const paidAmt = parseFloat(sale.credit.paid_amount) || 0;
                setPaymentStatus(paidAmt > 0 ? 'partial' : 'credit');
                setDepositAmount(paidAmt > 0 ? paidAmt : '');

                // Populate payments if split
                if (sale.credit.payments && sale.credit.payments.length > 0) {
                    if (sale.credit.payments.length > 1) {
                        setIsSplitPayment(true);
                        setPayments(sale.credit.payments.map(p => ({
                            method: p.payment_method,
                            amount: parseFloat(p.amount)
                        })));
                    } else {
                        setPaymentMethod(sale.credit.payments[0].payment_method);
                    }
                } else {
                    setPaymentMethod('cash'); // Fallback
                }
            } else {
                setPaymentStatus('paid');
                // Check if we have SalePayments (split) or single method
                // We assume backend might send 'payments' relation or we deduce from movements if 'payments' table is empty (legacy)
                // But for new Phase 2, we should load from SalePayment model if available.
                // Assuming the generic 'sale' response includes 'payments' (SalePayment) or we check logic.
                // For now, let's assume if 'payment_method' is 'mixed', we look for detailed payments.

                // If backend sends specific payments list (we might need to update Controller show method to include sale_payments)
                // Let's assume standard behavior for now:
                let method = sale.payment_method;
                if (method === 'mixed' || (sale.sale_payments && sale.sale_payments.length > 0)) {
                    setIsSplitPayment(true);
                    if (sale.sale_payments && sale.sale_payments.length > 0) {
                        setPayments(sale.sale_payments.map(p => ({
                            method: p.payment_method,
                            amount: parseFloat(p.amount)
                        })));
                    }
                } else {
                    if (!method && sale.movements.length > 0) {
                        method = sale.movements[0].payment_method;
                    }
                    if (method === 'credit') method = 'cash';
                    setPaymentMethod(method || 'cash');
                }
            }

            setNextId(sale.id); // Show current ID being edited

            Swal.fire({
                title: 'Modo Edición',
                text: `Editando Venta #${String(id).padStart(6, '0')}`,
                icon: 'info',
                timer: 1500,
                showConfirmButton: false
            });
        } catch (error) {
            console.error('Error fetching sale for edit:', error);
            Swal.fire('Error', 'No se pudo cargar la venta para editar.', 'error');
        }
    };

    // Filter Products
    useEffect(() => {
        if (!search.trim()) {
            setFilteredProducts([]);
            return;
        }
        if (selectedProduct && search === selectedProduct.name) {
            setFilteredProducts([]);
            return;
        }
        const lowerSearch = search.toLowerCase();
        const filtered = products.filter(p =>
            p.name.toLowerCase().includes(lowerSearch) ||
            p.sku.toLowerCase().includes(lowerSearch)
        );
        setFilteredProducts(filtered);
    }, [search, products, selectedProduct]);

    // Auto Add Product to Cart (Scanner or Dropdown Click)
    const autoAddProduct = (product) => {
        if (product.stock <= 0) {
            Swal.fire('Sin Stock', `El producto ${product.name} tiene stock 0.`, 'warning');
            return;
        }

        let defaultUnitValue = 'unit';
        let defaultUnitLabel = 'Unidad';
        let factor = 1;

        if (product.measure_type === 'kg') {
            defaultUnitValue = 'kg';
            defaultUnitLabel = 'Kilogramos (kg)';
        }

        const inputQty = 1;
        const inputPrice = parseFloat(product.sale_price) || 0;
        const normalizedQuantity = inputQty * factor;

        let remainingBaseQty = normalizedQuantity;
        const newItems = [];
        const productBatches = product.batches || [];

        if (productBatches.length === 0) {
            newItems.push({
                ...product,
                displayQuantity: inputQty,
                displayUnit: defaultUnitLabel,
                displayUnitValue: defaultUnitValue,
                quantity: normalizedQuantity,
                unitPrice: inputPrice,
                sale_price: product.sale_price,
                subtotal: normalizedQuantity * inputPrice
            });
        } else {
            for (const batch of productBatches) {
                if (remainingBaseQty <= 0) break;

                const takeFromBatch = Math.min(remainingBaseQty, batch.quantity);
                if (takeFromBatch <= 0) continue;

                const portionDisplayQty = (takeFromBatch / factor);

                newItems.push({
                    ...product,
                    name: `${product.name} (${batch.batch_number || 'Lote'})`,
                    displayQuantity: portionDisplayQty,
                    displayUnit: defaultUnitLabel,
                    displayUnitValue: defaultUnitValue,
                    quantity: takeFromBatch,
                    unitPrice: inputPrice,
                    sale_price: product.sale_price,
                    subtotal: takeFromBatch * inputPrice
                });

                remainingBaseQty -= takeFromBatch;
            }

            if (remainingBaseQty > 0) {
                const portionDisplayQty = (remainingBaseQty / factor);
                newItems.push({
                    ...product,
                    displayQuantity: portionDisplayQty,
                    displayUnit: defaultUnitLabel,
                    displayUnitValue: defaultUnitValue,
                    quantity: remainingBaseQty,
                    unitPrice: inputPrice,
                    sale_price: product.sale_price,
                    subtotal: remainingBaseQty * inputPrice
                });
            }
        }

        // Add to top of cart for visibility
        setCart(prev => [...newItems, ...prev]);

        setSearch('');
        setSelectedProduct(null);
        setShowDropdown(false);
        setTimeout(() => inputRef.current?.focus(), 50);
    };

    const handleAddItem = () => {
        const match = products.find(p => p.sku === search || p.name.toLowerCase() === search.toLowerCase());
        if (match) {
            autoAddProduct(match);
            return;
        }
        if (search.trim() !== '') {
            Swal.fire('No encontrado', 'El código de barras o nombre no coincide con ningún producto.', 'info');
        }
    };

    const removeItem = (index) => {
        setCart(prev => prev.filter((_, i) => i !== index));
    };


    // Confirmation Modal State
    const [showConfirmation, setShowConfirmation] = useState(false);

    // Discount State
    const [discount, setDiscount] = useState('');

    // Change Calculation State
    const [receivedAmount, setReceivedAmount] = useState('');

    const handleFinishSale = () => {
        if (cart.length === 0) return;

        // Validation for 0 quantity
        const hasZeroQty = cart.some(item => parseFloat(item.displayQuantity) <= 0 || isNaN(parseFloat(item.displayQuantity)));
        if (hasZeroQty) {
            Swal.fire('Cantidades en cero', 'Hay productos con cantidad cero en el carrito. Por favor elimínelos o asigne una cantidad válida antes de cobrar.', 'warning');
            return;
        }

        // Validation for Credit/Partial
        if ((paymentStatus === 'credit' || paymentStatus === 'partial') && !selectedClient) {
            Swal.fire('Atención', 'Debe seleccionar un cliente para ventas a crédito o parciales.', 'warning');
            return;
        }

        // Calculate Totals for validation
        const subTotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
        const totalToPay = Math.max(0, subTotal - (parseFloat(discount) || 0));

        // Validation based on Mode
        if (isSplitPayment) {
            const sumPayments = payments.reduce((sum, p) => sum + (p.amount || 0), 0);
            if (paymentStatus === 'paid') {
                if (Math.abs(sumPayments - totalToPay) > 50) { // Tolerance of 50 pesos
                    Swal.fire('Monto Incorrecto', `La suma de pagos ($${sumPayments.toLocaleString()}) debe ser igual al Total ($${totalToPay.toLocaleString()})`, 'warning');
                    return;
                }
            } else if (paymentStatus === 'partial') {
                if (sumPayments <= 0) {
                    Swal.fire('Monto Incorrecto', 'Debe agregar al menos un abono.', 'warning');
                    return;
                }
                if (sumPayments >= totalToPay) {
                    Swal.fire('Monto Incorrecto', `El abono ($${sumPayments.toLocaleString()}) no puede ser mayor o igual al total ($${totalToPay.toLocaleString()}). Use el estado 'Pagado' en su lugar.`, 'warning');
                    return;
                }
            }
        } else {
            // Single Mode Validations
            if (paymentStatus === 'paid' && paymentMethod === 'cash') {
                const received = parseFloat(receivedAmount);
                if (!received || received < totalToPay) {
                    Swal.fire('Monto Insuficiente', `El monto recibido debe ser mayor o igual a $${totalToPay.toLocaleString()}`, 'warning');
                    return;
                }
            }
            if (paymentStatus === 'partial') {
                const deposit = parseFloat(depositAmount);
                if (!deposit || deposit <= 0) {
                    Swal.fire('Monto Incorrecto', 'Ingrese un monto de abono válido.', 'warning');
                    return;
                }
            }
        }

        setShowConfirmation(true);
    };

    const executeSale = async () => {
        setLoading(true);
        try {
            // Determine actual method to send
            const finalMethod = (paymentStatus === 'credit' || paymentStatus === 'partial') && paymentMethod === 'cash' ? 'credit' : paymentMethod;

            // Construct Payments Array
            let finalPayments = [];
            const subTotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
            const totalTotal = Math.max(0, subTotal - (parseFloat(discount) || 0));

            if (isSplitPayment) {
                finalPayments = payments;
            } else {
                if (paymentStatus === 'paid') {
                    finalPayments = [{ method: paymentMethod, amount: totalTotal }];
                } else if (paymentStatus === 'partial') {
                    finalPayments = [{ method: paymentMethod, amount: parseFloat(depositAmount) || 0 }];
                } else if (paymentStatus === 'credit') {
                    finalPayments = [];
                }
            }

            const url = editMode ? `/api/sales/${editId}` : '/api/sales';
            const method = editMode ? 'POST' : 'POST'; // Keep POST for now, handle PUT if needed

            const payload = {
                items: cart,
                payment_method: isSplitPayment ? 'mixed' : finalMethod,
                payment_status: paymentStatus,
                payments: finalPayments,
                client_id: selectedClient?.id,
                deposit_amount: depositAmount || 0,
                discount: discount || 0,
                custom_date: customDate,
                received_amount: receivedAmount || 0,
                change_amount: Math.max(0, (parseFloat(receivedAmount) || 0) - totalTotal)
            };
            console.log('SALE PAYLOAD:', payload);

            const res = await fetch(url, {
                method: editMode ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                const d = await res.json();
                Swal.fire({ icon: 'success', title: editMode ? 'Venta Actualizada' : 'Venta Registrada', showConfirmButton: false, timer: 1500 });
                if (d.sale_id) {
                    window.open(`/sales/${d.sale_id}/ticket`, '_blank');
                }

                setCart([]);
                setSelectedClient(null);
                setClientQuery('');
                setPaymentMethod('cash');
                setPaymentStatus('paid');
                setDepositAmount('');
                setDiscount('');
                setReceivedAmount('');
                setIsSplitPayment(false);
                setPayments([]);
                setShowConfirmation(false);

                if (editMode) {
                    setEditMode(false);
                    setEditId(null);
                    window.history.replaceState({}, document.title, "/sales");
                } else {
                    try {
                        const nextIdRes = await fetch('/api/sales/next-id');
                        const nextIdData = await nextIdRes.json();
                        setNextId(nextIdData.next_id);
                    } catch (e) {
                        console.error('Error fetching next ID:', e);
                    }
                }
                await fetchProducts(); // Force wait for fresh stock after sale
            } else {
                const d = await res.json();
                Swal.fire('Error', d.message || 'Error', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Conexión fallida', 'error');
        } finally {
            setLoading(false);
        }
    };

    // Client Search logic
    useEffect(() => {
        if (clientQuery.length > 2) {
            const timer = setTimeout(async () => {
                const res = await fetch(`/api/clients/search?q=${clientQuery}`);
                const data = await res.json();
                setClients(data);
            }, 300);
            return () => clearTimeout(timer);
        } else {
            setClients([]);
        }
    }, [clientQuery]);
    const selectClient = (c) => { setSelectedClient(c); setClientQuery(c.name); setClients([]); };
    const formatMoney = (amount) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);

    // Total Calculation with Discount
    const subTotal = cart.reduce((sum, item) => sum + (parseFloat(item.subtotal) || 0), 0);
    const totalTotal = Math.max(0, subTotal - (parseFloat(discount) || 0));

    const promptCreateClient = async (name) => {
        const { value: formValues } = await Swal.fire({
            title: 'Nuevo Cliente',
            html: `
                <input id="swal-name" class="swal2-input" placeholder="Nombre" value="${name}" autocomplete="off">
                <input id="swal-phone" class="swal2-input" placeholder="Teléfono" autocomplete="off">
                <input id="swal-doc" class="swal2-input" placeholder="Documento (Opcional)" autocomplete="off">
            `,
            focusConfirm: false,
            preConfirm: () => {
                return {
                    name: document.getElementById('swal-name').value,
                    phone: document.getElementById('swal-phone').value,
                    document: document.getElementById('swal-doc').value
                }
            }
        });

        if (formValues) {
            try {
                const res = await fetch('/api/clients', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify(formValues)
                });

                if (res.ok) {
                    const data = await res.json();
                    selectClient(data.client); // Use the client object from the response
                    Swal.fire('Creado', 'Cliente registrado y seleccionado.', 'success');
                } else {
                    Swal.fire('Error', 'No se pudo crear el cliente.', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        }
    };

    return (
        <div className="pos-container" style={{
            display: 'flex',
            flexDirection: 'column',
            gap: '20px',
            height: 'calc(100vh - 60px)',
            overflowY: 'auto',
        }}>
            {/* 2-COLUMN LAYOUT CONTAINER */}
            {/* EDIT MODE BANNER */}
            {editMode && (
                <div style={{
                    position: 'fixed', top: 0, left: 0, right: 0, zIndex: 9999,
                    background: '#ff9800', color: '#fff', textAlign: 'center',
                    padding: '10px', fontWeight: 'bold', fontSize: '1.2rem',
                    boxShadow: '0 2px 10px rgba(0,0,0,0.2)'
                }}>
                    ⚠️ MODO EDICIÓN ACTIVO: Estás modificando la Venta #{String(editId).padStart(6, '0')}
                    <button onClick={() => window.location.href = '/sales'} style={{ marginLeft: '20px', border: '1px solid white', background: 'transparent', color: 'white', borderRadius: '5px', padding: '2px 10px', fontSize: '0.9rem', cursor: 'pointer' }}>
                        Cancelar Edición
                    </button>
                </div>
            )}
            <div style={{
                display: 'grid',
                gridTemplateColumns: 'minmax(0, 65%) minmax(0, 35%)',
                gap: '20px',
                height: '100%',
                alignItems: 'start'
            }} className="desktop-grid">

                {/* --- LEFT PANEL: PRODUCT CATALOG --- */}
                <div style={{ display: 'flex', flexDirection: 'column', gap: '20px', height: '100%' }}>

                    {/* Search Bar & Dropdown */}
                    <div style={{ background: 'white', padding: '15px', borderRadius: '15px', boxShadow: '0 2px 10px rgba(0,0,0,0.05)', position: 'relative', zIndex: 1050 }}>
                        <input
                            ref={inputRef}
                            type="text"
                            className="form-control form-control-lg"
                            placeholder="🔍 Escanea o busca un producto..."
                            value={search}
                            onFocus={() => fetchProducts()}
                            onChange={(e) => { setSearch(e.target.value); setShowDropdown(true); }}
                            onKeyDown={(e) => { if (e.key === 'Enter') handleAddItem(); }}
                            autoFocus
                            autoComplete="off"
                            style={{ borderRadius: '12px', fontSize: '1.2rem', padding: '15px', background: '#f8f9fa', border: 'none' }}
                        />

                        {/* Search Dropdown */}
                        {search.trim() !== '' && showDropdown && (
                            <div style={{
                                position: 'absolute', top: '100%', left: 0, right: 0, background: 'white',
                                borderRadius: '12px', boxShadow: '0 4px 15px rgba(0,0,0,0.1)', marginTop: '5px',
                                maxHeight: '300px', overflowY: 'auto', border: '1px solid #eee'
                            }}>
                                {filteredProducts.length > 0 ? (
                                    filteredProducts.map(p => (
                                        <div
                                            key={p.sku}
                                            onClick={() => autoAddProduct(p)}
                                            style={{
                                                padding: '12px 15px', borderBottom: '1px solid #eee', cursor: 'pointer',
                                                display: 'flex', justifyContent: 'space-between', alignItems: 'center'
                                            }}
                                            onMouseOver={(e) => e.currentTarget.style.background = '#f8f9fa'}
                                            onMouseOut={(e) => e.currentTarget.style.background = 'white'}
                                        >
                                            <div>
                                                <div style={{ fontWeight: 'bold' }}>{p.name}</div>
                                                <div style={{ fontSize: '0.85rem', color: '#666' }}>{p.sku}</div>
                                            </div>
                                            <div style={{ textAlign: 'right' }}>
                                                <div style={{ fontWeight: 'bold', color: '#2e7d32' }}>${p.sale_price.toLocaleString()}</div>
                                                <div style={{ fontSize: '0.8rem', color: p.stock > 0 ? 'green' : 'red' }}>Stock: {p.stock}</div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div style={{ padding: '15px', textAlign: 'center', color: '#999' }}>
                                        No se encontraron productos
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Cart Items Table (Moved to Left Panel) */}
                    <div style={{ flex: 1, overflowY: 'auto', background: 'white', borderRadius: '15px', boxShadow: '0 4px 20px rgba(0,0,0,0.05)', display: 'flex', flexDirection: 'column' }}>
                        {cart.length === 0 ? (
                            <div style={{ textAlign: 'center', margin: 'auto', padding: '40px', color: '#adb5bd' }}>
                                <div style={{ fontSize: '4rem', opacity: 0.5 }}>🛒</div>
                                <h3>Carrito vacío</h3>
                                <p>Usa la barra superior para escanear o buscar productos.</p>
                            </div>
                        ) : (
                            <table className="table mb-0 w-100" style={{ tableLayout: 'fixed' }}>
                                <thead style={{ background: '#0f172a', color: 'white' }} className="sticky-top">
                                    <tr>
                                        <th style={{ width: '45%', padding: '12px 15px', border: 'none' }}>Producto</th>
                                        <th className="text-center" style={{ width: '25%', padding: '12px 5px', border: 'none' }}>Cant.</th>
                                        <th className="text-end" style={{ width: '20%', padding: '12px 5px', border: 'none' }}>Valor</th>
                                        <th style={{ width: '10%', border: 'none' }}></th>
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
                                                    value={item.displayQuantity}
                                                    step="0.01"
                                                    onChange={(e) => {
                                                        const val = e.target.value;

                                                        if (val === '') {
                                                            const updatedCart = [...cart];
                                                            updatedCart[index] = {
                                                                ...updatedCart[index],
                                                                displayQuantity: '',
                                                                quantity: 0,
                                                                subtotal: 0
                                                            };
                                                            setCart(updatedCart);
                                                            return;
                                                        }

                                                        const newDisplayQty = parseFloat(val);
                                                        if (isNaN(newDisplayQty) || newDisplayQty < 0) return;

                                                        const updatedCart = [...cart];
                                                        const currentItem = updatedCart[index];

                                                        let factor = 1;
                                                        if (currentItem.measure_type === 'kg') {
                                                            if (currentItem.displayUnitValue === 'lb') factor = 0.5;
                                                            if (currentItem.displayUnitValue === 'g') factor = 0.001;
                                                        }

                                                        const newBaseQty = newDisplayQty * factor;

                                                        updatedCart[index] = {
                                                            ...currentItem,
                                                            displayQuantity: newDisplayQty,
                                                            quantity: newBaseQty,
                                                            subtotal: newBaseQty * currentItem.unitPrice
                                                        };
                                                        setCart(updatedCart);
                                                    }}
                                                    style={{ width: '100%', padding: '2px', fontWeight: 'bold', background: '#fff', color: '#333', border: '1px solid #ccc' }}
                                                />
                                                <small className="text-muted" style={{ fontSize: '0.75rem', display: 'block' }}>{item.displayUnit}</small>
                                            </td>
                                            <td className="text-end align-middle fw-bold" style={{ padding: '10px 5px', fontSize: '1rem' }}>{formatMoney(item.subtotal)}</td>
                                            <td className="align-middle text-end" style={{ padding: '10px 10px 10px 0' }}>
                                                <button className="btn btn-sm btn-outline-danger border-0 p-0" onClick={() => removeItem(index)} style={{ fontSize: '1.2rem', lineHeight: 1, width: '24px', height: '24px' }}>×</button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>

                {/* --- RIGHT PANEL: THE TICKET --- */}
                <div style={{ display: 'flex', flexDirection: 'column', height: '100%', background: 'white', borderRadius: '15px', boxShadow: '0 4px 20px rgba(0,0,0,0.1)', overflow: 'hidden' }}>



                    {/* Metadata Header: Client & Info (NOW BELOW CART) */}
                    <div style={{ padding: '15px', background: '#f1f1f1', borderTop: '1px solid #ddd', borderBottom: '1px solid #ddd' }}>

                        {/* Metadata Row: Date & Ticket Label */}
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
                            <input
                                type="date"
                                className="form-control form-control-sm border-0"
                                style={{ background: '#fff', maxWidth: '140px', fontWeight: 'bold' }}
                                value={customDate}
                                onChange={(e) => setCustomDate(e.target.value)}
                            />
                            <div style={{ fontWeight: 'bold', color: '#555' }}>
                                {editMode ? '✏️ Editando' : '📄 Venta'} # {String(nextId).padStart(6, '0')}
                            </div>
                        </div>

                        {/* Client Selector (Compact) */}
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                            <div style={{ fontWeight: 'bold', fontSize: '0.75rem', color: '#999' }}>👤 CLIENTE</div>
                            {!selectedClient && (
                                <button className="btn btn-sm btn-outline-primary" style={{ fontSize: '0.7rem', padding: '2px 8px', borderRadius: '15px', border: '1px solid #0d6efd' }} onClick={() => promptCreateClient('')}>+ NUEVO</button>
                            )}
                        </div>
                        <div style={{ position: 'relative' }}>
                            {selectedClient ? (
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: '#fff', padding: '8px 12px', borderRadius: '8px', border: '1px solid #ddd' }}>
                                    <div>
                                        <div style={{ fontSize: '0.75rem', color: '#999' }}>CLIENTE</div>
                                        <div style={{ fontWeight: 'bold' }}>{selectedClient.name}</div>
                                    </div>
                                    <button className="btn btn-sm btn-outline-danger border-0" onClick={() => setSelectedClient(null)}>✕</button>
                                </div>
                            ) : (
                                <input
                                    className="form-control form-control-sm bg-white border-secondary"
                                    placeholder="👤 Cliente (Opcional)..."
                                    value={clientQuery}
                                    onChange={(e) => setClientQuery(e.target.value)}
                                    style={{ borderRadius: '8px' }}
                                />
                            )}

                            {/* Client Dropdown Logic */}
                            {clientQuery.length > 2 && (
                                <div style={{ position: 'absolute', bottom: '100%', width: '100%', background: 'white', color: 'black', borderRadius: '8px', zIndex: 200, marginBottom: '2px', boxShadow: '0 -4px 6px rgba(0,0,0,0.2)', maxHeight: '200px', overflowY: 'auto' }}>
                                    {clients.map(c => (
                                        <div key={c.id} onClick={() => selectClient(c)} style={{ padding: '8px', cursor: 'pointer', borderBottom: '1px solid #eee' }}>{c.name}</div>
                                    ))}
                                    <div
                                        onClick={() => promptCreateClient(clientQuery)}
                                        style={{ padding: '10px', cursor: 'pointer', background: '#e3f2fd', color: '#0d6efd', fontWeight: 'bold', fontSize: '0.9rem', borderBottom: 'none' }}
                                    >
                                        + Crear "{clientQuery}"
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Footer: Totals & Payment */}
                    <div style={{ padding: '15px', background: 'white', borderTop: '1px solid #eee' }}>

                        {/* Totals Section */}
                        <div style={{ marginBottom: '15px', paddingBottom: '15px', borderBottom: '1px dashed #eee' }}>
                            {discount > 0 && (
                                <div style={{ display: 'flex', justifyContent: 'space-between', color: '#666', marginBottom: '5px' }}>
                                    <span>Subtotal</span>
                                    <span>{formatMoney(subTotal)}</span>
                                </div>
                            )}
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <span style={{ fontWeight: 'bold', fontSize: '1.2rem' }}>TOTAL</span>
                                <span style={{ fontWeight: 'bold', fontSize: '1.6rem', color: '#2e7d32' }}>{formatMoney(totalTotal)}</span>
                            </div>
                        </div>

                        {/* Split Payment Toggle */}
                        {paymentStatus !== 'credit' && (
                            <div style={{ marginBottom: '10px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                                <label className="form-check-label" style={{ fontSize: '0.9rem', fontWeight: 'bold' }}>
                                    <input
                                        type="checkbox"
                                        className="form-check-input md-2"
                                        checked={isSplitPayment}
                                        onChange={e => {
                                            const checked = e.target.checked;
                                            setIsSplitPayment(checked);
                                            if (checked) {
                                                // Auto-add default row with full amount
                                                const needed = paymentStatus === 'partial' ? 0 : totalTotal;
                                                setPayments([{ method: 'cash', amount: needed }]);
                                            } else {
                                                setPayments([]); // Explicitly clear when untoggling
                                            }
                                        }}
                                        style={{ marginRight: '5px' }}
                                    />
                                    Pago Mixto / Múltiple
                                </label>
                            </div>
                        )}

                        {/* Payment Methods */}
                        {!isSplitPayment ? (
                            /* SINGLE PAYMENT MODE */
                            <div style={{
                                display: 'grid',
                                gridTemplateColumns: paymentStatus === 'credit' ? '1fr' : '1fr 1fr',
                                gap: '8px',
                                marginBottom: '8px'
                            }}>
                                <div>
                                    <label style={{ fontSize: '0.75rem', fontWeight: 'bold', color: '#666', marginBottom: '5px', display: 'block' }}>ESTADO</label>
                                    <select
                                        className="form-control"
                                        value={paymentStatus}
                                        onChange={e => {
                                            setPaymentStatus(e.target.value);
                                            setIsSplitPayment(false); // Reset split if status changes
                                            setPayments([]); // Clear payments list
                                        }}
                                        style={{ height: '35px', borderRadius: '8px', border: '1px solid #ced4da', fontWeight: '500', fontSize: '0.9rem', padding: '0 5px' }}
                                    >
                                        <option value="paid">✅ Pagado</option>
                                        <option value="credit">⏳ Crédito</option>
                                        <option value="partial">⚖️ Abono</option>
                                    </select>
                                </div>

                                {paymentStatus !== 'credit' && (
                                    <div>
                                        <label style={{ fontSize: '0.75rem', fontWeight: 'bold', color: '#666', marginBottom: '5px', display: 'block' }}>MÉTODO</label>
                                        <select
                                            className="form-control"
                                            value={paymentMethod}
                                            onChange={e => setPaymentMethod(e.target.value)}
                                            style={{ height: '35px', borderRadius: '8px', border: '1px solid #ced4da', fontWeight: '500', fontSize: '0.9rem', padding: '0 5px' }}
                                        >
                                            <option value="cash">💵 Efectivo</option>
                                            <option value="nequi">📱 Nequi</option>
                                            <option value="bancolombia">🏦 Bancolombia</option>
                                        </select>
                                    </div>
                                )}
                            </div>
                        ) : (
                            /* SPLIT PAYMENT MODE */
                            <div style={{ background: '#f8f9fa', padding: '10px', borderRadius: '10px', marginBottom: '15px', border: '1px border #eee' }}>
                                <div style={{ marginBottom: '10px', fontWeight: 'bold', fontSize: '0.9rem' }}>Desglose de Pagos:</div>
                                {payments.map((p, idx) => (
                                    <div key={idx} style={{ display: 'flex', gap: '5px', marginBottom: '5px' }}>
                                        <select
                                            className="form-control form-control-sm"
                                            value={p.method}
                                            onChange={e => {
                                                const newPayments = [...payments];
                                                newPayments[idx].method = e.target.value;
                                                setPayments(newPayments);
                                            }}
                                            style={{ flex: 1 }}
                                        >
                                            <option value="cash">💵 Efectivo</option>
                                            <option value="nequi">📱 Nequi</option>
                                            <option value="bancolombia">🏦 Bancolombia</option>
                                        </select>
                                        <input
                                            type="number"
                                            className="form-control form-control-sm"
                                            placeholder="Monto"
                                            value={p.amount}
                                            onChange={e => {
                                                const newPayments = [...payments];
                                                newPayments[idx].amount = parseFloat(e.target.value);
                                                setPayments(newPayments);
                                            }}
                                            style={{ width: '100px' }}
                                        />
                                        <button className="btn btn-sm btn-outline-danger" onClick={() => {
                                            const newPayments = payments.filter((_, i) => i !== idx);
                                            setPayments(newPayments);
                                        }}>×</button>
                                    </div>
                                ))}
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '5px' }}>
                                    <button className="btn btn-sm btn-link" onClick={() => {
                                        const currentSum = payments.reduce((sum, p) => sum + (p.amount || 0), 0);
                                        const remaining = Math.max(0, totalTotal - currentSum);
                                        setPayments([...payments, { method: 'cash', amount: remaining }]);
                                    }}>+ Agregar Pago</button>

                                    <span style={{ fontSize: '0.9rem', fontWeight: 'bold', color: payments.reduce((sum, p) => sum + (p.amount || 0), 0) >= totalTotal ? 'green' : 'orange' }}>
                                        Total: {formatMoney(payments.reduce((sum, p) => sum + (p.amount || 0), 0))}
                                    </span>
                                </div>
                            </div>
                        )}

                        {/* Dynamic Inputs based on payment status/method */}
                        <div style={{ marginBottom: '15px' }}>
                            {/* Discount Toggle/Input */}
                            <div style={{ marginBottom: '5px' }}>
                                <input
                                    type="number"
                                    className="form-control form-control-sm"
                                    placeholder="Descuento (Opc)"
                                    value={discount}
                                    onChange={e => setDiscount(e.target.value)}
                                    onWheel={(e) => e.target.blur()}
                                    style={{ borderRadius: '8px', height: '30px' }}
                                />
                            </div>

                            {/* SINGLE MODE INPUTS */}
                            {!isSplitPayment && paymentStatus === 'paid' && paymentMethod === 'cash' && (
                                <div style={{ background: '#f8f9fa', padding: '8px', borderRadius: '8px', border: '1px dashed #ced4da' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '10px' }}>
                                        <div style={{ flex: 1 }}>
                                            <label style={{ fontSize: '0.65rem', fontWeight: 'bold', color: '#555', marginBottom: '2px', display: 'block' }}>RECIBIDO</label>
                                            <input
                                                type="number"
                                                className="form-control"
                                                placeholder="$ 0"
                                                value={receivedAmount}
                                                onChange={e => setReceivedAmount(e.target.value)}
                                                style={{
                                                    fontWeight: 'bold', fontSize: '1.2rem', color: '#2e7d32',
                                                    borderRadius: '6px', border: '1px solid #2e7d32', height: '35px'
                                                }}
                                            />
                                        </div>
                                        <div style={{ flex: 1, textAlign: 'right' }}>
                                            <label style={{ fontSize: '0.65rem', fontWeight: 'bold', color: '#555', marginBottom: '2px', display: 'block' }}>CAMBIO</label>
                                            <div style={{ fontSize: '1.2rem', fontWeight: 'bold', color: ((parseFloat(receivedAmount) || 0) - totalTotal) >= 0 ? '#1976d2' : '#d32f2f', height: '35px', display: 'flex', alignItems: 'center', justifyContent: 'flex-end' }}>
                                                {formatMoney(Math.max(0, (parseFloat(receivedAmount) || 0) - totalTotal))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {!isSplitPayment && paymentStatus === 'partial' && (
                                <div style={{ marginBottom: '10px' }}>
                                    <label style={{ fontSize: '0.8rem', fontWeight: 'bold', color: '#555', marginBottom: '5px', display: 'block' }}>MONTO DEL ABONO:</label>
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
                        </div>

                        <button
                            className="btn btn-success w-100 py-3 fw-bold"
                            style={{ borderRadius: '12px', fontSize: '1.2rem', boxShadow: '0 4px 6px rgba(46, 125, 50, 0.2)' }}
                            onClick={handleFinishSale}
                            disabled={cart.length === 0 || loading}
                        >
                            {loading ? 'Procesando...' : '✓ COBRAR'}
                        </button>
                    </div>
                </div>
            </div>

            {/* Mobile CSS override */}
            <style>{`
                @media (max-width: 991px) {
                    .desktop-grid {
                        display: flex !important;
                        flex-direction: column;
                    }
                    /* Reverse order on mobile? No, search first is better */
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
                        <div style={{ padding: '20px', background: '#f8f9fa', borderBottom: '1px solid #e9ecef', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h4 style={{ margin: 0, fontWeight: '700', color: '#333' }}>🧾 Confirmar Venta</h4>
                            <button onClick={() => setShowConfirmation(false)} style={{ border: 'none', background: 'none', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                        </div>

                        {/* Modal Body */}
                        <div style={{ padding: '25px', overflowY: 'auto' }}>

                            {/* Summary Cards */}
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px', marginBottom: '20px' }}>
                                <div style={{ background: '#e3f2fd', padding: '15px', borderRadius: '10px' }}>
                                    <small className="text-muted d-block uppercase">CLIENTE</small>
                                    <div style={{ fontWeight: 'bold', fontSize: '1.1rem' }}>
                                        {selectedClient ? selectedClient.name : 'Cliente Genérico (Mostrador)'}
                                    </div>
                                </div>
                                <div style={{ background: '#fff3e0', padding: '15px', borderRadius: '10px' }}>
                                    <small className="text-muted d-block uppercase">FECHA</small>
                                    <div style={{ fontWeight: 'bold', fontSize: '1.1rem' }}>{customDate}</div>
                                </div>
                                <div style={{ background: '#e8f5e9', padding: '15px', borderRadius: '10px' }}>
                                    <small className="text-muted d-block uppercase">MÉTODO PAGO</small>
                                    <div style={{ fontWeight: 'bold', fontSize: '1.1rem' }}>
                                        {isSplitPayment ? (
                                            <div>
                                                Mixto
                                                <div style={{ fontSize: '0.8rem', fontWeight: 'normal', marginTop: '5px' }}>
                                                    {payments.map((p, i) => (
                                                        <div key={i}>
                                                            {p.method === 'cash' ? 'Efectivo' : (p.method === 'nequi' ? 'Nequi' : (p.method === 'bancolombia' ? 'Bancolombia' : p.method))}: {formatMoney(p.amount)}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        ) : (
                                            <>
                                                {paymentStatus === 'paid' ? 'Contado' : (paymentStatus === 'credit' ? 'Crédito' : 'Parcial')}
                                                <span className="text-muted small"> ({paymentMethod === 'cash' ? 'Efectivo' : (paymentMethod === 'nequi' ? 'Nequi' : (paymentMethod === 'bancolombia' ? 'Bancolombia' : 'Otro'))})</span>
                                            </>
                                        )}
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
                                        <th className="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {cart.map((item, idx) => (
                                        <tr key={idx}>
                                            <td>
                                                {item.sku} - {item.name}
                                                <div><small className="text-muted">{item.displayUnit}</small></div>
                                            </td>
                                            <td className="text-center">{item.displayQuantity}</td>
                                            <td className="text-end fw-bold">{formatMoney(item.subtotal)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    {discount > 0 && (
                                        <tr>
                                            <td colSpan="2" className="text-end text-muted">Subtotal:</td>
                                            <td className="text-end">{formatMoney(subTotal)}</td>
                                        </tr>
                                    )}
                                    {discount > 0 && (
                                        <tr>
                                            <td colSpan="2" className="text-end text-danger">Descuento:</td>
                                            <td className="text-end text-danger">- {formatMoney(discount)}</td>
                                        </tr>
                                    )}
                                    <tr style={{ fontSize: '1.2rem', borderTop: '2px solid #333' }}>
                                        <td colSpan="2" className="text-end fw-bold">TOTAL:</td>
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
                                onClick={executeSale}
                                disabled={loading}
                                style={{ borderRadius: '10px', padding: '10px 30px', fontWeight: 'bold', display: 'flex', alignItems: 'center', gap: '10px' }}
                            >
                                {loading ? 'Procesando...' : (
                                    <><span>✓</span> Confirmar Venta</>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
