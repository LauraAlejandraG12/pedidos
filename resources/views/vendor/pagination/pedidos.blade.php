{{-- Paginación con el CSS propio del proyecto (sin Tailwind ni Bootstrap). --}}
@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Paginación de pedidos">

        @if ($paginator->onFirstPage())
            <span class="disabled">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="activo">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
        @else
            <span class="disabled">Siguiente</span>
        @endif
    </nav>
@endif
