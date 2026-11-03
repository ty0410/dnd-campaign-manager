<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('character_level_ups', function (Blueprint $table) {
            $table->string('feat_index')->nullable()->after('total_hp_gained');
            $table->string('feat_name')->nullable()->after('feat_index');
            $table->text('feat_description')->nullable()->after('feat_name');
            $table->json('ability_score_increases')->nullable()->after('feat_description');
        });
    }

    public function down(): void
    {
        Schema::table('character_level_ups', function (Blueprint $table) {
            $table->dropColumn(['feat_index', 'feat_name', 'feat_description', 'ability_score_increases']);
        });
    }
};
