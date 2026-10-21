<?php

namespace App\Http\Requests;

use App\Enums\EstadoPedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(EstadoPedido::valores())],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe indicar el nuevo estado del pedido.',
            'estado.in' => 'El estado debe ser pendiente, despachado o entregado.',
        ];
    }
}
