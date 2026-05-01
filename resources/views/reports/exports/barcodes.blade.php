<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Códigos de Barras - {{ \App\Models\Setting::getBusinessName() ?? 'SHEVERE' }}</title>
    <style>
        @page {
            margin: 0;
            /* 80mm width = ~226.77pt. DomPDF uses the controller setPaper instead of this usually, but good to have */
        }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 10px;
            width: 76mm; /* standard print width for 80mm paper */
            background-color: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            color: #000;
            text-transform: uppercase;
            font-size: 16px;
            font-weight: bold;
        }
        .header p {
            margin: 0;
            color: #000;
            font-size: 11px;
        }
        .catalog-container {
            width: 100%;
        }
        .product-card {
            width: 100%;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #000;
            text-align: center;
            page-break-inside: avoid;
        }
        .product-name {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .product-sku {
            font-size: 12px;
            color: #000;
            margin-bottom: 10px;
        }
        .barcode-section {
            display: block;
            text-align: center;
            margin: 0 auto;
        }
        .barcode-section > div {
            margin: 0 auto !important; /* Ensure the HTML barcode centers */
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #000;
            margin-top: 10px;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Códigos de Barras</h2>
        <p>{{ \App\Models\Setting::getBusinessName() ?? 'SHEVERE' }}</p>
        <p>Generado: {{ date('d/m/y h:i A') }}</p>
    </div>

    <div class="catalog-container">
        @foreach($products as $product)
        <div class="product-card">
            <div class="product-name">{{ $product->name }}</div>
            <div class="product-sku">SKU/REF: {{ $product->sku }}</div>
            
            <div class="barcode-section">
                @php
                    try {
                        $code = (string) $product->sku;
                        // For HTML generator, we can use slightly larger factors since we are centering
                        // widthFactor=2, height=40
                        $barcode = $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, 40);
                        echo $barcode;
                    } catch (\Exception $e) {
                        echo '<span style="color:red; font-size:10px;">Error: ' . $e->getMessage() . '</span>';
                    }
                @endphp
            </div>
        </div>
        @endforeach
    </div>

    <div class="footer">
        Fin del catálogo
    </div>
</body>
</html>
