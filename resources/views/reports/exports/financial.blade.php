<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #8B0000; color: #ffffff; }
        .title { font-size: 18px; font-weight: bold; color: #8B0000; text-align: center; }
        .meta { margin-bottom: 20px; }
        .expense { color: #dc3545; }
        .profit { font-weight: bold; }
        .positive { color: green; }
        .negative { color: red; }
    </style>

</head>
<body>
    <div class="title">{{ \App\Models\Setting::getBusinessName() }} - Estado de Resultados</div>
    <div class="meta">
        <strong>Generado:</strong> {{ date('d/m/Y H:i') }}<br>
        <strong>Desde:</strong> {{ $startDate }}<br>
        <strong>Hasta:</strong> {{ $endDate }}
    </div>

    <table style="width: 100%; border-collapse: collapse; font-family: sans-serif;">
        <thead>
            <tr>
                <th style="background-color: #8B0000; color: white; padding: 8px; text-align: left; width: 40%;">Concepto</th>
                <th style="background-color: #8B0000; color: white; padding: 8px; text-align: right; width: 30%;">REAL <br><small style="font-weight: normal; font-size: 0.8em;">(Facturado)</small></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">(+) Ingresos por Ventas</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold;">${{ number_format($totalRealSales, 0) }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">(-) Costo de Ventas</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right; color: #dc3545;">- ${{ number_format($totalCost, 0) }}</td>
            </tr>
            
            <tr style="background-color: #f0f0f0;">
                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">(=) Utilidad Bruta</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold;">${{ number_format($realGrossProfit, 0) }}</td>
            </tr>

            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">(-) Gastos Operativos</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right; color: #dc3545;">- ${{ number_format($totalExpenses, 0) }}</td>
            </tr>

            <tr style="background-color: #e0e0e0;">
                <td style="border: 1px solid #ddd; padding: 12px 8px; font-weight: bold; font-size: 1.1em;">(=) UTILIDAD OPERACIONAL</td>
                <td style="border: 1px solid #ddd; padding: 12px 8px; text-align: right; font-weight: bold; font-size: 1.1em; color: {{ $realNetProfit >= 0 ? 'green' : 'red' }};">
                    ${{ number_format($realNetProfit, 0) }}
                </td>
            </tr>
        </tbody>
    </table>
    
    <div style="margin-top: 20px; font-size: 10px; color: #666; font-style: italic; text-align: center;">
    </div>
</body>
</html>
