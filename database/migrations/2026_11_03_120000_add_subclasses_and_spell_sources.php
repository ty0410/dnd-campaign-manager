<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function indexExists(string $table, string $index): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index]
        );
        return (int) $result->total > 0;
    }

    public function up(): void
    {
        $classColumns = [];
        if (!Schema::hasColumn('character_classes', 'subclass_index')) $classColumns[] = 'subclass_index';
        if (!Schema::hasColumn('character_classes', 'subclass_name')) $classColumns[] = 'subclass_name';
        if ($classColumns) {
            Schema::table('character_classes', function (Blueprint $table) use ($classColumns) {
                if (in_array('subclass_index', $classColumns, true)) $table->string('subclass_index')->nullable()->after('class_index');
                if (in_array('subclass_name', $classColumns, true)) $table->string('subclass_name')->nullable()->after('subclass_index');
            });
        }

        if (!$this->indexExists('character_spells', 'character_spells_character_id_index')) {
            Schema::table('character_spells', function (Blueprint $table) {
                $table->index('character_id', 'character_spells_character_id_index');
            });
        }
        if ($this->indexExists('character_spells', 'character_spells_character_spell_unique')) {
            Schema::table('character_spells', function (Blueprint $table) {
                $table->dropUnique('character_spells_character_spell_unique');
            });
        }
        if (!$this->indexExists('character_spells', 'character_spells_source_unique')) {
            Schema::table('character_spells', function (Blueprint $table) {
                $table->unique(['character_id', 'spell_index', 'source_class_index'], 'character_spells_source_unique');
            });
        }

        $spellColumns = [];
        if (!Schema::hasColumn('character_spells', 'spell_level')) $spellColumns[] = 'spell_level';
        if (!Schema::hasColumn('character_spells', 'spell_type')) $spellColumns[] = 'spell_type';
        if ($spellColumns) {
            Schema::table('character_spells', function (Blueprint $table) use ($spellColumns) {
                if (in_array('spell_level', $spellColumns, true)) $table->unsignedTinyInteger('spell_level')->default(0)->after('spell_name');
                if (in_array('spell_type', $spellColumns, true)) $table->string('spell_type')->default('known')->after('source_class_index');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('character_spells', 'character_spells_source_unique')) {
            Schema::table('character_spells', fn (Blueprint $table) => $table->dropUnique('character_spells_source_unique'));
        }
        if (!$this->indexExists('character_spells', 'character_spells_character_spell_unique')) {
            Schema::table('character_spells', function (Blueprint $table) {
                $table->unique(['character_id', 'spell_index'], 'character_spells_character_spell_unique');
            });
        }
        $spellColumns = [];
        if (Schema::hasColumn('character_spells', 'spell_level')) $spellColumns[] = 'spell_level';
        if (Schema::hasColumn('character_spells', 'spell_type')) $spellColumns[] = 'spell_type';
        if ($spellColumns) Schema::table('character_spells', fn (Blueprint $table) => $table->dropColumn($spellColumns));
        if ($this->indexExists('character_spells', 'character_spells_character_id_index')) {
            Schema::table('character_spells', fn (Blueprint $table) => $table->dropIndex('character_spells_character_id_index'));
        }
        $classColumns = [];
        if (Schema::hasColumn('character_classes', 'subclass_index')) $classColumns[] = 'subclass_index';
        if (Schema::hasColumn('character_classes', 'subclass_name')) $classColumns[] = 'subclass_name';
        if ($classColumns) Schema::table('character_classes', fn (Blueprint $table) => $table->dropColumn($classColumns));
    }
};
