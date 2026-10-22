
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_character', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->cascadeOnDelete();

            $table->foreignId('character_id')
                ->constrained('characters')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['campaign_id', 'character_id'],
                'campaign_character_unique'
            );
        });

        // Conservamos las asignaciones que ya existían.
        $now = now();

        DB::table('characters')
            ->whereNotNull('campaign_id')
            ->select(['id', 'campaign_id'])
            ->orderBy('id')
            ->chunk(200, function ($characters) use ($now) {
                $assignments = [];

                foreach ($characters as $character) {
                    $assignments[] = [
                        'campaign_id' => $character->campaign_id,
                        'character_id' => $character->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($assignments)) {
                    DB::table('campaign_character')
                        ->insertOrIgnore($assignments);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_character');
    }
};
