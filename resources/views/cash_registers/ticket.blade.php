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
            font-size: 11px;
            color: #000;
            line-height: 1.25;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .bold        { font-weight: 700; }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .solid {
            border-top: 1.5px solid #000;
            margin: 5px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }

        .row-indent {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
            padding-left: 6px;
            font-size: 10.5px;
        }

        .method-title {
            font-weight: 700;
            font-size: 11.5px;
            margin-top: 6px;
            margin-bottom: 2px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            border-top: 1px solid #000;
            margin-top: 2px;
            padding-top: 2px;
        }

        .diff-line {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }

        .adj-item {
            font-size: 10px;
            margin: 2px 0;
        }

        .muted { color: #555; }
    </style>
</head>

<body onload="window.print()">

    {{-- ENCABEZADO --}}
    <div class="text-center bold" style="font-size: 14px;">
        {{ \App\Models\Setting::first()->business_name ?? 'INV-SHEVERE' }}
    </div>
    <div class="text-center" style="font-size: 10px;">CUADRE DE CAJA #{{ str_pad($register->id, 6, '0', STR_PAD_LEFT) }}</div>

    <div class="solid"></div>

    <div class="row"><span>Cajero:</span>   <span class="bold">{{ $register->user->name }}</span></div>
    <div class="row"><span>Apertura:</span> <span>{{ $register->opened_at->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Cierre:</span>   <span>{{ $register->closed_at ? $register->closed_at->format('d/m/Y H:i') : '—' }}</span></div>
    <div class="row"><span>Estado:</span>   <span class="bold">{{ strtoupper($register->status) }}</span></div>

    <div class="solid"></div>
    <div class="text-center bold" style="font-size: 11px;">— RESUMEN DE SALDOS —</div>
    <div class="divider"></div>

    {{-- ===================== HELPER MACRO ===================== --}}
    @php
        function fmt($n) {
            return '$ ' . number_format($n, 0, ',', '.');
        }
    @endphp

    {{-- ===================== EFECTIVO ===================== --}}
    @php
        $b = $breakdown['cash'];
        $adjNet = $b['adj_entry'] - $b['adj_exit'];
        $total  = $b['initial'] + $b['income'] - $b['outgo'] + $adjNet;
    @endphp

    <div class="method-title">EFECTIVO</div>
    <div class="row-indent"><span>Base inicial</span>    <span>{{ fmt($b['initial']) }}</span></div>
    @if($b['income'] > 0)
    <div class="row-indent"><span>(+) Ingresos</span>   <span>{{ fmt($b['income']) }}</span></div>
    @endif
    @if($b['outgo'] > 0)
    <div class="row-indent"><span>(-) Egresos</span>    <span>{{ fmt($b['outgo']) }}</span></div>
    @endif
    @if($adjNet != 0)
    <div class="row-indent muted">
        <span>{{ $adjNet > 0 ? '(+)' : '(-)' }} Ajustes</span>
        <span>{{ fmt(abs($adjNet)) }}</span>
    </div>
    @endif
    <div class="total-line"><span>Total Sistema</span>  <span>{{ fmt($total) }}</span></div>
    <div class="diff-line"><span>Total Físico</span>    <span>{{ fmt($register->physical_cash) }}</span></div>
    @php $diff = $register->physical_cash - $total; @endphp
    <div class="diff-line bold"><span>Diferencia</span> <span>{{ $diff >= 0 ? '+' : '' }}{{ fmt($diff) }}</span></div>

    <div class="divider"></div>

    {{-- ===================== NEQUI ===================== --}}
    @php
        $b = $breakdown['nequi'];
        $adjNet = $b['adj_entry'] - $b['adj_exit'];
        $total  = $b['initial'] + $b['income'] - $b['outgo'] + $adjNet;
    @endphp

    <div class="method-title">NEQUI</div>
    <div class="row-indent"><span>Base inicial</span>    <span>{{ fmt($b['initial']) }}</span></div>
    @if($b['income'] > 0)
    <div class="row-indent"><span>(+) Ingresos</span>   <span>{{ fmt($b['income']) }}</span></div>
    @endif
    @if($b['outgo'] > 0)
    <div class="row-indent"><span>(-) Egresos</span>    <span>{{ fmt($b['outgo']) }}</span></div>
    @endif
    @if($adjNet != 0)
    <div class="row-indent muted">
        <span>{{ $adjNet > 0 ? '(+)' : '(-)' }} Ajustes</span>
        <span>{{ fmt(abs($adjNet)) }}</span>
    </div>
    @endif
    <div class="total-line"><span>Total Sistema</span>  <span>{{ fmt($total) }}</span></div>
    <div class="diff-line"><span>Total Físico</span>    <span>{{ fmt($register->physical_nequi) }}</span></div>
    @php $diff = $register->physical_nequi - $total; @endphp
    <div class="diff-line bold"><span>Diferencia</span> <span>{{ $diff >= 0 ? '+' : '' }}{{ fmt($diff) }}</span></div>

    <div class="divider"></div>

    {{-- ===================== BANCOLOMBIA ===================== --}}
    @php
        $b = $breakdown['bancolombia'];
        $adjNet = $b['adj_entry'] - $b['adj_exit'];
        $total  = $b['initial'] + $b['income'] - $b['outgo'] + $adjNet;
    @endphp

    <div class="method-title">BANCOLOMBIA</div>
    <div class="row-indent"><span>Base inicial</span>    <span>{{ fmt($b['initial']) }}</span></div>
    @if($b['income'] > 0)
    <div class="row-indent"><span>(+) Ingresos</span>   <span>{{ fmt($b['income']) }}</span></div>
    @endif
    @if($b['outgo'] > 0)
    <div class="row-indent"><span>(-) Egresos</span>    <span>{{ fmt($b['outgo']) }}</span></div>
    @endif
    @if($adjNet != 0)
    <div class="row-indent muted">
        <span>{{ $adjNet > 0 ? '(+)' : '(-)' }} Ajustes</span>
        <span>{{ fmt(abs($adjNet)) }}</span>
    </div>
    @endif
    <div class="total-line"><span>Total Sistema</span>  <span>{{ fmt($total) }}</span></div>
    <div class="diff-line"><span>Total Físico</span>    <span>{{ fmt($register->physical_bancolombia) }}</span></div>
    @php $diff = $register->physical_bancolombia - $total; @endphp
    <div class="diff-line bold"><span>Diferencia</span> <span>{{ $diff >= 0 ? '+' : '' }}{{ fmt($diff) }}</span></div>

    {{-- ===================== AJUSTES MANUALES ===================== --}}
    @if($adjustments->count() > 0)
        <div class="solid"></div>
        <div class="text-center bold" style="font-size: 11px;">— AJUSTES MANUALES —</div>
        <div class="divider"></div>
        @foreach($adjustments as $adj)
            <div class="adj-item">
                <div class="row">
                    <span>{{ $adj->type == 'entry' ? '(+)' : '(-)' }} {{ strtoupper($adj->payment_method) }}</span>
                    <span class="bold">{{ fmt($adj->amount) }}</span>
                </div>
                <div class="muted" style="font-size: 9.5px; padding-left: 4px;">
                    {{ $adj->description ?? 'Sin descripción' }}
                </div>
            </div>
        @endforeach
    @endif

    {{-- ===================== NOTAS ===================== --}}
    @if($register->notes)
        <div class="solid"></div>
        <div class="bold" style="font-size: 10px;">Notas:</div>
        <div style="font-size: 10px;">{{ $register->notes }}</div>
    @endif

    <div class="solid"></div>

    <div class="text-center" style="margin-top: 20px;">
        _______________________<br>
        <span style="font-size: 10px;">Firma del Cajero</span>
    </div>

    <div class="text-center muted" style="font-size: 9px; margin-top: 8px;">
        Generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>