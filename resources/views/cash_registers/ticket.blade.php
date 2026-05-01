<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Cierre de Caja #{{ $register->id }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            width: 76mm;
            margin: 0;
            padding: 5mm;
            font-size: 11.5px;
            font-weight: 700;
            color: #000;
            line-height: 1.2;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: 700;
        }

        .border-top {
            border-top: 1.5px solid #000;
            margin-top: 5px;
            padding-top: 5px;
        }

        .border-bottom {
            border-bottom: 1.5px solid #000;
            margin-bottom: 5px;
            padding-bottom: 5px;
        }

        .mb-5 {
            margin-bottom: 5px;
        }

        .mt-10 {
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .summary-row {
            margin: 3px 0;
        }
    </style>
</head>

<body onload="window.print()">
    <div class="text-center bold" style="font-size: 16px;">
        {{ \App\Models\Setting::first()->business_name ?? 'INV-SHEVERE' }}
    </div>
    <div class="text-center mb-5">
        CUADRE DE CAJA #{{ str_pad($register->id, 6, '0', STR_PAD_LEFT) }}
    </div>

    <div class="border-top border-bottom mb-5">
        <div class="row"><span>Cajero:</span> <span>{{ $register->user->name }}</span></div>
        <div class="row"><span>Apertura:</span> <span>{{ $register->opened_at->format('d/m/Y H:i') }}</span></div>
        <div class="row"><span>Cierre:</span> <span>{{ $register->closed_at->format('d/m/Y H:i') }}</span></div>
        <div class="row"><span>Estado:</span> <span>{{ strtoupper($register->status) }}</span></div>
    </div>

    <div class="bold text-center mb-5 mt-10">--- RESUMEN DE SALDOS ---</div>

    <div class="summary-row bold">EFECTIVO:</div>
    <div class="row"><span>Base Inicial:</span> <span>$ {{ number_format($register->initial_cash, 0, ',', '.') }}</span>
    </div>
    <div class="row"><span>Ventas/Ingresos:</span> <span>$
            {{ number_format($register->system_cash - $register->initial_cash, 0, ',', '.') }}</span></div>
    <div class="row border-top"><span>Total Sistema:</span> <span>$
            {{ number_format($register->system_cash, 0, ',', '.') }}</span></div>
    <div class="row"><span>Total Físico:</span> <span>$
            {{ number_format($register->physical_cash, 0, ',', '.') }}</span></div>
    <div class="row bold"><span>Diferencia:</span> <span>$
            {{ number_format($register->physical_cash - $register->system_cash, 0, ',', '.') }}</span></div>

    <div class="summary-row bold mt-10">NEQUI:</div>
    <div class="row"><span>Total Sistema:</span> <span>$
            {{ number_format($register->system_nequi, 0, ',', '.') }}</span></div>
    <div class="row"><span>Total Físico:</span> <span>$
            {{ number_format($register->physical_nequi, 0, ',', '.') }}</span></div>
    <div class="row bold"><span>Diferencia:</span> <span>$
            {{ number_format($register->physical_nequi - $register->system_nequi, 0, ',', '.') }}</span></div>

    <div class="summary-row bold mt-10">BANCOLOMBIA:</div>
    <div class="row"><span>Total Sistema:</span> <span>$
            {{ number_format($register->system_bancolombia, 0, ',', '.') }}</span></div>
    <div class="row"><span>Total Físico:</span> <span>$
            {{ number_format($register->physical_bancolombia, 0, ',', '.') }}</span></div>
    <div class="row bold"><span>Diferencia:</span> <span>$
            {{ number_format($register->physical_bancolombia - $register->system_bancolombia, 0, ',', '.') }}</span>
    </div>

    @if($adjustments->count() > 0)
        <div class="border-top mt-10">
            <div class="bold text-center mb-5">--- AJUSTES MANUALES ---</div>
            @foreach($adjustments as $adj)
                <div style="font-size: 10px; margin-bottom: 3px;">
                    <div class="row">
                        <span>{{ $adj->type == 'entry' ? '(+) ENTRADA' : '(-) SALIDA' }}
                            [{{ strtoupper($adj->payment_method) }}]</span>
                        <span class="bold">$ {{ number_format($adj->amount, 0, ',', '.') }}</span>
                    </div>
                    <div style="color: #444;">Motivo: {{ $adj->description ?? 'Sin descripción' }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if($register->notes)
        <div class="border-top mt-10">
            <div class="bold">Notas:</div>
            <div style="font-size: 10px;">{{ $register->notes }}</div>
        </div>
    @endif

    <div class="text-center mt-10" style="margin-top: 30px;">
        _______________________<br>
        Firma del Cajero
    </div>

    <div class="text-center mt-10" style="font-size: 10px;">
        Generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>

</html>