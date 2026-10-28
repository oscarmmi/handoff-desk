<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatTurn extends Model
{
    protected $fillable = ['question', 'decision', 'reason', 'answer'];

    /** @return HasOne<Handoff, $this> */
    public function handoff(): HasOne
    {
        return $this->hasOne(Handoff::class);
    }
}
