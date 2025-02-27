<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Raffle extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

}

