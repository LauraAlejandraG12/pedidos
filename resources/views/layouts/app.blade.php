<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Pedidos') · Módulo de pedidos</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<header class="topbar">
    <div class="contenedor topbar-inner">
        <a class="marca" href="{{ route('pedidos.index') }}">🚚 Pedidos UVA3</a>

        @auth
            <nav>
                <a href="{{ route('pedidos.index') }}">Pedidos</a>
                <a href="{{ route('reportes.ventas') }}">Reporte de ventas</a>
            </nav>

            <div class="sesion">
                <span class="usuario">
                    {{ auth()->user()->name }}
                    · {{ auth()->user()->esAdmin() ? 'Administrador' : 'Operador' }}
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-secundario btn-chico">Salir</button>
                </form>
            </div>
        @endauth
    </div>
</header>

<main class="contenedor">
    @if (session('exito'))
        <div class="alerta exito">{{ session('exito') }}</div>
    @endif

    @if ($errors->any())
        <div class="alerta error">{{ $errors->first() }}</div>
    @endif

    @yield('contenido')
</main>

<footer class="pie">
    Módulo de pedidos · UVA3 · Laravel {{ app()->version() }} corriendo en Docker
</footer>

</body>
</html>
