<!DOCTYPE html>
<html>
<head>
    <title>Catálogo de Códigos de Barras - {{ \App\Models\Setting::getBusinessName() ?? 'SHEVERE' }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #8B0000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h2 {
            margin: 0;
            color: #8B0000;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        .catalog-container {
            width: 100%;
        }
        .product-card {
            width: 95%;
            margin: 0 auto 15px auto;
            border: 1px solid #e0e0e0;
            border-left: 8px solid #8B0000;
            background-color: #fff;
            padding: 15px;
            page-break-inside: avoid;
        }
        .product-info {
            display: inline-block;
            width: 60%;
            vertical-align: middle;
        }
        .product-name {
            font-size: 16px;
            font-weight: bold;
            color: #222;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .product-sku {
            font-family: monospace;
            font-size: 13px;
            color: #8B0000;
            background: #fff5f5;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .barcode-section {
            display: inline-block;
            width: 35%;
            text-align: right;
            vertical-align: middle;
        }
        .barcode-img {
            max-width: 100%;
            height: auto;
        }
        .footer {
            position: fixed;
            bottom: -0.5cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Catálogo de Códigos de Barras</h2>
        <p>{{ \App\Models\Setting::getBusinessName() ?? 'SHEVERE' }} | Fecha de Emisión: {{ date('d/m/Y h:i A') }}</p>
    </div>

    <div class="catalog-container">
        @foreach($products as $product)
        <div class="product-card">
            <div class="product-info">
                <div class="product-name">{{ $product->name }}</div>
                <div class="product-sku">REF: {{ $product->sku }}</div>
            </div>
            
            <div class="barcode-section">
                @php
                    try {
                        $code = (string) $product->sku;
                        // Use Code 128 for reliability
                        $barcode = $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, 45);
                        echo $barcode;
                    } catch (\Exception $e) {
                        echo '<span style="color:red; font-size:10px;">Error al generar código: ' . $product->sku . ' - ' . $e->getMessage() . '</span>';
                    }
                @endphp
            </div>
        </div>
        @endforeach
    </div>

    <div class="footer">
        Sistema de Inventario {{ \App\Models\Setting::getBusinessName() ?? 'SHEVERE' }} &copy; {{ date('Y') }} - Generado Automáticamente
    </div>
</body>
</html>
