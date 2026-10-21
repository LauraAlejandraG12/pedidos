@extends('layouts.app')

@section('titulo', 'Reporte de ventas')

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>Total vendido por cliente</h1>
            <p class="subtitulo">
                Mes actual ({{ \Illuminate\Support\Carbon::parse($desde)->locale('es')->isoFormat('MMMM YYYY') }}):
                del {{ \Illuminate\Support\Carbon::parse($desde)->locale('es')->isoFormat('D MMM') }}
                al {{ \Illuminate\Support\Carbon::parse($hasta)->locale('es')->isoFormat('D MMM YYYY') }}
            </p>
        </div>
        <div style="text-align: right">
            <div class="subtitulo">Total general</div>
            <div class="total-general">${{ number_format($total_general, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="tarjeta">
        @if (empty($filas))
            <p class="vacio">Todavía no hay ventas registradas en el mes actual.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Cliente</th>
                    <th>Ciudad</th>
                    <th class="num">Pedidos</th>
                    <th class="num">Total vendido</th>
                    <th class="num">% del total</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($filas as $fila)
                    <tr @class(['resaltado' => $fila['puesto'] === 1])>
                        <td>{{ $fila['puesto'] }}</td>
                        <td>{{ $fila['cliente'] }}</td>
                        <td>{{ $fila['ciudad'] }}</td>
                        <td class="num">{{ $fila['pedidos'] }}</td>
                        <td class="num"><strong>${{ number_format($fila['total_vendido'], 0, ',', '.') }}</strong></td>
                        <td class="num">
                            {{ $total_general > 0 ? number_format($fila['total_vendido'] / $total_general * 100, 1, ',', '.') : '0' }} %
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <p class="subtitulo">
        También disponible en JSON: <code>GET {{ url('/api/reporte/ventas') }}</code>
    </p>
@endsection
