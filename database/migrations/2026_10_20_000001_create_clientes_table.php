<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla clientes (según el modelo de la pregunta 1.4: id, nombre, ciudad).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('ciudad', 100);
            $table->timestamps();

            $table->index('ciudad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
