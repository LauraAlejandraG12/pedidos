@extends('layouts.app')

@section('titulo', 'Nuevo pedido')

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>Nuevo pedido</h1>
            <p class="subtitulo">Todo pedido nace en estado <strong>pendiente</strong>.</p>
        </div>
    </div>

    <div class="tarjeta">
        <form class="formulario" method="POST" action="{{ route('pedidos.store') }}">
            @csrf

            <div class="campo">
                <label for="cliente_id">Cliente</label>
                <select id="cliente_id" name="cliente_id" required>
                    <option value="">Seleccione un cliente…</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>
                            {{ $cliente->nombre }} ({{ $cliente->ciudad }})
                        </option>
                    @endforeach
                </select>
                @error('cliente_id') <span class="ayuda" style="color: var(--rojo)">{{ $message }}</span> @enderror
            </div>

            <div class="campo">
                <label for="total">Total (COP)</label>
                <input type="number" id="total" name="total" step="0.01" min="0.01"
                       value="{{ old('total') }}" required>
                @error('total') <span class="ayuda" style="color: var(--rojo)">{{ $message }}</span> @enderror
            </div>

            <div class="campo">
                <label for="fecha">Fecha</label>
                <input type="date" id="fecha" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" required>
                @error('fecha') <span class="ayuda" style="color: var(--rojo)">{{ $message }}</span> @enderror
            </div>

            <div class="acciones-formulario">
                <button class="btn">Guardar pedido</button>
                <a class="btn btn-secundario" href="{{ route('pedidos.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
