@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <div class="tarjeta" style="max-width: 460px; margin: 40px auto;">
        <div class="tarjeta-cabecera">
            <h2>Iniciar sesión</h2>
        </div>

        <form class="formulario" method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="campo">
                <label for="email">Correo</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
                <span class="ayuda">Datos de prueba: <code>admin@uva3.test</code> /
                    <code>operador@uva3.test</code> (contraseña: <code>password</code>)</span>
            </div>

            <div class="acciones-formulario">
                <button class="btn">Entrar</button>
            </div>
        </form>
    </div>
@endsection
