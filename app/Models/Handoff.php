<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Handoff extends Model
{
    protected $fillable = ['chat_turn_id', 'queue', 'summary'];

    /** @return BelongsTo<ChatTurn, $this> */
    public function chatTurn(): BelongsTo
    {
        return $this->belongsTo(ChatTurn::class);
    }
}
