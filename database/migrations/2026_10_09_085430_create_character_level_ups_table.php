
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el historial de subida de nivel.
     */
    public function up(): void
    {
        Schema::create('character_level_ups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('character_id')
                ->constrained('characters')
                ->cascadeOnDelete();

            $table->foreignId('character_class_id')
                ->constrained('character_classes')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('class_level');
            $table->unsignedTinyInteger('hit_die');

            $table->enum('hp_method', ['average', 'manual']);

            $table->unsignedSmallInteger('base_hp_gained');
            $table->unsignedSmallInteger('constitution_modifier_applied')
                ->default(0);
            $table->unsignedSmallInteger('total_hp_gained');

            $table->timestamps();

            $table->unique(
                ['character_class_id', 'class_level'],
                'character_level_ups_class_level_unique'
            );
        });
    }

    /**
     * Elimina la tabla si se revierte esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_level_ups');
    }
};
