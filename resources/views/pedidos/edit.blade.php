@extends('layouts.app')

@section('titulo', "Editar pedido #{$pedido->id}")

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>Pedido #{{ $pedido->id }}</h1>
            <p class="subtitulo">
                {{ $pedido->cliente->nombre }} · {{ $pedido->cliente->ciudad }} ·
                estado actual: <span class="badge {{ $pedido->estado->color() }}">{{ $pedido->estado->etiqueta() }}</span>
            </p>
        </div>
        <a class="btn btn-secundario" href="{{ route('pedidos.index') }}">Volver al listado</a>
    </div>

    @if ($pedido->estaEntregado())
        <div class="alerta aviso">
            Este pedido fue entregado el {{ $pedido->entregado_at?->format('d/m/Y H:i') }}.
            Los pedidos entregados no se pueden modificar.
            @if (auth()->user()->esAdmin())
                Como eres <strong>administrador</strong>, puedes corregirlo igualmente.
            @endif
        </div>
    @endif

    @if ($pedido->costo_envio !== null)
        <div class="alerta aviso">
            Costo de envío cotizado con la transportadora:
            <strong>${{ number_format($pedido->costo_envio, 0, ',', '.') }}</strong>
            (cotizado el {{ $pedido->despachado_at?->format('d/m/Y H:i') }})
        </div>
    @endif

    <div class="tarjeta">
        <form class="formulario" method="POST" action="{{ route('pedidos.update', $pedido) }}">
            @csrf
            @method('PUT')

            <div class="campo">
                <label for="cliente_id">Cliente</label>
                <select id="cliente_id" name="cliente_id" required>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected($pedido->cliente_id === $cliente->id)>
                            {{ $cliente->nombre }} ({{ $cliente->ciudad }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="total">Total (COP)</label>
                <input type="number" id="total" name="total" step="0.01" min="0.01"
                       value="{{ old('total', $pedido->total) }}" required>
                @error('total') <span class="ayuda" style="color: var(--rojo)">{{ $message }}</span> @enderror
            </div>

            <div class="campo">
                <label for="fecha">Fecha</label>
                <input type="date" id="fecha" name="fecha"
                       value="{{ old('fecha', $pedido->fecha->toDateString()) }}" required>
                @error('fecha') <span class="ayuda" style="color: var(--rojo)">{{ $message }}</span> @enderror
            </div>

            @if (auth()->user()->esAdmin())
                <div class="campo">
                    <label for="observaciones">Observaciones (solo las ve el administrador)</label>
                    <textarea id="observaciones" name="observaciones" rows="3">{{ old('observaciones', $pedido->observaciones) }}</textarea>
                </div>
            @endif

            <div class="acciones-formulario">
                <button class="btn">Guardar cambios</button>
                <a class="btn btn-secundario" href="{{ route('pedidos.index') }}">Cancelar</a>
            </div>
        </form>
    </div>

    @can('update', $pedido)
        <div class="tarjeta">
            <div class="tarjeta-cabecera">
                <h2>Cambiar estado</h2>
            </div>
            <form class="formulario" method="POST" action="{{ route('pedidos.estado', $pedido) }}">
                @csrf

                <div class="campo">
                    <label for="estado">Nuevo estado</label>
                    <select id="estado" name="estado" required>
                        @foreach (\App\Enums\EstadoPedido::cases() as $estado)
                            <option value="{{ $estado->value }}" @selected($pedido->estado === $estado)>
                                {{ $estado->etiqueta() }}
                            </option>
                        @endforeach
                    </select>
                    <span class="ayuda">
                        Al marcar <strong>despachado</strong> el sistema cotiza el envío con la transportadora
                        y lo guarda en el pedido. Al regresar a <strong>pendiente</strong> se limpia ese costo.
                    </span>
                </div>

                <div class="acciones-formulario">
                    <button class="btn">Aplicar cambio</button>
                </div>
            </form>
        </div>
    @endcan
@endsection
