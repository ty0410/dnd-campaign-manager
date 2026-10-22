
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade los datos necesarios para las clases múltiples.
     */
    public function up(): void
    {
        Schema::table('character_classes', function (Blueprint $table) {
            $table->string('class_index')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedTinyInteger('hit_die')->nullable();
        });
    }

    /**
     * Revierte únicamente los campos añadidos.
     */
    public function down(): void
    {
        Schema::table('character_classes', function (Blueprint $table) {
            $table->dropColumn([
                'class_index',
                'is_primary',
                'hit_die',
            ]);
        });
    }
};
