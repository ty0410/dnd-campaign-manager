
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las competencias procedentes de distintas fuentes.
     */
    public function up(): void
    {
        Schema::create('character_proficiencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('character_id')
                ->constrained('characters')
                ->cascadeOnDelete();

            $table->enum('category', [
                'skill',
                'saving_throw',
                'weapon',
                'armor',
                'tool',
                'language',
            ]);

            $table->string('proficiency_index');
            $table->string('proficiency_name');

            $table->string('source_type')->nullable();
            $table->string('source_index')->nullable();

            // 0 = sin competencia, 1 = competente, 2 = experto
            $table->unsignedTinyInteger('proficiency_level')->default(1);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(
                ['character_id', 'category'],
                'character_proficiencies_character_category_index'
            );

            $table->index(
                ['character_id', 'proficiency_index'],
                'character_proficiencies_character_index_index'
            );
        });
    }

    /**
     * Elimina la tabla al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_proficiencies');
    }
};
