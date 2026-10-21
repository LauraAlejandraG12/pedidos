<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla pedidos (modelo de la pregunta 1.4: id, cliente_id, total, fecha)
 * más los campos que exige este módulo: estado, costo de envío y marcas de
 * tiempo para auditar cuándo se despachó y se entregó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->decimal('total', 12, 2);
            $table->date('fecha');

            $table->enum('estado', ['pendiente', 'despachado', 'entregado'])->default('pendiente');
            $table->decimal('costo_envio', 12, 2)->nullable();
            $table->timestamp('despachado_at')->nullable();
            $table->timestamp('entregado_at')->nullable();
            $table->text('observaciones')->nullable(); // visible solo para el admin

            $table->timestamps();

            $table->index('estado');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
