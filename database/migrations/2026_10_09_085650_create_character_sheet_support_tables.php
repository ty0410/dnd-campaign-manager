
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cambios manuales de competencias
        Schema::create('character_proficiency_overrides', function (Blueprint $table) {
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

            // 0 = sin competencia, 1 = competente, 2 = pericia
            $table->unsignedTinyInteger('proficiency_level')->default(1);

            $table->timestamps();

            $table->unique(
                ['character_id', 'category', 'proficiency_index'],
                'character_proficiency_overrides_unique'
            );
        });

        // Inventario
        Schema::create('character_inventory', function (Blueprint $table) {
            $table->id();

            $table->foreignId('character_id')
                ->constrained('characters')
                ->cascadeOnDelete();

            $table->string('equipment_index');
            $table->string('item_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('weight', 8, 2)->nullable();
            $table->boolean('is_equipped')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['character_id', 'equipment_index'],
                'character_inventory_character_equipment_index'
            );
        });

        // Monedas: una fila por personaje
        Schema::create('character_currency', function (Blueprint $table) {
            $table->id();

            $table->foreignId('character_id')
                ->unique()
                ->constrained('characters')
                ->cascadeOnDelete();

            $table->unsignedInteger('copper')->default(0);
            $table->unsignedInteger('silver')->default(0);
            $table->unsignedInteger('electrum')->default(0);
            $table->unsignedInteger('gold')->default(0);
            $table->unsignedInteger('platinum')->default(0);

            $table->timestamps();
        });

        // Conjuros del personaje
        Schema::create('character_spells', function (Blueprint $table) {
            $table->id();

            $table->foreignId('character_id')
                ->constrained('characters')
                ->cascadeOnDelete();

            $table->string('spell_index');
            $table->string('spell_name');
            $table->string('source_class_index')->nullable();

            $table->boolean('is_known')->default(true);
            $table->boolean('is_prepared')->default(false);
            $table->boolean('is_exception')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['character_id', 'spell_index'],
                'character_spells_character_spell_unique'
            );
        });

        // Espacios de conjuro normales o de magia de pacto
        Schema::create('character_spell_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('character_id')
                ->constrained('characters')
                ->cascadeOnDelete();

            $table->enum('slot_type', ['normal', 'pact']);
            $table->unsignedTinyInteger('spell_level');
            $table->unsignedTinyInteger('total_slots')->default(0);
            $table->unsignedTinyInteger('used_slots')->default(0);

            $table->timestamps();

            $table->unique(
                ['character_id', 'slot_type', 'spell_level'],
                'character_spell_slots_unique'
            );
        });
    }

    public function down(): void
    {
        // Primero se eliminan las tablas que dependen de characters.
        Schema::dropIfExists('character_spell_slots');
        Schema::dropIfExists('character_spells');
        Schema::dropIfExists('character_currency');
        Schema::dropIfExists('character_inventory');
        Schema::dropIfExists('character_proficiency_overrides');
    }
};
