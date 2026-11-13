<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';

    protected $fillable = ['requester_id', 'addressee_id', 'status'];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function addressee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    /**
     * Relación entre dos usuarios, en cualquiera de los dos sentidos.
     */
    public function scopeBetween(Builder $query, int $a, int $b): Builder
    {
        return $query->where(function ($q) use ($a, $b) {
            $q->where(fn ($x) => $x->where('requester_id', $a)->where('addressee_id', $b))
                ->orWhere(fn ($x) => $x->where('requester_id', $b)->where('addressee_id', $a));
        });
    }
}
