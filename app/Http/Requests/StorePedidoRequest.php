<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePedidoRequest extends FormRequest
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
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')],
            'total' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'total.min' => 'El total debe ser mayor a cero.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
        ];
    }
}
