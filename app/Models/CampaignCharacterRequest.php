<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class CampaignCharacterRequest extends Model
{
    public const PLAYER_REQUEST = 'player_request';
    public const DM_INVITATION = 'dm_invitation';

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'campaign_id',
        'character_id',
        'requested_by',
        'direction',
        'status',
        'responded_by',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Usuario al que le corresponde responder a la solicitud.
     * Una petición del jugador la aprueba el Dungeon Master;
     * una invitación del Dungeon Master la aprueba el dueño del personaje.
     */
    public function approverId(): ?int
    {
        $this->loadMissing(['campaign', 'character']);

        return (int) ($this->direction === self::PLAYER_REQUEST
            ? $this->campaign?->master_id
            : $this->character?->user_id);
    }

    /**
     * Incorpora el personaje a la campaña y a su dueño como participante.
     */
    public static function attachToCampaign(Campaign $campaign, Character $character): void
    {
        DB::transaction(function () use ($campaign, $character) {
            $character->campaigns()->syncWithoutDetaching([$campaign->id]);

            if ($character->user_id) {
                $campaign->users()->syncWithoutDetaching([$character->user_id]);
            }
        });
    }

    public function approve(User $by): void
    {
        DB::transaction(function () use ($by) {
            self::attachToCampaign($this->campaign, $this->character);

            $this->update([
                'status' => self::APPROVED,
                'responded_by' => $by->id,
                'responded_at' => now(),
            ]);
        });
    }

    public function reject(User $by): void
    {
        $this->update([
            'status' => self::REJECTED,
            'responded_by' => $by->id,
            'responded_at' => now(),
        ]);
    }
}
