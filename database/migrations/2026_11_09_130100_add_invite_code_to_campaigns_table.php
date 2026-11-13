<?php

use App\Models\Campaign;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('invite_code', 12)->nullable()->unique()->after('image_path');
        });

        // Las campañas existentes también reciben su código.
        DB::table('campaigns')->whereNull('invite_code')->orderBy('id')->each(function ($campaign) {
            DB::table('campaigns')
                ->where('id', $campaign->id)
                ->update(['invite_code' => Campaign::generateInviteCode()]);
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropUnique(['invite_code']);
            $table->dropColumn('invite_code');
        });
    }
};
