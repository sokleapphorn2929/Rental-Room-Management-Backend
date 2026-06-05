<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiResponse extends Model
{
    protected $table = 'ai_responses';

    protected $fillable = [
        'prompt',
        'ai_payload',
    ];

    protected $casts = [
        'ai_payload' => 'array', 
    ];
}