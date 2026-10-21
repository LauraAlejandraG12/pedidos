@extends('layouts.app')

@section('titulo', 'Listado de pedidos')

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>Pedidos</h1>
            <p class="subtitulo">{{ $pedidos->total() }} pedido(s) en la página actual</p>
        </div>

        <a class="btn" href="{{ route('pedidos.create') }}">+ Nuevo pedido</a>
    </div>

    {{-- Filtro por estado --}}
    <div class="filtros">
        <a class="filtro {{ $estadoSeleccionado === null ? 'activo' : '' }}" href="{{ route('pedidos.index') }}">
            Todos <span class="cuenta">{{ $resumen['pendiente'] + ($resumen['despachado'] ?? 0) + ($resumen['entregado'] ?? 0) }}</span>
        </a>

        @foreach ($estados as $estado)
            <a class="filtro {{ $estadoSeleccionado === $estado->value ? 'activo' : '' }}"
               href="{{ route('pedidos.index', ['estado' => $estado->value]) }}">
                {{ $estado->etiqueta() }} <span class="cuenta">{{ $resumen[$estado->value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="tarjeta">
        @if ($pedidos->isEmpty())
            <p class="vacio">No hay pedidos {{ $estadoSeleccionado ? 'con estado "'.$estadoSeleccionado.'"' : '' }}.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Ciudad</th>
                    <th>Fecha</th>
                    <th class="num">Total</th>
                    <th class="num">Envío</th>
                    <th>Estado</th>
                    <th style="width: 260px;">Acciones</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($pedidos as $pedido)
                    <tr>
                        <td>{{ $pedido->id }}</td>
                        <td>{{ $pedido->cliente->nombre }}</td>
                        <td>{{ $pedido->cliente->ciudad }}</td>
                        <td>{{ $pedido->fecha->format('d/m/Y') }}</td>
                        <td class="num">${{ number_format($pedido->total, 0, ',', '.') }}</td>
                        <td class="num">
                            {{ $pedido->costo_envio !== null ? '$'.number_format($pedido->costo_envio, 0, ',', '.') : '—' }}
                        </td>
                        <td>
                            <span class="badge {{ $pedido->estado->color() }}">{{ $pedido->estado->etiqueta() }}</span>
                        </td>
                        <td>
                            @can('update', $pedido)
                                <form class="form-estado" method="POST" action="{{ route('pedidos.estado', $pedido) }}">
                                    @csrf
                                    <select name="estado" aria-label="Nuevo estado del pedido {{ $pedido->id }}">
                                        @foreach ($estados as $estado)
                                            <option value="{{ $estado->value }}" @selected($pedido->estado === $estado)>
                                                {{ $estado->etiqueta() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-chico">Cambiar</button>
                                </form>

                                <a class="btn btn-secundario btn-chico" href="{{ route('pedidos.edit', $pedido) }}">Editar</a>
                            @else
                                <span class="subtitulo">Entregado · solo admin corrige</span>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            {{ $pedidos->links() }}
        @endif
    </div>
@endsection
