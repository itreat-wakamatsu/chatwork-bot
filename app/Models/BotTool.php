<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotTool extends Model
{
    protected $fillable = [
        'name',
        'label',
        'is_enabled',
    ];
}
