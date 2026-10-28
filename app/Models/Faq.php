<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property array<int, string> $keywords
 */
class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'keywords'];

    protected function casts(): array
    {
        return ['keywords' => 'array'];
    }
}
