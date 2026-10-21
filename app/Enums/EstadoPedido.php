<?php

namespace App\Enums;

/**
 * Estados posibles de un pedido, tal como los define el enunciado.
 */
enum EstadoPedido: string
{
    case Pendiente = 'pendiente';
    case Despachado = 'despachado';
    case Entregado = 'entregado';

    /**
     * Valores válidos para validar peticiones (usado en formularios y API).
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Despachado => 'Despachado',
            self::Entregado => 'Entregado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'ambar',
            self::Despachado => 'azul',
            self::Entregado => 'verde',
        };
    }

    /**
     * Índice de la secuencia del pedido (para validar transiciones).
     */
    public function orden(): int
    {
        return match ($this) {
            self::Pendiente => 0,
            self::Despachado => 1,
            self::Entregado => 2,
        };
    }
}
