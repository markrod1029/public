<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Raffle;

class Merchant extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the user that owns the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function raffles()
    {
        return $this->hasMany(Raffle::class, 'user_id', 'user_id');
    }



}
